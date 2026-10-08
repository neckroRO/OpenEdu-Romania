<?php

namespace Tests\Feature;

use App\Data\Curriculum\CurriculumDuplicateSuggestion;
use App\Models\CurriculumAlias;
use App\Models\Subject;
use App\Services\Curriculum\CurriculumDuplicateSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumDuplicateSuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_normalized_subject_match_is_suggested(): void
    {
        $subject = Subject::create([
            'code' => 'romanian',
            'name' => 'Limba și literatura română',
            'status' => 'active',
        ]);

        $suggestions = app(
            CurriculumDuplicateSuggestionService::class
        )->forSubject(
            'limba si literatura romana'
        );

        $this->assertCount(1, $suggestions);

        $suggestion = $suggestions->first();

        $this->assertInstanceOf(
            CurriculumDuplicateSuggestion::class,
            $suggestion
        );

        $this->assertSame(
            $subject->id,
            $suggestion->entityId
        );

        $this->assertSame(
            'normalized_exact_match',
            $suggestion->reason
        );

        $this->assertSame(
            1.0,
            $suggestion->score
        );
    }

    public function test_similar_subject_name_is_suggested(): void
    {
        Subject::create([
            'code' => 'social-education',
            'name' => 'Educație socială',
            'status' => 'active',
        ]);

        $suggestions = app(
            CurriculumDuplicateSuggestionService::class
        )->forSubject(
            'Educatie social'
        );

        $this->assertCount(1, $suggestions);

        $suggestion = $suggestions->first();

        $this->assertSame(
            'similar_name',
            $suggestion->reason
        );

        $this->assertGreaterThanOrEqual(
            0.75,
            $suggestion->score
        );
    }

    public function test_unrelated_subject_is_not_suggested(): void
    {
        Subject::create([
            'code' => 'geography',
            'name' => 'Geografie',
            'status' => 'active',
        ]);

        $suggestions = app(
            CurriculumDuplicateSuggestionService::class
        )->forSubject(
            'Matematică'
        );

        $this->assertCount(0, $suggestions);
    }

    public function test_approved_alias_can_identify_subject(): void
    {
        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => $subject->id,
            'alias' => 'Mate',
            'normalized_alias' => 'mate',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $suggestions = app(
            CurriculumDuplicateSuggestionService::class
        )->forSubject(
            'MATE'
        );

        $this->assertCount(1, $suggestions);

        $suggestion = $suggestions->first();

        $this->assertSame(
            $subject->id,
            $suggestion->entityId
        );

        $this->assertSame(
            'approved_alias_match',
            $suggestion->reason
        );
    }

    public function test_pending_alias_is_ignored(): void
    {
        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => $subject->id,
            'alias' => 'Mate',
            'normalized_alias' => 'mate',
            'source' => 'community',
            'status' => 'pending',
        ]);

        $suggestions = app(
            CurriculumDuplicateSuggestionService::class
        )->forSubject(
            'Mate'
        );

        $this->assertCount(0, $suggestions);
    }

    public function test_subject_can_be_excluded_from_its_own_suggestions(): void
    {
        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $suggestions = app(
            CurriculumDuplicateSuggestionService::class
        )->forSubject(
            'Matematică',
            $subject->id
        );

        $this->assertCount(0, $suggestions);
    }

    public function test_duplicate_candidate_is_returned_only_once(): void
    {
        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => $subject->id,
            'alias' => 'Matematica',
            'normalized_alias' => 'matematica',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $suggestions = app(
            CurriculumDuplicateSuggestionService::class
        )->forSubject(
            'Matematica'
        );

        $this->assertCount(1, $suggestions);
    }

    public function test_suggestion_can_be_serialized_to_array(): void
    {
        $suggestion = new CurriculumDuplicateSuggestion(
            entityType: 'subject',
            entityId: 15,
            name: 'Matematică',
            score: 1.0,
            level: 'high',
            reason: 'approved_alias_match',
        );

        $this->assertSame([
            'entity_type' => 'subject',
            'entity_id' => 15,
            'name' => 'Matematică',
            'score' => 1.0,
            'level' => 'high',
            'reason' => 'approved_alias_match',
        ], $suggestion->toArray());
    }
}
