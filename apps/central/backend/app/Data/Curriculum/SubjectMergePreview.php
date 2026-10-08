<?php

namespace App\Data\Curriculum;

class SubjectMergePreview
{
    public function __construct(
        public readonly int $sourceSubjectId,
        public readonly int $targetSubjectId,
        public readonly int $curriculumSubjectsToMove,
        public readonly int $curriculumSubjectCollisions,
        public readonly int $aliasesToMove,
        public readonly int $aliasesToDeduplicate,
        public readonly bool $sourceNameAliasWillBeCreated,
        public readonly int $reputationsToMove,
        public readonly int $reputationsToConsolidate,
        public readonly int $reputationEventsToMove,
        public readonly bool $blocked,
        public readonly array $blockingReasons,
    ) {
    }

    public function toArray(): array
    {
        return [
            'source_subject_id' => $this->sourceSubjectId,
            'target_subject_id' => $this->targetSubjectId,
            'curriculum_subjects_to_move' =>
                $this->curriculumSubjectsToMove,
            'curriculum_subject_collisions' =>
                $this->curriculumSubjectCollisions,
            'aliases_to_move' => $this->aliasesToMove,
            'aliases_to_deduplicate' =>
                $this->aliasesToDeduplicate,
            'source_name_alias_will_be_created' =>
                $this->sourceNameAliasWillBeCreated,
            'reputations_to_move' => $this->reputationsToMove,
            'reputations_to_consolidate' =>
                $this->reputationsToConsolidate,
            'reputation_events_to_move' =>
                $this->reputationEventsToMove,
            'blocked' => $this->blocked,
            'blocking_reasons' => $this->blockingReasons,
        ];
    }
}
