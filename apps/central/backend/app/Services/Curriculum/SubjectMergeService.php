<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumAlias;
use App\Models\CurriculumEntityMerge;
use App\Models\CurriculumSubject;
use App\Models\ReputationEvent;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use DomainException;
use Illuminate\Support\Facades\DB;

class SubjectMergeService
{
    public function __construct(
        private readonly CurriculumTextNormalizer $normalizer
    ) {
    }

    public function merge(
        Subject $source,
        Subject $target,
        User $merger,
        ?string $reason = null
    ): CurriculumEntityMerge {
        if ($source->id === $target->id) {
            throw new DomainException(
                'A subject cannot be merged into itself.'
            );
        }

        return DB::transaction(function () use (
            $source,
            $target,
            $merger,
            $reason
        ): CurriculumEntityMerge {
            $source = Subject::query()
                ->lockForUpdate()
                ->findOrFail($source->id);

            $target = Subject::query()
                ->lockForUpdate()
                ->findOrFail($target->id);

            if ($source->status === 'merged') {
                throw new DomainException(
                    'The source subject has already been merged.'
                );
            }

            if ($target->status === 'merged') {
                throw new DomainException(
                    'A merged subject cannot be used as canonical target.'
                );
            }

            $this->assertNoCurriculumSubjectCollisions(
                $source,
                $target
            );

            $this->moveCurriculumSubjects(
                $source,
                $target
            );

            $this->mergeReputations(
                $source,
                $target
            );

            $this->moveReputationEvents(
                $source,
                $target
            );

            $this->moveAliases(
                $source,
                $target
            );

            $this->createSourceNameAlias(
                $source,
                $target,
                $merger
            );

            $merge = CurriculumEntityMerge::create([
                'entity_type' => 'subject',
                'source_entity_id' => $source->id,
                'target_entity_id' => $target->id,
                'merged_by' => $merger->id,
                'merged_at' => now(),
                'reason' => $reason,
                'metadata' => [
                    'source_code' => $source->code,
                    'source_name' => $source->name,
                    'target_code' => $target->code,
                    'target_name' => $target->name,
                ],
            ]);

            $source->update([
                'status' => 'merged',
            ]);

            return $merge->refresh();
        });
    }

    private function assertNoCurriculumSubjectCollisions(
        Subject $source,
        Subject $target
    ): void {
        $sourceLinks = CurriculumSubject::query()
            ->where('subject_id', $source->id)
            ->get();

        foreach ($sourceLinks as $sourceLink) {
            $query = CurriculumSubject::query()
                ->where(
                    'curriculum_version_id',
                    $sourceLink->curriculum_version_id
                )
                ->where(
                    'education_level_id',
                    $sourceLink->education_level_id
                )
                ->where(
                    'subject_id',
                    $target->id
                );

            if ($sourceLink->curriculum_framework_variant_id === null) {
                $query->whereNull(
                    'curriculum_framework_variant_id'
                );
            } else {
                $query->where(
                    'curriculum_framework_variant_id',
                    $sourceLink->curriculum_framework_variant_id
                );
            }

            if ($query->exists()) {
                throw new DomainException(
                    'The merge would create a duplicate curriculum subject.'
                );
            }
        }
    }

    private function moveCurriculumSubjects(
        Subject $source,
        Subject $target
    ): void {
        CurriculumSubject::query()
            ->where('subject_id', $source->id)
            ->update([
                'subject_id' => $target->id,
            ]);
    }

    private function mergeReputations(
        Subject $source,
        Subject $target
    ): void {
        $sourceReputations = UserSubjectReputation::query()
            ->where('subject_id', $source->id)
            ->lockForUpdate()
            ->get();

        foreach ($sourceReputations as $sourceReputation) {
            $targetReputation = UserSubjectReputation::query()
                ->where(
                    'user_id',
                    $sourceReputation->user_id
                )
                ->where(
                    'subject_id',
                    $target->id
                )
                ->lockForUpdate()
                ->first();

            if ($targetReputation === null) {
                $sourceReputation->update([
                    'subject_id' => $target->id,
                ]);

                continue;
            }

            $targetReputation->update([
                'score' =>
                    $targetReputation->score
                    + $sourceReputation->score,
                'contribution_count' =>
                    $targetReputation->contribution_count
                    + $sourceReputation->contribution_count,
                'review_count' =>
                    $targetReputation->review_count
                    + $sourceReputation->review_count,
            ]);

            $sourceReputation->delete();
        }
    }

    private function moveReputationEvents(
        Subject $source,
        Subject $target
    ): void {
        ReputationEvent::query()
            ->where('subject_id', $source->id)
            ->update([
                'subject_id' => $target->id,
            ]);
    }

    private function moveAliases(
        Subject $source,
        Subject $target
    ): void {
        $aliases = CurriculumAlias::query()
            ->where('entity_type', 'subject')
            ->where('entity_id', $source->id)
            ->get();

        foreach ($aliases as $alias) {
            $alreadyExists = CurriculumAlias::query()
                ->where('entity_type', 'subject')
                ->where('entity_id', $target->id)
                ->where(
                    'normalized_alias',
                    $alias->normalized_alias
                )
                ->exists();

            if ($alreadyExists) {
                $alias->delete();

                continue;
            }

            $alias->update([
                'entity_id' => $target->id,
            ]);
        }
    }

    private function createSourceNameAlias(
        Subject $source,
        Subject $target,
        User $merger
    ): void {
        $normalizedSourceName = $this->normalizer->normalize(
            $source->name
        );

        $normalizedTargetName = $this->normalizer->normalize(
            $target->name
        );

        if (
            $normalizedSourceName === ''
            || $normalizedSourceName === $normalizedTargetName
        ) {
            return;
        }

        CurriculumAlias::firstOrCreate(
            [
                'entity_type' => 'subject',
                'entity_id' => $target->id,
                'normalized_alias' => $normalizedSourceName,
            ],
            [
                'alias' => $source->name,
                'source' => 'system',
                'status' => 'approved',
                'created_by' => $merger->id,
                'approved_by' => $merger->id,
            ]
        );
    }
}
