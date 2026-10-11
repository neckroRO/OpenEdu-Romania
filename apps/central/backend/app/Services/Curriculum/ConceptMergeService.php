<?php

namespace App\Services\Curriculum;

use App\Models\CompetencyConcept;
use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\CurriculumAlias;
use App\Models\CurriculumEntityMerge;
use App\Models\LessonConcept;
use App\Models\ResourceConcept;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConceptMergeService
{
    public function __construct(
        private readonly CurriculumTextNormalizer $normalizer
    ) {
    }

    public function merge(
        Concept $source,
        Concept $target,
        User $merger,
        ?string $reason = null
    ): CurriculumEntityMerge {
        if ($source->id === $target->id) {
            throw new DomainException(
                'A concept cannot be merged into itself.'
            );
        }

        return DB::transaction(function () use (
            $source,
            $target,
            $merger,
            $reason
        ): CurriculumEntityMerge {
            $source = Concept::query()
                ->lockForUpdate()
                ->findOrFail($source->id);

            $target = Concept::query()
                ->lockForUpdate()
                ->findOrFail($target->id);

            if ($source->status === 'merged') {
                throw new DomainException(
                    'The source concept has already been merged.'
                );
            }

            if ($target->status === 'merged') {
                throw new DomainException(
                    'A merged concept cannot be used as canonical target.'
                );
            }

            /*
             * Placements must be consolidated first.
             *
             * LessonConcept validates that the linked concept belongs
             * to the same curriculum subject as the lesson. Moving
             * placements first guarantees that the canonical target
             * inherits all source curriculum scopes before lesson
             * links are repointed.
             */
            $this->mergePlacements(
                $source,
                $target
            );

            $this->mergeCompetencies(
                $source,
                $target
            );

            $this->mergeLessons(
                $source,
                $target
            );

            $this->mergeResources(
                $source,
                $target
            );

            $this->moveAliases(
                $source,
                $target
            );

            $this->createSourceTitleAlias(
                $source,
                $target,
                $merger
            );

            $merge = CurriculumEntityMerge::create([
                'entity_type' => 'concept',
                'source_entity_id' => $source->id,
                'target_entity_id' => $target->id,
                'merged_by' => $merger->id,
                'merged_at' => now(),
                'reason' => $reason,
                'metadata' => [
                    'source_code' => $source->code,
                    'source_title' => $source->title,
                    'target_code' => $target->code,
                    'target_title' => $target->title,
                ],
            ]);

            $source->update([
                'status' => 'merged',
            ]);

            return $merge->refresh();
        });
    }

    private function mergePlacements(
        Concept $source,
        Concept $target
    ): void {
        $links = ConceptPlacement::query()
            ->where('concept_id', $source->id)
            ->lockForUpdate()
            ->get();

        foreach ($links as $sourceLink) {
            $targetLink = ConceptPlacement::query()
                ->where('concept_id', $target->id)
                ->where('domain_id', $sourceLink->domain_id)
                ->lockForUpdate()
                ->first();

            if ($targetLink === null) {
                $sourceLink->update([
                    'concept_id' => $target->id,
                ]);

                continue;
            }

            $this->consolidateCoreRelation(
                $sourceLink,
                $targetLink
            );
        }
    }

    private function mergeCompetencies(
        Concept $source,
        Concept $target
    ): void {
        $links = CompetencyConcept::query()
            ->where('concept_id', $source->id)
            ->lockForUpdate()
            ->get();

        foreach ($links as $sourceLink) {
            $targetLink = CompetencyConcept::query()
                ->where(
                    'competency_id',
                    $sourceLink->competency_id
                )
                ->where('concept_id', $target->id)
                ->lockForUpdate()
                ->first();

            if ($targetLink === null) {
                $sourceLink->update([
                    'concept_id' => $target->id,
                ]);

                continue;
            }

            $this->consolidateCoreRelation(
                $sourceLink,
                $targetLink
            );
        }
    }

    private function mergeLessons(
        Concept $source,
        Concept $target
    ): void {
        $links = LessonConcept::query()
            ->where('concept_id', $source->id)
            ->lockForUpdate()
            ->get();

        foreach ($links as $sourceLink) {
            $targetLink = LessonConcept::query()
                ->where(
                    'lesson_id',
                    $sourceLink->lesson_id
                )
                ->where('concept_id', $target->id)
                ->lockForUpdate()
                ->first();

            if ($targetLink === null) {
                $sourceLink->update([
                    'concept_id' => $target->id,
                ]);

                continue;
            }

            $this->consolidateCoreRelation(
                $sourceLink,
                $targetLink
            );
        }
    }

    private function mergeResources(
        Concept $source,
        Concept $target
    ): void {
        $links = ResourceConcept::query()
            ->where('concept_id', $source->id)
            ->lockForUpdate()
            ->get();

        foreach ($links as $sourceLink) {
            $targetLink = ResourceConcept::query()
                ->where(
                    'resource_id',
                    $sourceLink->resource_id
                )
                ->where('concept_id', $target->id)
                ->lockForUpdate()
                ->first();

            if ($targetLink === null) {
                $sourceLink->update([
                    'concept_id' => $target->id,
                ]);

                continue;
            }

            $targetLink->update([
                'display_order' => min(
                    $sourceLink->display_order,
                    $targetLink->display_order
                ),
                'is_primary' =>
                    $sourceLink->is_primary
                    || $targetLink->is_primary,
            ]);

            $sourceLink->delete();
        }
    }

    private function consolidateCoreRelation(
        Model $sourceLink,
        Model $targetLink
    ): void {
        $targetLink->update([
            'display_order' => min(
                $sourceLink->display_order,
                $targetLink->display_order
            ),
            'is_core' =>
                $sourceLink->is_core
                || $targetLink->is_core,
        ]);

        $sourceLink->delete();
    }

    private function moveAliases(
        Concept $source,
        Concept $target
    ): void {
        $aliases = CurriculumAlias::query()
            ->where('entity_type', 'concept')
            ->where('entity_id', $source->id)
            ->lockForUpdate()
            ->get();

        foreach ($aliases as $alias) {
            $alreadyExists = CurriculumAlias::query()
                ->where('entity_type', 'concept')
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

    private function createSourceTitleAlias(
        Concept $source,
        Concept $target,
        User $merger
    ): void {
        $normalizedSourceTitle = $this->normalizer->normalize(
            $source->title
        );

        $normalizedTargetTitle = $this->normalizer->normalize(
            $target->title
        );

        if (
            $normalizedSourceTitle === ''
            || $normalizedSourceTitle === $normalizedTargetTitle
        ) {
            return;
        }

        CurriculumAlias::firstOrCreate(
            [
                'entity_type' => 'concept',
                'entity_id' => $target->id,
                'normalized_alias' => $normalizedSourceTitle,
            ],
            [
                'alias' => $source->title,
                'source' => 'system',
                'status' => 'approved',
                'created_by' => $merger->id,
                'approved_by' => $merger->id,
            ]
        );
    }
}
