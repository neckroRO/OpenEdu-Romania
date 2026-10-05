<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'concept_id' => [
                'sometimes',
                'integer',
                'exists:concepts,id',
            ],

            'type' => [
                'sometimes',
                'string',
                'max:64',
            ],

            'title' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'summary' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'content' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'source_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],

            'language_code' => [
                'sometimes',
                'string',
                'size:2',
            ],

            'difficulty_level' => [
                'sometimes',
                'integer',
                'min:1',
                'max:10',
            ],

            'complexity_level' => [
                'sometimes',
                'integer',
                'min:1',
                'max:10',
            ],
        ];
    }
}
