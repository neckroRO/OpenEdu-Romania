<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\CurriculumArea;
use App\Models\CurriculumFrameworkVariant;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RomanianOfficialCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $curriculum = Curriculum::updateOrCreate(
                ['code' => 'RO-NATIONAL'],
                [
                    'name' => 'Curriculum național România',
                    'country_code' => 'RO',
                    'status' => 'active',
                ]
            );

            $areas = $this->seedCurriculumAreas();
            $levels = $this->seedEducationLevels();
            $subjects = $this->seedSubjects();

            $primaryVersion = CurriculumVersion::updateOrCreate(
                [
                    'curriculum_id' => $curriculum->id,
                    'version' => 'RO-PRIMARY-OM3371-2013',
                ],
                [
                    'valid_from' => '2013-09-01',
                    'valid_until' => null,
                    'status' => 'active',
                ]
            );

            $primaryVariant = CurriculumFrameworkVariant::updateOrCreate(
                [
                    'curriculum_version_id' => $primaryVersion->id,
                    'code' => 'standard-ro',
                ],
                [
                    'name' => 'Învățământ primar standard cu predare în limba română',
                    'source_reference' => 'OMEN nr. 3371/12.03.2013',
                    'source_url' => 'https://legislatie.just.ro/Public/DetaliiDocument/146699',
                    'source_annex' => 'Anexa 1',
                    'approved_at' => '2013-03-12',
                    'status' => 'active',
                ]
            );

            $lowerSecondaryVersion = CurriculumVersion::updateOrCreate(
                [
                    'curriculum_id' => $curriculum->id,
                    'version' => 'RO-GYMNASIUM-OM3590-2016',
                ],
                [
                    'valid_from' => '2017-09-01',
                    'valid_until' => null,
                    'status' => 'active',
                ]
            );

            $lowerSecondaryVariant = CurriculumFrameworkVariant::updateOrCreate(
                [
                    'curriculum_version_id' => $lowerSecondaryVersion->id,
                    'code' => 'standard-ro',
                ],
                [
                    'name' => 'Învățământ gimnazial standard cu predare în limba română',
                    'source_reference' => 'OMENCS nr. 3590/05.04.2016',
                    'source_url' => 'https://legislatie.just.ro/Public/DetaliiDocument/179198',
                    'source_annex' => 'Anexa 2',
                    'approved_at' => '2016-04-05',
                    'status' => 'active',
                ]
            );

            $this->seedPrimaryFramework(
                $primaryVersion,
                $primaryVariant,
                $areas,
                $levels,
                $subjects
            );

            $this->seedLowerSecondaryFramework(
                $lowerSecondaryVersion,
                $lowerSecondaryVariant,
                $areas,
                $levels,
                $subjects
            );
        });
    }

    private function seedCurriculumAreas(): array
    {
        $definitions = [
            'language-communication' => [
                'name' => 'Limbă și comunicare',
                'display_order' => 10,
            ],
            'mathematics-sciences' => [
                'name' => 'Matematică și științe ale naturii',
                'display_order' => 20,
            ],
            'people-society' => [
                'name' => 'Om și societate',
                'display_order' => 30,
            ],
            'arts' => [
                'name' => 'Arte',
                'display_order' => 40,
            ],
            'technologies' => [
                'name' => 'Tehnologii',
                'display_order' => 50,
            ],
            'physical-education-sport-health' => [
                'name' => 'Educație fizică, sport și sănătate',
                'display_order' => 60,
            ],
            'counselling-orientation' => [
                'name' => 'Consiliere și orientare',
                'display_order' => 70,
            ],
        ];

        $areas = [];

        foreach ($definitions as $code => $data) {
            $areas[$code] = CurriculumArea::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'display_order' => $data['display_order'],
                    'status' => 'active',
                ]
            );
        }

        return $areas;
    }

    private function seedEducationLevels(): array
    {
        $definitions = [
            'grade-p' => [
                'name' => 'Clasa pregătitoare',
                'ordinal' => 0,
                'education_stage' => 'primar',
            ],
            'grade-1' => [
                'name' => 'Clasa I',
                'ordinal' => 1,
                'education_stage' => 'primar',
            ],
            'grade-2' => [
                'name' => 'Clasa a II-a',
                'ordinal' => 2,
                'education_stage' => 'primar',
            ],
            'grade-3' => [
                'name' => 'Clasa a III-a',
                'ordinal' => 3,
                'education_stage' => 'primar',
            ],
            'grade-4' => [
                'name' => 'Clasa a IV-a',
                'ordinal' => 4,
                'education_stage' => 'primar',
            ],
            'grade-5' => [
                'name' => 'Clasa a V-a',
                'ordinal' => 5,
                'education_stage' => 'gimnazial',
            ],
            'grade-6' => [
                'name' => 'Clasa a VI-a',
                'ordinal' => 6,
                'education_stage' => 'gimnazial',
            ],
            'grade-7' => [
                'name' => 'Clasa a VII-a',
                'ordinal' => 7,
                'education_stage' => 'gimnazial',
            ],
            'grade-8' => [
                'name' => 'Clasa a VIII-a',
                'ordinal' => 8,
                'education_stage' => 'gimnazial',
            ],
        ];

        $levels = [];

        foreach ($definitions as $code => $data) {
            $levels[$code] = EducationLevel::updateOrCreate(
                ['code' => $code],
                $data
            );
        }

        return $levels;
    }

    private function seedSubjects(): array
    {
        $definitions = [
            'romanian-communication' => 'Comunicare în limba română',
            'romanian-language-literature' => 'Limba și literatura română',
            'modern-language' => 'Limbă modernă',
            'modern-language-1' => 'Limba modernă 1',
            'modern-language-2' => 'Limba modernă 2',

            'mathematics-environment-exploration' => 'Matematică și explorarea mediului',
            'mathematics' => 'Matematică',
            'natural-sciences' => 'Științe ale naturii',
            'physics' => 'Fizică',
            'chemistry' => 'Chimie',
            'biology' => 'Biologie',

            'history' => 'Istorie',
            'geography' => 'Geografie',
            'civic-education' => 'Educație civică',
            'social-education' => 'Educație socială',
            'religion' => 'Religie',

            'latin-language-culture-elements' =>
                'Elemente de limbă latină și de cultură romanică',

            'physical-education' => 'Educație fizică',
            'physical-education-sport' => 'Educație fizică și sport',
            'play-movement' => 'Joc și mișcare',

            'music-movement' => 'Muzică și mișcare',
            'visual-arts-practical-skills' =>
                'Arte vizuale și abilități practice',
            'visual-education' => 'Educație plastică',
            'music-education' => 'Educație muzicală',

            'technology-practical-applications' =>
                'Educație tehnologică și aplicații practice',
            'informatics-ict' => 'Informatică și TIC',

            'personal-development' => 'Dezvoltare personală',
            'counselling-personal-development' =>
                'Consiliere și dezvoltare personală',
        ];

        $subjects = [];

        foreach ($definitions as $code => $name) {
            $subjects[$code] = Subject::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => null,
                    'status' => 'active',
                ]
            );
        }

        return $subjects;
    }

    private function seedPrimaryFramework(
        CurriculumVersion $version,
        CurriculumFrameworkVariant $variant,
        array $areas,
        array $levels,
        array $subjects
    ): void {
        $rows = [
            ['grade-p', 'language-communication', 'romanian-communication', 5],
            ['grade-p', 'language-communication', 'modern-language', 1],
            ['grade-p', 'mathematics-sciences', 'mathematics-environment-exploration', 4],
            ['grade-p', 'people-society', 'religion', 1],
            ['grade-p', 'physical-education-sport-health', 'physical-education', 2],
            ['grade-p', 'arts', 'music-movement', 2],
            ['grade-p', 'arts', 'visual-arts-practical-skills', 2],
            ['grade-p', 'counselling-orientation', 'personal-development', 2],

            ['grade-1', 'language-communication', 'romanian-communication', 7],
            ['grade-1', 'language-communication', 'modern-language', 1],
            ['grade-1', 'mathematics-sciences', 'mathematics-environment-exploration', 4],
            ['grade-1', 'people-society', 'religion', 1],
            ['grade-1', 'physical-education-sport-health', 'physical-education', 2],
            ['grade-1', 'arts', 'music-movement', 2],
            ['grade-1', 'arts', 'visual-arts-practical-skills', 2],
            ['grade-1', 'counselling-orientation', 'personal-development', 1],

            ['grade-2', 'language-communication', 'romanian-communication', 6],
            ['grade-2', 'language-communication', 'modern-language', 1],
            ['grade-2', 'mathematics-sciences', 'mathematics-environment-exploration', 5],
            ['grade-2', 'people-society', 'religion', 1],
            ['grade-2', 'physical-education-sport-health', 'physical-education', 2],
            ['grade-2', 'arts', 'music-movement', 2],
            ['grade-2', 'arts', 'visual-arts-practical-skills', 2],
            ['grade-2', 'counselling-orientation', 'personal-development', 1],

            ['grade-3', 'language-communication', 'romanian-language-literature', 5],
            ['grade-3', 'language-communication', 'modern-language', 2],
            ['grade-3', 'mathematics-sciences', 'mathematics', 4],
            ['grade-3', 'mathematics-sciences', 'natural-sciences', 1],
            ['grade-3', 'people-society', 'civic-education', 1],
            ['grade-3', 'people-society', 'religion', 1],
            ['grade-3', 'physical-education-sport-health', 'physical-education', 2],
            ['grade-3', 'physical-education-sport-health', 'play-movement', 1],
            ['grade-3', 'arts', 'music-movement', 1],
            ['grade-3', 'arts', 'visual-arts-practical-skills', 2],

            ['grade-4', 'language-communication', 'romanian-language-literature', 5],
            ['grade-4', 'language-communication', 'modern-language', 2],
            ['grade-4', 'mathematics-sciences', 'mathematics', 4],
            ['grade-4', 'mathematics-sciences', 'natural-sciences', 1],
            ['grade-4', 'people-society', 'history', 1],
            ['grade-4', 'people-society', 'geography', 1],
            ['grade-4', 'people-society', 'civic-education', 1],
            ['grade-4', 'people-society', 'religion', 1],
            ['grade-4', 'physical-education-sport-health', 'physical-education', 2],
            ['grade-4', 'physical-education-sport-health', 'play-movement', 1],
            ['grade-4', 'arts', 'music-movement', 1],
            ['grade-4', 'arts', 'visual-arts-practical-skills', 1],
        ];

        $this->seedFrameworkRows(
            $version,
            $variant,
            $rows,
            $areas,
            $levels,
            $subjects
        );
    }

    private function seedLowerSecondaryFramework(
        CurriculumVersion $version,
        CurriculumFrameworkVariant $variant,
        array $areas,
        array $levels,
        array $subjects
    ): void {
        $rows = [
            ['grade-5', 'language-communication', 'romanian-language-literature', 4],
            ['grade-5', 'language-communication', 'modern-language-1', 2],
            ['grade-5', 'language-communication', 'modern-language-2', 2],
            ['grade-5', 'mathematics-sciences', 'mathematics', 4],
            ['grade-5', 'mathematics-sciences', 'biology', 1],
            ['grade-5', 'people-society', 'social-education', 1],
            ['grade-5', 'people-society', 'history', 2],
            ['grade-5', 'people-society', 'geography', 1],
            ['grade-5', 'people-society', 'religion', 1],
            ['grade-5', 'arts', 'visual-education', 1],
            ['grade-5', 'arts', 'music-education', 1],
            ['grade-5', 'physical-education-sport-health', 'physical-education-sport', 2],
            ['grade-5', 'technologies', 'technology-practical-applications', 1],
            ['grade-5', 'technologies', 'informatics-ict', 1],
            ['grade-5', 'counselling-orientation', 'counselling-personal-development', 1],

            ['grade-6', 'language-communication', 'romanian-language-literature', 4],
            ['grade-6', 'language-communication', 'modern-language-1', 2],
            ['grade-6', 'language-communication', 'modern-language-2', 2],
            ['grade-6', 'mathematics-sciences', 'mathematics', 4],
            ['grade-6', 'mathematics-sciences', 'physics', 2],
            ['grade-6', 'mathematics-sciences', 'biology', 2],
            ['grade-6', 'people-society', 'social-education', 1],
            ['grade-6', 'people-society', 'history', 1],
            ['grade-6', 'people-society', 'geography', 1],
            ['grade-6', 'people-society', 'religion', 1],
            ['grade-6', 'arts', 'visual-education', 1],
            ['grade-6', 'arts', 'music-education', 1],
            ['grade-6', 'physical-education-sport-health', 'physical-education-sport', 2],
            ['grade-6', 'technologies', 'technology-practical-applications', 1],
            ['grade-6', 'technologies', 'informatics-ict', 1],
            ['grade-6', 'counselling-orientation', 'counselling-personal-development', 1],

            ['grade-7', 'language-communication', 'romanian-language-literature', 4],
            ['grade-7', 'language-communication', 'modern-language-1', 2],
            ['grade-7', 'language-communication', 'modern-language-2', 2],
            ['grade-7', 'language-communication', 'latin-language-culture-elements', 1],
            ['grade-7', 'mathematics-sciences', 'mathematics', 4],
            ['grade-7', 'mathematics-sciences', 'physics', 2],
            ['grade-7', 'mathematics-sciences', 'chemistry', 2],
            ['grade-7', 'mathematics-sciences', 'biology', 2],
            ['grade-7', 'people-society', 'social-education', 1],
            ['grade-7', 'people-society', 'history', 1],
            ['grade-7', 'people-society', 'geography', 1],
            ['grade-7', 'people-society', 'religion', 1],
            ['grade-7', 'arts', 'visual-education', 1],
            ['grade-7', 'arts', 'music-education', 1],
            ['grade-7', 'physical-education-sport-health', 'physical-education-sport', 2],
            ['grade-7', 'technologies', 'technology-practical-applications', 1],
            ['grade-7', 'technologies', 'informatics-ict', 1],
            ['grade-7', 'counselling-orientation', 'counselling-personal-development', 1],

            ['grade-8', 'language-communication', 'romanian-language-literature', 4],
            ['grade-8', 'language-communication', 'modern-language-1', 2],
            ['grade-8', 'language-communication', 'modern-language-2', 2],
            ['grade-8', 'mathematics-sciences', 'mathematics', 4],
            ['grade-8', 'mathematics-sciences', 'physics', 2],
            ['grade-8', 'mathematics-sciences', 'chemistry', 2],
            ['grade-8', 'mathematics-sciences', 'biology', 1],
            ['grade-8', 'people-society', 'social-education', 1],
            ['grade-8', 'people-society', 'history', 2],
            ['grade-8', 'people-society', 'geography', 2],
            ['grade-8', 'people-society', 'religion', 1],
            ['grade-8', 'arts', 'visual-education', 1],
            ['grade-8', 'arts', 'music-education', 1],
            ['grade-8', 'physical-education-sport-health', 'physical-education-sport', 2],
            ['grade-8', 'technologies', 'technology-practical-applications', 1],
            ['grade-8', 'technologies', 'informatics-ict', 1],
            ['grade-8', 'counselling-orientation', 'counselling-personal-development', 1],
        ];

        $this->seedFrameworkRows(
            $version,
            $variant,
            $rows,
            $areas,
            $levels,
            $subjects
        );
    }

    private function seedFrameworkRows(
        CurriculumVersion $version,
        CurriculumFrameworkVariant $variant,
        array $rows,
        array $areas,
        array $levels,
        array $subjects
    ): void {
        foreach ($rows as $index => $row) {
            [$levelCode, $areaCode, $subjectCode, $hours] = $row;

            CurriculumSubject::updateOrCreate(
                [
                    'curriculum_version_id' => $version->id,
                    'curriculum_framework_variant_id' => $variant->id,
                    'education_level_id' => $levels[$levelCode]->id,
                    'subject_id' => $subjects[$subjectCode]->id,
                ],
                [
                    'curriculum_area_id' => $areas[$areaCode]->id,
                    'component' => 'TC',
                    'hours_min' => $hours,
                    'hours_max' => $hours,
                    'display_order' => $index + 1,
                    'status' => 'active',
                ]
            );
        }
    }
}
