<?php

namespace App\Http\Requests;

use App\Enums\LessonResourceRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonVersionResourcesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resources' => [
                'present',
                'array',
                'max:100',
            ],

            'resources.*.resource_id' => [
                'required',
                'integer',
                'distinct',
                'exists:resources,id',
            ],

            'resources.*.role' => [
                'required',
                Rule::enum(LessonResourceRole::class),
            ],

            'resources.*.display_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'resources.*.is_required' => [
                'required',
                'boolean',
            ],
        ];
    }
}
