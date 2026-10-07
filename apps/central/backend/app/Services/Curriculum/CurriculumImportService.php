<?php

namespace App\Services\Curriculum;

use App\Models\Competency;
use App\Models\CompetencyConcept;
use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Throwable;

class CurriculumImportService
{
    public function __construct(
        private readonly CurriculumImportValidator $validator
    ) {
    }

    public function import(array $payload): array
    {
        $data = $this->validator->validate($payload);

        return DB::transaction(function () use ($data): array {
            $curriculum = $this->importCurriculum($data);
            $version = $this->importCurriculumVersion(
                $data,
                $curriculum
            );
            $level = $this->importEducationLevel($data);
            $subject = $this->importSubject($data);

            $curriculumSubject = $this->importCurriculumSubject(
                $data,
                $version,
                $level,
                $subject
            );

            $domains = $this->importDomains(
                $data,
                $curriculumSubject
            );

            $concepts = $this->importConcepts(
                $data,
                $domains
            );

            $competencies = $this->importCompetencies(
                $data,
                $curriculumSubject,
                $concepts
            );

            return [
                'curriculum_id' => $curriculum->id,
                'curriculum_version_id' => $version->id,
                'education_level_id' => $level->id,
                'subject_id' => $subject->id,
                'curriculum_subject_id' => $curriculumSubject->id,
                'domains' => count($domains),
                'concepts' => count($concepts),
                'competencies' => count($competencies),
            ];
        });
    }

    public function dryRun(array $payload): array
    {
        DB::beginTransaction();

        try {
            $result = $this->import($payload);

            DB::rollBack();

            return $result;
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    private function importCurriculum(array $data): Curriculum
    {
        $item = $data['curriculum'];

        return Curriculum::updateOrCreate(
            [
                'code' => $item['code'],
            ],
            [
                'name' => $item['name'],
                'country_code' => $item['country_code'],
                'status' => $item['status'],
            ]
        );
    }

    private function importCurriculumVersion(
        array $data,
        Curriculum $curriculum
    ): CurriculumVersion {
        $item = $data['curriculum_version'];

        return CurriculumVersion::updateOrCreate(
            [
                'curriculum_id' => $curriculum->id,
                'version' => $item['version'],
            ],
            [
                'valid_from' => $item['valid_from'] ?? null,
                'valid_until' => $item['valid_until'] ?? null,
                'status' => $item['status'],
            ]
        );
    }

    private function importEducationLevel(
        array $data
    ): EducationLevel {
        $item = $data['education_level'];

        return EducationLevel::updateOrCreate(
            [
                'code' => $item['code'],
            ],
            [
                'name' => $item['name'],
                'ordinal' => $item['ordinal'],
                'education_stage' => $item['education_stage'],
            ]
        );
    }

    private function importSubject(array $data): Subject
    {
        $item = $data['subject'];

        return Subject::updateOrCreate(
            [
                'code' => $item['code'],
            ],
            [
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'status' => $item['status'],
            ]
        );
    }

    private function importCurriculumSubject(
        array $data,
        CurriculumVersion $version,
        EducationLevel $level,
        Subject $subject
    ): CurriculumSubject {
        $item = $data['curriculum_subject'];
        $program = $item['program'];

        return CurriculumSubject::updateOrCreate(
            [
                'curriculum_version_id' => $version->id,
                'education_level_id' => $level->id,
                'subject_id' => $subject->id,
            ],
            [
                'display_order' => $item['display_order'],
                'status' => $item['status'],
                'program_reference' =>
                    $program['reference'] ?? null,
                'program_source_url' =>
                    $program['source_url'] ?? null,
                'program_approved_at' =>
                    $program['approved_at'] ?? null,
            ]
        );
    }

    private function importDomains(
        array $data,
        CurriculumSubject $curriculumSubject
    ): array {
        $models = [];

        foreach ($data['domains'] as $item) {
            $models[$item['key']] = Domain::updateOrCreate(
                [
                    'curriculum_subject_id' =>
                        $curriculumSubject->id,
                    'title' => $item['title'],
                ],
                [
                    'description' =>
                        $item['description'] ?? null,
                    'display_order' =>
                        $item['display_order'],
                ]
            );
        }

        foreach ($data['domains'] as $item) {
            $domain = $models[$item['key']];
            $parentKey = $item['parent_key'] ?? null;

            $parentId = $parentKey !== null
                ? $models[$parentKey]->id
                : null;

            if ($domain->parent_domain_id !== $parentId) {
                $domain->parent_domain_id = $parentId;
                $domain->save();
            }
        }

        return $models;
    }

    private function importConcepts(
        array $data,
        array $domains
    ): array {
        $models = [];

        foreach ($data['concepts'] as $item) {
            $concept = Concept::updateOrCreate(
                [
                    'code' => $item['code'],
                ],
                [
                    'title' => $item['title'],
                    'description' =>
                        $item['description'] ?? null,
                    'status' => $item['status'],
                ]
            );

            $models[$item['code']] = $concept;

            foreach ($item['placements'] as $placement) {
                $domain = $domains[$placement['domain_key']];

                ConceptPlacement::updateOrCreate(
                    [
                        'concept_id' => $concept->id,
                        'domain_id' => $domain->id,
                    ],
                    [
                        'display_order' =>
                            $placement['display_order'],
                        'is_core' => $placement['is_core'],
                    ]
                );
            }
        }

        return $models;
    }

    private function importCompetencies(
        array $data,
        CurriculumSubject $curriculumSubject,
        array $concepts
    ): array {
        $models = [];

        foreach ($data['competencies'] as $item) {
            $models[$item['code']] = Competency::updateOrCreate(
                [
                    'curriculum_subject_id' =>
                        $curriculumSubject->id,
                    'code' => $item['code'],
                ],
                [
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'description' =>
                        $item['description'] ?? null,
                    'display_order' =>
                        $item['display_order'],
                    'status' => $item['status'],
                ]
            );
        }

        foreach ($data['competencies'] as $item) {
            $competency = $models[$item['code']];
            $parentCode = $item['parent_code'] ?? null;

            $parentId = $parentCode !== null
                ? $models[$parentCode]->id
                : null;

            if ($competency->parent_competency_id !== $parentId) {
                $competency->parent_competency_id = $parentId;
                $competency->save();
            }

            foreach ($item['concepts'] as $link) {
                $concept = $concepts[$link['code']];

                CompetencyConcept::updateOrCreate(
                    [
                        'competency_id' => $competency->id,
                        'concept_id' => $concept->id,
                    ],
                    [
                        'display_order' =>
                            $link['display_order'],
                        'is_core' => $link['is_core'],
                    ]
                );
            }
        }

        return $models;
    }
}
