<?php

namespace Database\Seeders;

use App\Models\Concept;
use App\Models\ConceptPlacement;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\Domain;
use App\Models\EducationLevel;
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
                'description' => 'Introducere în ecuațiile de gradul I cu o necunoscută.',
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
                'summary' => 'O introducere intuitivă în ecuațiile de gradul I.',
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
