<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumAlias;
use Illuminate\Support\Collection;

class CurriculumDuplicateMatcher
{
    public function __construct(
        private readonly CurriculumTextNormalizer $normalizer
    ) {
    }

    public function normalizedEquals(
        string $left,
        string $right
    ): bool {
        return $this->normalizer->normalize($left)
            === $this->normalizer->normalize($right);
    }

    public function similarityScore(
        string $left,
        string $right
    ): float {
        $leftNormalized = $this->normalizer->normalize($left);
        $rightNormalized = $this->normalizer->normalize($right);

        if (
            $leftNormalized === ''
            || $rightNormalized === ''
        ) {
            return 0.0;
        }

        if ($leftNormalized === $rightNormalized) {
            return 1.0;
        }

        similar_text(
            $leftNormalized,
            $rightNormalized,
            $percentage
        );

        return round(
            $percentage / 100,
            4
        );
    }

    public function similarityLevel(float $score): string
    {
        if ($score >= 0.90) {
            return 'high';
        }

        if ($score >= 0.75) {
            return 'medium';
        }

        return 'low';
    }

    public function approvedAliasMatches(
        string $entityType,
        string $value
    ): Collection {
        $normalized = $this->normalizer->normalize($value);

        if ($normalized === '') {
            return collect();
        }

        return CurriculumAlias::query()
            ->where('entity_type', $entityType)
            ->where('normalized_alias', $normalized)
            ->where('status', 'approved')
            ->get();
    }
}
