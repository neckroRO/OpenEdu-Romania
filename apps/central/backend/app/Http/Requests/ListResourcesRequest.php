<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListResourcesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if (is_string($this->input('q'))) {
            $data['q'] = trim($this->input('q'));
        }

        if (is_string($this->input('direction'))) {
            $data['direction'] = strtolower($this->input('direction'));
        }

        if (is_string($this->input('sort'))) {
            $data['sort'] = strtolower($this->input('sort'));
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'q' => [
                'nullable',
                'string',
                'max:255',
            ],

            'type' => [
                'nullable',
                'string',
                'max:64',
            ],

            'language_code' => [
                'nullable',
                'string',
                'size:2',
            ],

            'difficulty_level' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],

            'complexity_level' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],

            'concept_id' => [
                'nullable',
                'integer',
                'exists:concepts,id',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],

            'sort' => [
                'sometimes',
                Rule::in([
                    'published_at',
                    'title',
                    'difficulty_level',
                ]),
            ],

            'direction' => [
                'sometimes',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ];
    }
}
