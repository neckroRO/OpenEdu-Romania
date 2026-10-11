<?php

namespace App\Data\Curriculum;

class ConceptMergePreview
{
    public function __construct(
        public readonly int $sourceConceptId,
        public readonly int $targetConceptId,

        public readonly int $placementsToMove,
        public readonly int $placementsToDeduplicate,

        public readonly int $competenciesToMove,
        public readonly int $competenciesToDeduplicate,

        public readonly int $lessonsToMove,
        public readonly int $lessonsToDeduplicate,

        public readonly int $resourcesToMove,
        public readonly int $resourcesToDeduplicate,

        public readonly int $aliasesToMove,
        public readonly int $aliasesToDeduplicate,

        public readonly bool $sourceTitleAliasWillBeCreated,

        public readonly bool $blocked,
        public readonly array $blockingReasons,
    ) {
    }

    public function toArray(): array
    {
        return [
            'source_concept_id' =>
                $this->sourceConceptId,

            'target_concept_id' =>
                $this->targetConceptId,

            'placements_to_move' =>
                $this->placementsToMove,

            'placements_to_deduplicate' =>
                $this->placementsToDeduplicate,

            'competencies_to_move' =>
                $this->competenciesToMove,

            'competencies_to_deduplicate' =>
                $this->competenciesToDeduplicate,

            'lessons_to_move' =>
                $this->lessonsToMove,

            'lessons_to_deduplicate' =>
                $this->lessonsToDeduplicate,

            'resources_to_move' =>
                $this->resourcesToMove,

            'resources_to_deduplicate' =>
                $this->resourcesToDeduplicate,

            'aliases_to_move' =>
                $this->aliasesToMove,

            'aliases_to_deduplicate' =>
                $this->aliasesToDeduplicate,

            'source_title_alias_will_be_created' =>
                $this->sourceTitleAliasWillBeCreated,

            'blocked' =>
                $this->blocked,

            'blocking_reasons' =>
                $this->blockingReasons,
        ];
    }
}
