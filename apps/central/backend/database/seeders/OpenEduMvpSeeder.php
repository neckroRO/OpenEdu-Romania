<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\CompetencyConcept;
use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonCompetency;
use App\Models\Resource;
use App\Models\ResourceConcept;
use App\Models\ResourceVersion;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class OpenEduMvpSeeder extends Seeder
{
    public function run(): void
    {
        $curriculum = Curriculum::updateOrCreate(
            ['code' => 'RO-NATIONAL'],
            [
                'name' => 'Curriculum național România',
                'country_code' => 'RO',
                'status' => 'active',
            ]
        );

        $curriculumVersion = CurriculumVersion::updateOrCreate(
            [
                'curriculum_id' => $curriculum->id,
                'version' => 'MVP-2026',
            ],
            [
                'valid_from' => '2026-01-01',
                'valid_until' => null,
                'status' => 'active',
            ]
        );

        $educationLevel = EducationLevel::updateOrCreate(
            ['code' => 'grade-7'],
            [
                'name' => 'Clasa a VII-a',
                'ordinal' => 7,
                'education_stage' => 'gimnazial',
            ]
        );

        $subject = Subject::updateOrCreate(
            ['code' => 'mathematics'],
            [
                'name' => 'Matematică',
                'description' => 'Matematică pentru învățământul gimnazial.',
                'status' => 'active',
            ]
        );

        $curriculumSubject = CurriculumSubject::updateOrCreate(
            [
                'curriculum_version_id' => $curriculumVersion->id,
                'education_level_id' => $educationLevel->id,
                'subject_id' => $subject->id,
            ],
            [
                'display_order' => 1,
                'status' => 'active',
                'program_reference' => 'OMEN nr. 3393/28.02.2017',
                'program_source_url' =>
                    'https://www.edu.ro/Ordin_ministru_3393_2017',
                'program_approved_at' => '2017-02-28',
            ]
        );

        $domain = Domain::updateOrCreate(
            [
                'curriculum_subject_id' => $curriculumSubject->id,
                'title' => 'Algebră',
            ],
            [
                'parent_domain_id' => null,
                'description' => 'Concepte fundamentale de algebră.',
                'display_order' => 1,
            ]
        );

        $concept = Concept::updateOrCreate(
            ['code' => 'linear-equations'],
            [
                'title' => 'Ecuații de gradul I',
                'description' =>
                    'Introducere în ecuațiile de gradul I cu o necunoscută.',
                'status' => 'active',
            ]
        );

        ConceptPlacement::updateOrCreate(
            [
                'concept_id' => $concept->id,
                'domain_id' => $domain->id,
            ],
            [
                'display_order' => 1,
                'is_core' => true,
            ]
        );

        $generalCompetencies = [
            '1' => 'Identificarea datelor și relațiilor matematice',
            '2' => 'Prelucrarea datelor matematice',
            '3' => 'Utilizarea conceptelor și algoritmilor matematici',
            '4' => 'Exprimarea demersurilor de rezolvare',
            '5' => 'Analizarea situațiilor matematice',
            '6' => 'Modelarea matematică a situațiilor date',
        ];

        $generalModels = [];

        foreach ($generalCompetencies as $code => $title) {
            $generalModels[$code] = Competency::updateOrCreate(
                [
                    'curriculum_subject_id' => $curriculumSubject->id,
                    'code' => $code,
                ],
                [
                    'parent_competency_id' => null,
                    'type' => 'general',
                    'title' => $title,
                    'description' => null,
                    'display_order' => (int) $code,
                    'status' => 'active',
                ]
            );
        }

        $specificCompetencies = [
            '1.2' => [
                'parent' => '1',
                'title' =>
                    'Identificarea situațiilor rezolvabile prin ecuații sau sisteme liniare',
            ],
            '2.2' => [
                'parent' => '2',
                'title' =>
                    'Verificarea soluțiilor ecuațiilor și sistemelor liniare',
            ],
            '3.2' => [
                'parent' => '3',
                'title' =>
                    'Utilizarea transformărilor echivalente în rezolvarea ecuațiilor',
            ],
            '4.2' => [
                'parent' => '4',
                'title' =>
                    'Redactarea rezolvării ecuațiilor și sistemelor liniare',
            ],
            '5.2' => [
                'parent' => '5',
                'title' =>
                    'Stabilirea metodelor de rezolvare pentru ecuații și sisteme',
            ],
            '6.2' => [
                'parent' => '6',
                'title' =>
                    'Modelarea situațiilor folosind ecuații și sisteme liniare',
            ],
        ];

        $specificModels = [];

        foreach ($specificCompetencies as $code => $data) {
            $specific = Competency::updateOrCreate(
                [
                    'curriculum_subject_id' => $curriculumSubject->id,
                    'code' => $code,
                ],
                [
                    'parent_competency_id' =>
                        $generalModels[$data['parent']]->id,
                    'type' => 'specific',
                    'title' => $data['title'],
                    'description' => null,
                    'display_order' => 2,
                    'status' => 'active',
                ]
            );

            $specificModels[$code] = $specific;

            CompetencyConcept::updateOrCreate(
                [
                    'competency_id' => $specific->id,
                    'concept_id' => $concept->id,
                ],
                [
                    'display_order' => 1,
                    'is_core' => true,
                ]
            );
        }

        $lesson = Lesson::updateOrCreate(
            [
                'curriculum_subject_id' => $curriculumSubject->id,
                'code' => 'linear-equations-basics',
            ],
            [
                'title' => 'Ecuații de gradul I',
                'description' =>
                    'Introducere în rezolvarea ecuațiilor de gradul I.',
                'display_order' => 1,
                'status' => 'active',
            ]
        );

        foreach (
            array_keys($specificCompetencies) as $index => $code
        ) {
            LessonCompetency::updateOrCreate(
                [
                    'lesson_id' => $lesson->id,
                    'competency_id' => $specificModels[$code]->id,
                ],
                [
                    'display_order' => $index + 1,
                    'is_core' => true,
                ]
            );
        }

        $resource = Resource::updateOrCreate(
            ['code' => 'linear-equations-introduction'],
            [
                'type' => 'explanation',
                'status' => 'active',
            ]
        );

        ResourceVersion::updateOrCreate(
            [
                'resource_id' => $resource->id,
                'version_number' => 1,
            ],
            [
                'title' => 'Ecuațiile de gradul I explicate simplu',
                'summary' =>
                    'O introducere intuitivă în ecuațiile de gradul I.',
                'content' => <<<'TEXT'
O ecuație poate fi privită ca o balanță.

Cele două părți ale ecuației trebuie să rămână egale. Dacă efectuăm o operație într-o parte, trebuie să efectuăm aceeași operație și în cealaltă parte.

Exemplu:

x + 3 = 8

Scădem 3 din ambele părți:

x + 3 - 3 = 8 - 3

Rezultă:

x = 5

Putem verifica înlocuind x cu 5:

5 + 3 = 8

Afirmația este adevărată, deci soluția este corectă.
TEXT,
                'source_url' => null,
                'language_code' => 'ro',
                'difficulty_level' => 1,
                'complexity_level' => 1,
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        ResourceConcept::updateOrCreate(
            [
                'resource_id' => $resource->id,
                'concept_id' => $concept->id,
            ],
            [
                'is_primary' => true,
                'display_order' => 1,
            ]
        );
    }
}
