<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonConceptsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'concepts' => [
                'present',
                'array',
                'max:100',
            ],

            'concepts.*.concept_id' => [
                'required',
                'integer',
                'distinct',
                'exists:concepts,id',
            ],

            'concepts.*.display_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'concepts.*.is_core' => [
                'required',
                'boolean',
            ],
        ];
    }
}
