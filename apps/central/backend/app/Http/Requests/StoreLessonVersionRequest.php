<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'summary' => [
                'nullable',
                'string',
            ],

            'learning_objectives' => [
                'nullable',
                'array',
            ],

            'learning_objectives.*' => [
                'string',
                'max:1000',
            ],

            'content' => [
                'nullable',
                'string',
            ],

            'estimated_duration_minutes' => [
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
