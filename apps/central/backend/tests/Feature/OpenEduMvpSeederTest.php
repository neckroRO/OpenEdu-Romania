<?php

namespace Tests\Feature;

use App\Models\CurriculumSubject;
use App\Models\Lesson;
use Database\Seeders\OpenEduMvpSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OpenEduMvpSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_mvp_seeder_creates_curriculum_lessons_competencies_and_is_idempotent(): void
    {
        $this->seed(OpenEduMvpSeeder::class);
        $this->seed(OpenEduMvpSeeder::class);

        $this->assertDatabaseHas('curriculum_subjects', [
            'program_reference' => 'OMEN nr. 3393/28.02.2017',
            'program_source_url' =>
                'https://www.edu.ro/Ordin_ministru_3393_2017',
        ]);

        $curriculumSubject = CurriculumSubject::query()->firstOrFail();

        $this->assertSame(
            '2017-02-28',
            $curriculumSubject->program_approved_at->toDateString()
        );

        $this->assertDatabaseHas('concepts', [
            'code' => 'linear-equations',
            'title' => 'Ecuații de gradul I',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('competencies', [
            'code' => '1',
            'type' => 'general',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('competencies', [
            'code' => '1.2',
            'type' => 'specific',
            'status' => 'active',
        ]);

        $this->assertSame(
            6,
            DB::table('competencies')
                ->where('type', 'general')
                ->count()
        );

        $this->assertSame(
            6,
            DB::table('competencies')
                ->where('type', 'specific')
                ->count()
        );

        $this->assertSame(
            6,
            DB::table('competency_concepts')->count()
        );

        $this->assertSame(
            1,
            DB::table('curriculum_subjects')->count()
        );

        $this->assertSame(
            1,
            DB::table('concepts')
                ->where('code', 'linear-equations')
                ->count()
        );

        $lesson = Lesson::query()
            ->where('code', 'linear-equations-basics')
            ->firstOrFail();

        $this->assertSame(
            $curriculumSubject->id,
            $lesson->curriculum_subject_id
        );

        $this->assertSame(
            'Ecuații de gradul I',
            $lesson->title
        );

        $this->assertSame(
            1,
            DB::table('lessons')
                ->where('code', 'linear-equations-basics')
                ->count()
        );

        $this->assertSame(
            6,
            DB::table('lesson_competencies')
                ->where('lesson_id', $lesson->id)
                ->count()
        );

        $this->assertSame(
            6,
            DB::table('lesson_competencies')
                ->where('lesson_id', $lesson->id)
                ->where('is_core', true)
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('resources')
                ->where(
                    'code',
                    'linear-equations-introduction'
                )
                ->count()
        );
    }
}
