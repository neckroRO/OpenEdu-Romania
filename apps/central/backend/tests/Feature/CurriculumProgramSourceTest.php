<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumProgramSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_curriculum_subject_can_store_official_program_source(): void
    {
        $curriculum = Curriculum::create([
            'code' => 'RO-NATIONAL',
            'name' => 'Curriculum național România',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST',
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'grade-7',
            'name' => 'Clasa a VII-a',
            'ordinal' => 7,
            'education_stage' => 'gimnazial',
        ]);

        $subject = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $curriculumSubject = CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
            'program_reference' => 'OMEN nr. 3393/28.02.2017',
            'program_source_url' => 'https://example.test/programa.pdf',
            'program_approved_at' => '2017-02-28',
        ]);

        $curriculumSubject->refresh();

        $this->assertSame(
            'OMEN nr. 3393/28.02.2017',
            $curriculumSubject->program_reference
        );

        $this->assertSame(
            'https://example.test/programa.pdf',
            $curriculumSubject->program_source_url
        );

        $this->assertSame(
            '2017-02-28',
            $curriculumSubject->program_approved_at->toDateString()
        );
    }
}
