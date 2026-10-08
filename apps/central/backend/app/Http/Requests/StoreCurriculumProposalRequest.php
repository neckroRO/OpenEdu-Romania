<?php

namespace App\Http\Requests;

use App\Enums\CurriculumProposalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->canContribute() === true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => [
                'required',
                Rule::in(['subject']),
            ],

            'entity_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::prohibitedIf(
                    fn (): bool =>
                        $this->input('proposal_type')
                        === CurriculumProposalType::Create->value
                ),
                Rule::requiredIf(
                    fn (): bool =>
                        in_array(
                            $this->input('proposal_type'),
                            [
                                CurriculumProposalType::Update->value,
                                CurriculumProposalType::Alias->value,
                                CurriculumProposalType::MergeCandidate->value,
                            ],
                            true
                        )
                ),
            ],

            'proposal_type' => [
                'required',
                Rule::in([
                    CurriculumProposalType::Create->value,
                    CurriculumProposalType::Update->value,
                    CurriculumProposalType::Alias->value,
                    CurriculumProposalType::MergeCandidate->value,
                ]),
            ],

            'payload' => [
                'required',
                'array',
                'min:1',
            ],

            'payload.duplicate_entity_id' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->input('proposal_type')
                        === CurriculumProposalType::MergeCandidate->value
                ),
                'integer',
                'min:1',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
