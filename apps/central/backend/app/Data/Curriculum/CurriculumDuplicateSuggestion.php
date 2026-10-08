<?php

namespace App\Data\Curriculum;

class CurriculumDuplicateSuggestion
{
    public function __construct(
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly string $name,
        public readonly float $score,
        public readonly string $level,
        public readonly string $reason,
    ) {
    }

    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'name' => $this->name,
            'score' => $this->score,
            'level' => $this->level,
            'reason' => $this->reason,
        ];
    }
}
