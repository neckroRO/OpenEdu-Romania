<?php

namespace App\Services\Curriculum;

use App\Data\Curriculum\SubjectMergePreview;
use App\Models\CurriculumAlias;
use App\Models\CurriculumSubject;
use App\Models\ReputationEvent;
use App\Models\Subject;
use App\Models\UserSubjectReputation;

class SubjectMergePreviewService
{
    public function __construct(
        private readonly CurriculumTextNormalizer $normalizer
    ) {
    }

    public function preview(
        Subject $source,
        Subject $target
    ): SubjectMergePreview {
        $blockingReasons = [];

        if ($source->id === $target->id) {
            $blockingReasons[] = 'same_subject';
        }

        if ($source->status === 'merged') {
            $blockingReasons[] = 'source_already_merged';
        }

        if ($target->status === 'merged') {
            $blockingReasons[] = 'target_already_merged';
        }

        $sourceLinks = CurriculumSubject::query()
            ->where('subject_id', $source->id)
            ->get();

        $collisionCount = 0;

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
                $collisionCount++;
            }
        }

        if ($collisionCount > 0) {
            $blockingReasons[] = 'curriculum_subject_collision';
        }

        $sourceAliases = CurriculumAlias::query()
            ->where('entity_type', 'subject')
            ->where('entity_id', $source->id)
            ->get();

        $aliasesToMove = 0;
        $aliasesToDeduplicate = 0;

        foreach ($sourceAliases as $alias) {
            $existsOnTarget = CurriculumAlias::query()
                ->where('entity_type', 'subject')
                ->where('entity_id', $target->id)
                ->where(
                    'normalized_alias',
                    $alias->normalized_alias
                )
                ->exists();

            if ($existsOnTarget) {
                $aliasesToDeduplicate++;
            } else {
                $aliasesToMove++;
            }
        }

        $sourceNameNormalized = $this->normalizer->normalize(
            $source->name
        );

        $targetNameNormalized = $this->normalizer->normalize(
            $target->name
        );

        $sourceNameAliasExists = CurriculumAlias::query()
            ->where('entity_type', 'subject')
            ->where('entity_id', $target->id)
            ->where(
                'normalized_alias',
                $sourceNameNormalized
            )
            ->exists();

        $sourceNameAliasWillBeCreated =
            $sourceNameNormalized !== ''
            && $sourceNameNormalized !== $targetNameNormalized
            && ! $sourceNameAliasExists;

        $sourceReputations = UserSubjectReputation::query()
            ->where('subject_id', $source->id)
            ->get();

        $reputationsToMove = 0;
        $reputationsToConsolidate = 0;

        foreach ($sourceReputations as $sourceReputation) {
            $targetExists = UserSubjectReputation::query()
                ->where(
                    'user_id',
                    $sourceReputation->user_id
                )
                ->where(
                    'subject_id',
                    $target->id
                )
                ->exists();

            if ($targetExists) {
                $reputationsToConsolidate++;
            } else {
                $reputationsToMove++;
            }
        }

        $reputationEventsToMove = ReputationEvent::query()
            ->where('subject_id', $source->id)
            ->count();

        return new SubjectMergePreview(
            sourceSubjectId: $source->id,
            targetSubjectId: $target->id,
            curriculumSubjectsToMove: $sourceLinks->count(),
            curriculumSubjectCollisions: $collisionCount,
            aliasesToMove: $aliasesToMove,
            aliasesToDeduplicate: $aliasesToDeduplicate,
            sourceNameAliasWillBeCreated: $sourceNameAliasWillBeCreated,
            reputationsToMove: $reputationsToMove,
            reputationsToConsolidate: $reputationsToConsolidate,
            reputationEventsToMove: $reputationEventsToMove,
            blocked: $blockingReasons !== [],
            blockingReasons: $blockingReasons,
        );
    }
}
