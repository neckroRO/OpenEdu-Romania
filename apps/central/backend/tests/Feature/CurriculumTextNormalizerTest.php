<?php

namespace Tests\Feature;

use App\Services\Curriculum\CurriculumTextNormalizer;
use Tests\TestCase;

class CurriculumTextNormalizerTest extends TestCase
{
    public function test_it_normalizes_case_and_diacritics(): void
    {
        $normalizer = app(CurriculumTextNormalizer::class);

        $this->assertSame(
            'limba si literatura romana',
            $normalizer->normalize(
                'Limba și literatura română'
            )
        );
    }

    public function test_it_normalizes_punctuation_and_extra_whitespace(): void
    {
        $normalizer = app(CurriculumTextNormalizer::class);

        $this->assertSame(
            'educatie sociala',
            $normalizer->normalize(
                '  Educație   socială!!! '
            )
        );
    }

    public function test_it_preserves_numbers(): void
    {
        $normalizer = app(CurriculumTextNormalizer::class);

        $this->assertSame(
            'limba moderna 1',
            $normalizer->normalize(
                'Limba modernă 1'
            )
        );
    }

    public function test_empty_input_remains_empty(): void
    {
        $normalizer = app(CurriculumTextNormalizer::class);

        $this->assertSame(
            '',
            $normalizer->normalize('   ')
        );
    }

    public function test_equivalent_romanian_labels_have_same_normalized_form(): void
    {
        $normalizer = app(CurriculumTextNormalizer::class);

        $this->assertSame(
            $normalizer->normalize(
                'Limba și literatura română'
            ),
            $normalizer->normalize(
                'limba si literatura romana'
            )
        );
    }
}
