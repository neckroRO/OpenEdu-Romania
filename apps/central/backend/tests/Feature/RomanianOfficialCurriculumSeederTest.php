<?php

namespace Tests\Feature;

use Database\Seeders\RomanianOfficialCurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RomanianOfficialCurriculumSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_curriculum_seeder_creates_primary_and_lower_secondary_frameworks(): void
    {
        $this->seed(RomanianOfficialCurriculumSeeder::class);

        $this->assertDatabaseCount('curriculum_areas', 7);
        $this->assertDatabaseCount('education_levels', 9);
        $this->assertDatabaseCount('subjects', 28);
        $this->assertDatabaseCount('curriculum_framework_variants', 2);
        $this->assertDatabaseCount('curriculum_subjects', 112);

        $this->assertDatabaseHas('curricula', [
            'code' => 'RO-NATIONAL',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('curriculum_versions', [
            'version' => 'RO-PRIMARY-OM3371-2013',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('curriculum_versions', [
            'version' => 'RO-GYMNASIUM-OM3590-2016',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('curriculum_framework_variants', [
            'code' => 'standard-ro',
            'source_reference' => 'OMEN nr. 3371/12.03.2013',
            'source_annex' => 'Anexa 1',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('curriculum_framework_variants', [
            'code' => 'standard-ro',
            'source_reference' => 'OMENCS nr. 3590/05.04.2016',
            'source_annex' => 'Anexa 2',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('education_levels', [
            'code' => 'grade-p',
            'name' => 'Clasa pregătitoare',
            'ordinal' => 0,
            'education_stage' => 'primar',
        ]);

        $this->assertDatabaseHas('education_levels', [
            'code' => 'grade-8',
            'name' => 'Clasa a VIII-a',
            'ordinal' => 8,
            'education_stage' => 'gimnazial',
        ]);

        $this->assertDatabaseHas('subjects', [
            'code' => 'mathematics-environment-exploration',
            'name' => 'Matematică și explorarea mediului',
        ]);

        $this->assertDatabaseHas('subjects', [
            'code' => 'latin-language-culture-elements',
            'name' => 'Elemente de limbă latină și de cultură romanică',
        ]);

        $this->assertDatabaseHas('curriculum_areas', [
            'code' => 'language-communication',
            'name' => 'Limbă și comunicare',
        ]);

        $this->assertDatabaseHas('curriculum_areas', [
            'code' => 'mathematics-sciences',
            'name' => 'Matematică și științe ale naturii',
        ]);
    }

    public function test_official_curriculum_seeder_is_idempotent(): void
    {
        $this->seed(RomanianOfficialCurriculumSeeder::class);
        $this->seed(RomanianOfficialCurriculumSeeder::class);

        $this->assertDatabaseCount('curriculum_areas', 7);
        $this->assertDatabaseCount('education_levels', 9);
        $this->assertDatabaseCount('subjects', 28);
        $this->assertDatabaseCount('curriculum_framework_variants', 2);
        $this->assertDatabaseCount('curriculum_subjects', 112);
    }

    public function test_official_curriculum_contains_expected_subject_counts_per_grade(): void
    {
        $this->seed(RomanianOfficialCurriculumSeeder::class);

        $expectedCounts = [
            'grade-p' => 8,
            'grade-1' => 8,
            'grade-2' => 8,
            'grade-3' => 10,
            'grade-4' => 12,
            'grade-5' => 15,
            'grade-6' => 16,
            'grade-7' => 18,
            'grade-8' => 17,
        ];

        foreach ($expectedCounts as $code => $expectedCount) {
            $levelId = \DB::table('education_levels')
                ->where('code', $code)
                ->value('id');

            $actualCount = \DB::table('curriculum_subjects')
                ->where('education_level_id', $levelId)
                ->count();

            $this->assertSame(
                $expectedCount,
                $actualCount,
                "Unexpected curriculum subject count for {$code}."
            );
        }
    }

    public function test_official_curriculum_contains_key_hour_allocations(): void
    {
        $this->seed(RomanianOfficialCurriculumSeeder::class);

        $this->assertFrameworkHours(
            'RO-PRIMARY-OM3371-2013',
            'grade-p',
            'romanian-communication',
            5
        );

        $this->assertFrameworkHours(
            'RO-PRIMARY-OM3371-2013',
            'grade-2',
            'mathematics-environment-exploration',
            5
        );

        $this->assertFrameworkHours(
            'RO-PRIMARY-OM3371-2013',
            'grade-4',
            'history',
            1
        );

        $this->assertFrameworkHours(
            'RO-GYMNASIUM-OM3590-2016',
            'grade-5',
            'mathematics',
            4
        );

        $this->assertFrameworkHours(
            'RO-GYMNASIUM-OM3590-2016',
            'grade-6',
            'physics',
            2
        );

        $this->assertFrameworkHours(
            'RO-GYMNASIUM-OM3590-2016',
            'grade-7',
            'chemistry',
            2
        );

        $this->assertFrameworkHours(
            'RO-GYMNASIUM-OM3590-2016',
            'grade-7',
            'latin-language-culture-elements',
            1
        );

        $this->assertFrameworkHours(
            'RO-GYMNASIUM-OM3590-2016',
            'grade-8',
            'geography',
            2
        );
    }

    private function assertFrameworkHours(
        string $version,
        string $levelCode,
        string $subjectCode,
        int $hours
    ): void {
        $row = \DB::table('curriculum_subjects')
            ->join(
                'curriculum_versions',
                'curriculum_versions.id',
                '=',
                'curriculum_subjects.curriculum_version_id'
            )
            ->join(
                'education_levels',
                'education_levels.id',
                '=',
                'curriculum_subjects.education_level_id'
            )
            ->join(
                'subjects',
                'subjects.id',
                '=',
                'curriculum_subjects.subject_id'
            )
            ->where('curriculum_versions.version', $version)
            ->where('education_levels.code', $levelCode)
            ->where('subjects.code', $subjectCode)
            ->select([
                'curriculum_subjects.hours_min',
                'curriculum_subjects.hours_max',
                'curriculum_subjects.component',
            ])
            ->first();

        $this->assertNotNull(
            $row,
            "Missing {$subjectCode} for {$levelCode} in {$version}."
        );

        $this->assertSame($hours, (int) $row->hours_min);
        $this->assertSame($hours, (int) $row->hours_max);
        $this->assertSame('TC', $row->component);
    }
}
