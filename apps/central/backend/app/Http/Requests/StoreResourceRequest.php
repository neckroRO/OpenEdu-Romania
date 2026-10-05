<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'concept_id' => [
                'required',
                'integer',
                'exists:concepts,id',
            ],

            'type' => [
                'required',
                'string',
                'max:64',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'summary' => [
                'nullable',
                'string',
            ],

            'content' => [
                'nullable',
                'string',
            ],

            'source_url' => [
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
