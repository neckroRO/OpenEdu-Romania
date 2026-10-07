<?php

namespace App\Services\Curriculum;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CurriculumImportValidator
{
    /**
     * @throws ValidationException
     */
    public function validate(array $payload): array
    {
        $validator = Validator::make(
            $payload,
            $this->rules()
        );

        $validator->after(function (ValidatorContract $validator) use ($payload) {
            $this->validateSemantics($validator, $payload);
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function rules(): array
    {
        return [
            'schema_version' => [
                'required',
                'string',
                'in:1.0',
            ],

            'curriculum' => [
                'required',
                'array',
            ],
            'curriculum.code' => [
                'required',
                'string',
                'max:64',
            ],
            'curriculum.name' => [
                'required',
                'string',
                'max:255',
            ],
            'curriculum.country_code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
            ],
            'curriculum.status' => [
                'required',
                'string',
                'in:active,inactive',
            ],

            'curriculum_version' => [
                'required',
                'array',
            ],
            'curriculum_version.version' => [
                'required',
                'string',
                'max:64',
            ],
            'curriculum_version.valid_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'curriculum_version.valid_until' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'curriculum_version.status' => [
                'required',
                'string',
                'in:active,inactive',
            ],

            'education_level' => [
                'required',
                'array',
            ],
            'education_level.code' => [
                'required',
                'string',
                'max:64',
            ],
            'education_level.name' => [
                'required',
                'string',
                'max:255',
            ],
            'education_level.ordinal' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],
            'education_level.education_stage' => [
                'required',
                'string',
                'max:64',
            ],

            'subject' => [
                'required',
                'array',
            ],
            'subject.code' => [
                'required',
                'string',
                'max:64',
            ],
            'subject.name' => [
                'required',
                'string',
                'max:255',
            ],
            'subject.description' => [
                'nullable',
                'string',
            ],
            'subject.status' => [
                'required',
                'string',
                'in:active,inactive',
            ],

            'curriculum_subject' => [
                'required',
                'array',
            ],
            'curriculum_subject.display_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],
            'curriculum_subject.status' => [
                'required',
                'string',
                'in:active,inactive',
            ],
            'curriculum_subject.program' => [
                'required',
                'array',
            ],
            'curriculum_subject.program.reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'curriculum_subject.program.source_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
            'curriculum_subject.program.approved_at' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'domains' => [
                'required',
                'array',
            ],
            'domains.*' => [
                'required',
                'array',
            ],
            'domains.*.key' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._-]+$/',
            ],
            'domains.*.parent_key' => [
                'nullable',
                'string',
                'max:64',
            ],
            'domains.*.title' => [
                'required',
                'string',
                'max:255',
            ],
            'domains.*.description' => [
                'nullable',
                'string',
            ],
            'domains.*.display_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'concepts' => [
                'required',
                'array',
            ],
            'concepts.*' => [
                'required',
                'array',
            ],
            'concepts.*.code' => [
                'required',
                'string',
                'max:64',
            ],
            'concepts.*.title' => [
                'required',
                'string',
                'max:255',
            ],
            'concepts.*.description' => [
                'nullable',
                'string',
            ],
            'concepts.*.status' => [
                'required',
                'string',
                'in:active,inactive',
            ],
            'concepts.*.placements' => [
                'required',
                'array',
            ],
            'concepts.*.placements.*.domain_key' => [
                'required',
                'string',
                'max:64',
            ],
            'concepts.*.placements.*.display_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],
            'concepts.*.placements.*.is_core' => [
                'required',
                'boolean',
            ],

            'competencies' => [
                'required',
                'array',
            ],
            'competencies.*' => [
                'required',
                'array',
            ],
            'competencies.*.code' => [
                'required',
                'string',
                'max:64',
            ],
            'competencies.*.type' => [
                'required',
                'string',
                'max:32',
            ],
            'competencies.*.parent_code' => [
                'nullable',
                'string',
                'max:64',
            ],
            'competencies.*.title' => [
                'required',
                'string',
                'max:255',
            ],
            'competencies.*.description' => [
                'nullable',
                'string',
            ],
            'competencies.*.display_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],
            'competencies.*.status' => [
                'required',
                'string',
                'in:active,inactive',
            ],
            'competencies.*.concepts' => [
                'present',
                'array',
            ],
            'competencies.*.concepts.*.code' => [
                'required',
                'string',
                'max:64',
            ],
            'competencies.*.concepts.*.display_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],
            'competencies.*.concepts.*.is_core' => [
                'required',
                'boolean',
            ],
        ];
    }

    private function validateSemantics(
        ValidatorContract $validator,
        array $payload
    ): void {
        $domains = is_array($payload['domains'] ?? null)
            ? $payload['domains']
            : [];

        $concepts = is_array($payload['concepts'] ?? null)
            ? $payload['concepts']
            : [];

        $competencies = is_array($payload['competencies'] ?? null)
            ? $payload['competencies']
            : [];

        $domainIndexes = $this->validateUniqueIdentifiers(
            $validator,
            $domains,
            'key',
            'domains'
        );

        $conceptIndexes = $this->validateUniqueIdentifiers(
            $validator,
            $concepts,
            'code',
            'concepts'
        );

        $competencyIndexes = $this->validateUniqueIdentifiers(
            $validator,
            $competencies,
            'code',
            'competencies'
        );

        $this->validateHierarchy(
            $validator,
            $domains,
            $domainIndexes,
            'key',
            'parent_key',
            'domains'
        );

        $this->validateConceptPlacements(
            $validator,
            $concepts,
            $domainIndexes
        );

        $this->validateHierarchy(
            $validator,
            $competencies,
            $competencyIndexes,
            'code',
            'parent_code',
            'competencies'
        );

        $this->validateCompetencyConcepts(
            $validator,
            $competencies,
            $conceptIndexes
        );
    }

    private function validateUniqueIdentifiers(
        ValidatorContract $validator,
        array $items,
        string $identifierField,
        string $path
    ): array {
        $indexes = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $identifier = $item[$identifierField] ?? null;

            if (!is_string($identifier) || $identifier === '') {
                continue;
            }

            if (array_key_exists($identifier, $indexes)) {
                $validator->errors()->add(
                    "{$path}.{$index}.{$identifierField}",
                    "Duplicate {$identifierField} '{$identifier}'."
                );

                continue;
            }

            $indexes[$identifier] = $index;
        }

        return $indexes;
    }

    private function validateHierarchy(
        ValidatorContract $validator,
        array $items,
        array $indexes,
        string $identifierField,
        string $parentField,
        string $path
    ): void {
        $parents = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $identifier = $item[$identifierField] ?? null;
            $parent = $item[$parentField] ?? null;

            if (!is_string($identifier) || $identifier === '') {
                continue;
            }

            if ($parent !== null && !is_string($parent)) {
                continue;
            }

            $parents[$identifier] = $parent;

            if ($parent === null) {
                continue;
            }

            if ($parent === $identifier) {
                $validator->errors()->add(
                    "{$path}.{$index}.{$parentField}",
                    'An item cannot be its own parent.'
                );

                continue;
            }

            if (!array_key_exists($parent, $indexes)) {
                $validator->errors()->add(
                    "{$path}.{$index}.{$parentField}",
                    "Parent '{$parent}' does not exist."
                );
            }
        }

        foreach ($parents as $identifier => $parent) {
            $visited = [];
            $current = $identifier;

            while (
                array_key_exists($current, $parents)
                && $parents[$current] !== null
            ) {
                if (isset($visited[$current])) {
                    $index = $indexes[$identifier] ?? null;

                    if ($index !== null) {
                        $validator->errors()->add(
                            "{$path}.{$index}.{$parentField}",
                            'Hierarchy contains a cycle.'
                        );
                    }

                    break;
                }

                $visited[$current] = true;

                $next = $parents[$current];

                if (
                    !is_string($next)
                    || !array_key_exists($next, $parents)
                ) {
                    break;
                }

                $current = $next;
            }
        }
    }

    private function validateConceptPlacements(
        ValidatorContract $validator,
        array $concepts,
        array $domainIndexes
    ): void {
        foreach ($concepts as $conceptIndex => $concept) {
            if (!is_array($concept)) {
                continue;
            }

            $placements = $concept['placements'] ?? [];

            if (!is_array($placements)) {
                continue;
            }

            $seenDomains = [];

            foreach ($placements as $placementIndex => $placement) {
                if (!is_array($placement)) {
                    continue;
                }

                $domainKey = $placement['domain_key'] ?? null;

                if (!is_string($domainKey) || $domainKey === '') {
                    continue;
                }

                if (!array_key_exists($domainKey, $domainIndexes)) {
                    $validator->errors()->add(
                        "concepts.{$conceptIndex}.placements."
                        ."{$placementIndex}.domain_key",
                        "Domain '{$domainKey}' does not exist."
                    );
                }

                if (isset($seenDomains[$domainKey])) {
                    $validator->errors()->add(
                        "concepts.{$conceptIndex}.placements."
                        ."{$placementIndex}.domain_key",
                        "Duplicate placement for domain '{$domainKey}'."
                    );
                }

                $seenDomains[$domainKey] = true;
            }
        }
    }

    private function validateCompetencyConcepts(
        ValidatorContract $validator,
        array $competencies,
        array $conceptIndexes
    ): void {
        foreach ($competencies as $competencyIndex => $competency) {
            if (!is_array($competency)) {
                continue;
            }

            $concepts = $competency['concepts'] ?? [];

            if (!is_array($concepts)) {
                continue;
            }

            $seenConcepts = [];

            foreach ($concepts as $conceptIndex => $concept) {
                if (!is_array($concept)) {
                    continue;
                }

                $code = $concept['code'] ?? null;

                if (!is_string($code) || $code === '') {
                    continue;
                }

                if (!array_key_exists($code, $conceptIndexes)) {
                    $validator->errors()->add(
                        "competencies.{$competencyIndex}.concepts."
                        ."{$conceptIndex}.code",
                        "Concept '{$code}' does not exist."
                    );
                }

                if (isset($seenConcepts[$code])) {
                    $validator->errors()->add(
                        "competencies.{$competencyIndex}.concepts."
                        ."{$conceptIndex}.code",
                        "Duplicate concept link '{$code}'."
                    );
                }

                $seenConcepts[$code] = true;
            }
        }
    }
}
