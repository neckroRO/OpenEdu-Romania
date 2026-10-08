<?php

namespace App\Services\Curriculum;

use App\Data\Curriculum\CurriculumDuplicateSuggestion;
use App\Models\Subject;
use Illuminate\Support\Collection;

class CurriculumDuplicateSuggestionService
{
    public function __construct(
        private readonly CurriculumDuplicateMatcher $matcher
    ) {
    }

    public function forSubject(
        string $name,
        ?int $excludeSubjectId = null
    ): Collection {
        $candidates = collect();

        $query = Subject::query();

        if ($excludeSubjectId !== null) {
            $query->whereKeyNot($excludeSubjectId);
        }

        foreach ($query->get() as $subject) {
            $score = $this->matcher->similarityScore(
                $name,
                $subject->name
            );

            if (
                $this->matcher->normalizedEquals(
                    $name,
                    $subject->name
                )
            ) {
                $candidates->push(
                    new CurriculumDuplicateSuggestion(
                        entityType: 'subject',
                        entityId: $subject->id,
                        name: $subject->name,
                        score: 1.0,
                        level: 'high',
                        reason: 'normalized_exact_match',
                    )
                );

                continue;
            }

            if ($score >= 0.75) {
                $candidates->push(
                    new CurriculumDuplicateSuggestion(
                        entityType: 'subject',
                        entityId: $subject->id,
                        name: $subject->name,
                        score: $score,
                        level: $this->matcher->similarityLevel($score),
                        reason: 'similar_name',
                    )
                );
            }
        }

        $aliasMatches = $this->matcher->approvedAliasMatches(
            'subject',
            $name
        );

        foreach ($aliasMatches as $alias) {
            if (
                $excludeSubjectId !== null
                && $alias->entity_id === $excludeSubjectId
            ) {
                continue;
            }

            $subject = Subject::find($alias->entity_id);

            if ($subject === null) {
                continue;
            }

            $alreadyPresent = $candidates->contains(
                fn (CurriculumDuplicateSuggestion $candidate): bool =>
                    $candidate->entityId === $subject->id
            );

            if ($alreadyPresent) {
                continue;
            }

            $candidates->push(
                new CurriculumDuplicateSuggestion(
                    entityType: 'subject',
                    entityId: $subject->id,
                    name: $subject->name,
                    score: 1.0,
                    level: 'high',
                    reason: 'approved_alias_match',
                )
            );
        }

        return $candidates
            ->sortByDesc(
                fn (CurriculumDuplicateSuggestion $candidate): float =>
                    $candidate->score
            )
            ->values();
    }
}
