<?php

namespace App\Services\Curriculum;

use App\Data\Curriculum\ConceptMergePreview;
use App\Models\CompetencyConcept;
use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\CurriculumAlias;
use App\Models\LessonConcept;
use App\Models\ResourceConcept;

class ConceptMergePreviewService
{
    public function __construct(
        private readonly CurriculumTextNormalizer $normalizer
    ) {
    }

    public function preview(
        Concept $source,
        Concept $target
    ): ConceptMergePreview {
        $blockingReasons = [];

        if ($source->id === $target->id) {
            $blockingReasons[] = 'same_concept';
        }

        if ($source->status === 'merged') {
            $blockingReasons[] = 'source_already_merged';
        }

        if ($target->status === 'merged') {
            $blockingReasons[] = 'target_already_merged';
        }

        [
            $placementsToMove,
            $placementsToDeduplicate,
        ] = $this->countRelations(
            ConceptPlacement::query()
                ->where('concept_id', $source->id)
                ->get(),
            ConceptPlacement::class,
            'domain_id',
            $target->id
        );

        [
            $competenciesToMove,
            $competenciesToDeduplicate,
        ] = $this->countRelations(
            CompetencyConcept::query()
                ->where('concept_id', $source->id)
                ->get(),
            CompetencyConcept::class,
            'competency_id',
            $target->id
        );

        [
            $lessonsToMove,
            $lessonsToDeduplicate,
        ] = $this->countRelations(
            LessonConcept::query()
                ->where('concept_id', $source->id)
                ->get(),
            LessonConcept::class,
            'lesson_id',
            $target->id
        );

        [
            $resourcesToMove,
            $resourcesToDeduplicate,
        ] = $this->countRelations(
            ResourceConcept::query()
                ->where('concept_id', $source->id)
                ->get(),
            ResourceConcept::class,
            'resource_id',
            $target->id
        );

        $sourceAliases = CurriculumAlias::query()
            ->where('entity_type', 'concept')
            ->where('entity_id', $source->id)
            ->get();

        $aliasesToMove = 0;
        $aliasesToDeduplicate = 0;

        foreach ($sourceAliases as $alias) {
            $existsOnTarget = CurriculumAlias::query()
                ->where('entity_type', 'concept')
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

        $sourceTitleNormalized = $this->normalizer->normalize(
            $source->title
        );

        $targetTitleNormalized = $this->normalizer->normalize(
            $target->title
        );

        $sourceTitleAliasExists = CurriculumAlias::query()
            ->where('entity_type', 'concept')
            ->where('entity_id', $target->id)
            ->where(
                'normalized_alias',
                $sourceTitleNormalized
            )
            ->exists();

        $sourceTitleAliasWillBeCreated =
            $sourceTitleNormalized !== ''
            && $sourceTitleNormalized !== $targetTitleNormalized
            && ! $sourceTitleAliasExists;

        return new ConceptMergePreview(
            sourceConceptId: $source->id,
            targetConceptId: $target->id,

            placementsToMove: $placementsToMove,
            placementsToDeduplicate:
                $placementsToDeduplicate,

            competenciesToMove: $competenciesToMove,
            competenciesToDeduplicate:
                $competenciesToDeduplicate,

            lessonsToMove: $lessonsToMove,
            lessonsToDeduplicate:
                $lessonsToDeduplicate,

            resourcesToMove: $resourcesToMove,
            resourcesToDeduplicate:
                $resourcesToDeduplicate,

            aliasesToMove: $aliasesToMove,
            aliasesToDeduplicate:
                $aliasesToDeduplicate,

            sourceTitleAliasWillBeCreated:
                $sourceTitleAliasWillBeCreated,

            blocked: $blockingReasons !== [],
            blockingReasons: $blockingReasons,
        );
    }

    private function countRelations(
        iterable $sourceLinks,
        string $modelClass,
        string $foreignKey,
        int $targetConceptId
    ): array {
        $toMove = 0;
        $toDeduplicate = 0;

        foreach ($sourceLinks as $sourceLink) {
            $existsOnTarget = $modelClass::query()
                ->where(
                    $foreignKey,
                    $sourceLink->{$foreignKey}
                )
                ->where(
                    'concept_id',
                    $targetConceptId
                )
                ->exists();

            if ($existsOnTarget) {
                $toDeduplicate++;
            } else {
                $toMove++;
            }
        }

        return [
            $toMove,
            $toDeduplicate,
        ];
    }
}
