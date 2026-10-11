<?php

namespace App\Http\Requests;

use App\Enums\CurriculumProposalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
                Rule::in([
                    'subject',
                    'domain',
                    'concept',
                ]),
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

            'payload.curriculum_subject_id' => [
                'sometimes',
                'integer',
                'min:1',
                'exists:curriculum_subjects,id',
            ],

            'payload.parent_domain_id' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'exists:domains,id',
            ],

            'payload.domain_id' => [
                'sometimes',
                'integer',
                'min:1',
                'exists:domains,id',
            ],

            'payload.title' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'payload.description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'payload.display_order' => [
                'sometimes',
                'integer',
                'min:0',
                'max:65535',
            ],

            'payload.code' => [
                'sometimes',
                'string',
                'max:128',
            ],

            'payload.alias' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $entityType = $this->input('entity_type');
                $proposalType = $this->input('proposal_type');
                $payload = $this->input('payload', []);

                if (! is_array($payload)) {
                    return;
                }

                match ($entityType) {
                    'domain' => $this->validateDomainProposal(
                        $validator,
                        $proposalType,
                        $payload
                    ),
                    'concept' => $this->validateConceptProposal(
                        $validator,
                        $proposalType,
                        $payload
                    ),
                    default => null,
                };
            },
        ];
    }

    private function validateDomainProposal(
        Validator $validator,
        mixed $proposalType,
        array $payload
    ): void {
        if (
            ! in_array(
                $proposalType,
                [
                    CurriculumProposalType::Create->value,
                    CurriculumProposalType::Update->value,
                ],
                true
            )
        ) {
            $validator->errors()->add(
                'proposal_type',
                'Domain proposals currently support only create and update.'
            );

            return;
        }

        $allowed = [
            'curriculum_subject_id',
            'parent_domain_id',
            'title',
            'description',
            'display_order',
        ];

        $this->rejectUnknownPayloadKeys(
            $validator,
            $payload,
            $allowed
        );

        if ($proposalType === CurriculumProposalType::Create->value) {
            foreach (
                [
                    'curriculum_subject_id',
                    'title',
                ] as $requiredKey
            ) {
                if (
                    ! array_key_exists($requiredKey, $payload)
                    || $payload[$requiredKey] === null
                    || $payload[$requiredKey] === ''
                ) {
                    $validator->errors()->add(
                        "payload.{$requiredKey}",
                        'This field is required for domain creation.'
                    );
                }
            }
        }

        $this->validateEntityExists(
            $validator,
            'domains',
            'domain'
        );

        $this->validateDomainParentScope(
            $validator,
            $proposalType,
            $payload
        );
    }

    private function validateDomainParentScope(
        Validator $validator,
        mixed $proposalType,
        array $payload
    ): void {
        if (
            ! array_key_exists(
                'parent_domain_id',
                $payload
            )
            || $payload['parent_domain_id'] === null
        ) {
            return;
        }

        $parentDomainId =
            (int) $payload['parent_domain_id'];

        $curriculumSubjectId = null;

        if (
            $proposalType
            === CurriculumProposalType::Create->value
        ) {
            if (
                ! array_key_exists(
                    'curriculum_subject_id',
                    $payload
                )
            ) {
                return;
            }

            $curriculumSubjectId =
                (int) $payload[
                    'curriculum_subject_id'
                ];
        } elseif (
            $proposalType
            === CurriculumProposalType::Update->value
        ) {
            $entityId = $this->input('entity_id');

            if (
                ! is_int($entityId)
                && ! ctype_digit(
                    (string) $entityId
                )
            ) {
                return;
            }

            $curriculumSubjectId =
                \DB::table('domains')
                    ->where(
                        'id',
                        (int) $entityId
                    )
                    ->value(
                        'curriculum_subject_id'
                    );

            if ($curriculumSubjectId === null) {
                return;
            }

            $curriculumSubjectId =
                (int) $curriculumSubjectId;
        }

        if ($curriculumSubjectId === null) {
            return;
        }

        $parentMatchesScope =
            \DB::table('domains')
                ->where('id', $parentDomainId)
                ->where(
                    'curriculum_subject_id',
                    $curriculumSubjectId
                )
                ->exists();

        if (! $parentMatchesScope) {
            $validator->errors()->add(
                'payload.parent_domain_id',
                'The parent domain must belong to the same curriculum subject.'
            );
        }
    }

    private function validateConceptProposal(
        Validator $validator,
        mixed $proposalType,
        array $payload
    ): void {
        if (
            ! in_array(
                $proposalType,
                [
                    CurriculumProposalType::Create->value,
                    CurriculumProposalType::Update->value,
                    CurriculumProposalType::Alias->value,
                    CurriculumProposalType::MergeCandidate->value,
                ],
                true
            )
        ) {
            $validator->errors()->add(
                'proposal_type',
                'Concept proposals support create, update, alias and merge candidate.'
            );

            return;
        }

        $allowed = match ($proposalType) {
            CurriculumProposalType::Alias->value => [
                'alias',
            ],
            CurriculumProposalType::MergeCandidate->value => [
                'duplicate_entity_id',
            ],
            CurriculumProposalType::Create->value => [
                'code',
                'title',
                'description',
                'domain_id',
            ],
            CurriculumProposalType::Update->value => [
                'code',
                'title',
                'description',
            ],
            default => [],
        };

        $this->rejectUnknownPayloadKeys(
            $validator,
            $payload,
            $allowed
        );

        if ($proposalType === CurriculumProposalType::Create->value) {
            foreach (['code', 'title', 'domain_id'] as $requiredKey) {
                if (
                    ! array_key_exists($requiredKey, $payload)
                    || $payload[$requiredKey] === null
                    || $payload[$requiredKey] === ''
                ) {
                    $validator->errors()->add(
                        "payload.{$requiredKey}",
                        'This field is required for concept creation.'
                    );
                }
            }
        }

        if (
            $proposalType === CurriculumProposalType::Alias->value
            && (
                ! array_key_exists('alias', $payload)
                || trim((string) $payload['alias']) === ''
            )
        ) {
            $validator->errors()->add(
                'payload.alias',
                'Alias is required for concept alias proposals.'
            );
        }

        $this->validateEntityExists(
            $validator,
            'concepts',
            'concept'
        );
    }

    private function rejectUnknownPayloadKeys(
        Validator $validator,
        array $payload,
        array $allowed
    ): void {
        $unknown = array_diff(
            array_keys($payload),
            $allowed
        );

        foreach ($unknown as $key) {
            $validator->errors()->add(
                "payload.{$key}",
                'This field is not allowed for this proposal.'
            );
        }
    }

    private function validateEntityExists(
        Validator $validator,
        string $table,
        string $entityType
    ): void {
        if (
            $this->input('proposal_type')
            === CurriculumProposalType::Create->value
        ) {
            return;
        }

        $entityId = $this->input('entity_id');

        if (! is_int($entityId) && ! ctype_digit((string) $entityId)) {
            return;
        }

        $exists = \DB::table($table)
            ->where('id', (int) $entityId)
            ->exists();

        if (! $exists) {
            $validator->errors()->add(
                'entity_id',
                "The selected {$entityType} does not exist."
            );
        }
    }
}
