<?php

namespace App\Services\Curriculum;

use Illuminate\Support\Str;

class CurriculumTextNormalizer
{
    public function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = Str::lower($value);
        $value = Str::ascii($value);

        $value = preg_replace(
            '/[^\p{L}\p{N}]+/u',
            ' ',
            $value
        ) ?? '';

        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        ) ?? '';

        return trim($value);
    }
}
