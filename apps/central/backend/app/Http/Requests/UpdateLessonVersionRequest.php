<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'summary' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'learning_objectives' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'learning_objectives.*' => [
                'string',
                'max:1000',
            ],

            'content' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'estimated_duration_minutes' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:600',
            ],

            'language_code' => [
                'sometimes',
                'string',
                'size:2',
            ],
        ];
    }
}
