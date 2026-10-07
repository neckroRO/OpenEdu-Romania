<?php

namespace App\Http\Requests;

use App\Enums\LessonReviewVerdict;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePedagogicalReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verdict' => [
                'required',
                Rule::enum(LessonReviewVerdict::class),
            ],

            'correctness_score' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'curriculum_alignment_score' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'clarity_score' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'pedagogical_value_score' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'difficulty_fit_score' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'comment' => [
                'required_unless:verdict,approve',
                'string',
                'max:5000',
            ],
        ];
    }
}
