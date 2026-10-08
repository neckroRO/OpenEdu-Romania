<?php

namespace Tests\Feature;

use App\Models\CurriculumAlias;
use App\Services\Curriculum\CurriculumDuplicateMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumDuplicateMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalized_labels_can_match_exactly(): void
    {
        $matcher = app(CurriculumDuplicateMatcher::class);

        $this->assertTrue(
            $matcher->normalizedEquals(
                'Limba și literatura română',
                'limba si literatura romana'
            )
        );
    }

    public function test_different_normalized_labels_do_not_match(): void
    {
        $matcher = app(CurriculumDuplicateMatcher::class);

        $this->assertFalse(
            $matcher->normalizedEquals(
                'Matematică',
                'Fizică'
            )
        );
    }

    public function test_identical_normalized_labels_have_maximum_similarity(): void
    {
        $matcher = app(CurriculumDuplicateMatcher::class);

        $this->assertSame(
            1.0,
            $matcher->similarityScore(
                'Limba și literatura română',
                'limba si literatura romana'
            )
        );
    }

    public function test_similar_labels_have_high_similarity(): void
    {
        $matcher = app(CurriculumDuplicateMatcher::class);

        $score = $matcher->similarityScore(
            'Educație socială',
            'Educatie sociala'
        );

        $this->assertGreaterThanOrEqual(
            0.90,
            $score
        );
    }

    public function test_different_labels_have_low_similarity(): void
    {
        $matcher = app(CurriculumDuplicateMatcher::class);

        $score = $matcher->similarityScore(
            'Matematică',
            'Geografie'
        );

        $this->assertLessThan(
            0.75,
            $score
        );
    }

    public function test_empty_label_has_zero_similarity(): void
    {
        $matcher = app(CurriculumDuplicateMatcher::class);

        $this->assertSame(
            0.0,
            $matcher->similarityScore(
                '',
                'Matematică'
            )
        );
    }

    public function test_similarity_levels_are_classified(): void
    {
        $matcher = app(CurriculumDuplicateMatcher::class);

        $this->assertSame(
            'high',
            $matcher->similarityLevel(0.95)
        );

        $this->assertSame(
            'medium',
            $matcher->similarityLevel(0.80)
        );

        $this->assertSame(
            'low',
            $matcher->similarityLevel(0.50)
        );
    }

    public function test_it_finds_approved_alias(): void
    {
        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => 15,
            'alias' => 'Mate',
            'normalized_alias' => 'mate',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $matches = app(CurriculumDuplicateMatcher::class)
            ->approvedAliasMatches(
                'subject',
                'MATE'
            );

        $this->assertCount(1, $matches);

        $this->assertSame(
            15,
            $matches->first()->entity_id
        );
    }

    public function test_pending_alias_is_not_considered_a_match(): void
    {
        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => 15,
            'alias' => 'Mate',
            'normalized_alias' => 'mate',
            'source' => 'community',
            'status' => 'pending',
        ]);

        $matches = app(CurriculumDuplicateMatcher::class)
            ->approvedAliasMatches(
                'subject',
                'Mate'
            );

        $this->assertCount(0, $matches);
    }

    public function test_alias_matching_is_scoped_by_entity_type(): void
    {
        CurriculumAlias::create([
            'entity_type' => 'curriculum_area',
            'entity_id' => 3,
            'alias' => 'Științe',
            'normalized_alias' => 'stiinte',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $matches = app(CurriculumDuplicateMatcher::class)
            ->approvedAliasMatches(
                'subject',
                'Științe'
            );

        $this->assertCount(0, $matches);
    }

    public function test_empty_value_has_no_alias_matches(): void
    {
        $matches = app(CurriculumDuplicateMatcher::class)
            ->approvedAliasMatches(
                'subject',
                '   '
            );

        $this->assertCount(0, $matches);
    }
}
