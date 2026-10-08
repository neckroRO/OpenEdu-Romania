<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewCurriculumProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->canModerate() === true;
    }

    public function rules(): array
    {
        return [
            'note' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
