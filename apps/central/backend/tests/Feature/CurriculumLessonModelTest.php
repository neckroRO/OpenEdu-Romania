<?php

namespace Tests\Feature;

use App\Models\Competency;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonCompetency;
use App\Models\Subject;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumLessonModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_belongs_to_curriculum_subject(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $lesson = Lesson::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'code' => 'linear-equations-basics',
            'title' => 'Ecuații de gradul I',
            'description' => 'Introducere în rezolvarea ecuațiilor de gradul I.',
            'display_order' => 1,
            'status' => 'active',
        ]);

        $this->assertTrue(
            $lesson->curriculumSubject->is($curriculumSubject)
        );

        $this->assertTrue(
            $curriculumSubject->lessons()
                ->whereKey($lesson->id)
                ->exists()
        );
    }

    public function test_lesson_can_have_ordered_competencies(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $lesson = $this->createLesson($curriculumSubject);

        $firstCompetency = $this->createCompetency(
            $curriculumSubject,
            '1.2',
            'Identificarea situațiilor rezolvabile prin ecuații'
        );

        $secondCompetency = $this->createCompetency(
            $curriculumSubject,
            '3.2',
            'Utilizarea transformărilor echivalente'
        );

        LessonCompetency::create([
            'lesson_id' => $lesson->id,
            'competency_id' => $firstCompetency->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        LessonCompetency::create([
            'lesson_id' => $lesson->id,
            'competency_id' => $secondCompetency->id,
            'display_order' => 2,
            'is_core' => false,
        ]);

        $links = $lesson->lessonCompetencies()
            ->orderBy('display_order')
            ->get();

        $this->assertCount(2, $links);
        $this->assertTrue($links[0]->competency->is($firstCompetency));
        $this->assertTrue($links[1]->competency->is($secondCompetency));
        $this->assertTrue($links[0]->is_core);
        $this->assertFalse($links[1]->is_core);

        $this->assertTrue(
            $lesson->competencies()
                ->whereKey($firstCompetency->id)
                ->exists()
        );

        $this->assertTrue(
            $firstCompetency->lessons()
                ->whereKey($lesson->id)
                ->exists()
        );
    }

    public function test_lesson_cannot_be_linked_to_competency_from_another_curriculum_subject(): void
    {
        $lessonCurriculumSubject = $this->createCurriculumSubject('lesson');
        $otherCurriculumSubject = $this->createCurriculumSubject('other');

        $lesson = $this->createLesson($lessonCurriculumSubject);

        $competency = $this->createCompetency(
            $otherCurriculumSubject,
            '1.1',
            'Competență din altă programă'
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Lesson and competency must belong to the same curriculum subject.'
        );

        LessonCompetency::create([
            'lesson_id' => $lesson->id,
            'competency_id' => $competency->id,
            'display_order' => 1,
            'is_core' => true,
        ]);
    }

    public function test_deleting_lesson_cascades_competency_links_but_keeps_competency(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $lesson = $this->createLesson($curriculumSubject);

        $competency = $this->createCompetency(
            $curriculumSubject,
            '1.2',
            'Competență specifică'
        );

        $link = LessonCompetency::create([
            'lesson_id' => $lesson->id,
            'competency_id' => $competency->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        $lesson->delete();

        $this->assertDatabaseMissing('lessons', [
            'id' => $lesson->id,
        ]);

        $this->assertDatabaseMissing('lesson_competencies', [
            'id' => $link->id,
        ]);

        $this->assertDatabaseHas('competencies', [
            'id' => $competency->id,
        ]);
    }

    public function test_deleting_curriculum_subject_cascades_lessons_and_links(): void
    {
        $curriculumSubject = $this->createCurriculumSubject();

        $lesson = $this->createLesson($curriculumSubject);

        $competency = $this->createCompetency(
            $curriculumSubject,
            '1.2',
            'Competență specifică'
        );

        $link = LessonCompetency::create([
            'lesson_id' => $lesson->id,
            'competency_id' => $competency->id,
            'display_order' => 1,
            'is_core' => true,
        ]);

        $curriculumSubject->delete();

        $this->assertDatabaseMissing('lessons', [
            'id' => $lesson->id,
        ]);

        $this->assertDatabaseMissing('lesson_competencies', [
            'id' => $link->id,
        ]);

        $this->assertDatabaseMissing('competencies', [
            'id' => $competency->id,
        ]);
    }

    private function createLesson(CurriculumSubject $curriculumSubject): Lesson
    {
        return Lesson::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'code' => 'linear-equations-basics',
            'title' => 'Ecuații de gradul I',
            'description' => 'Lecție de test.',
            'display_order' => 1,
            'status' => 'active',
        ]);
    }

    private function createCompetency(
        CurriculumSubject $curriculumSubject,
        string $code,
        string $title
    ): Competency {
        return Competency::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'parent_competency_id' => null,
            'code' => $code,
            'type' => 'specific',
            'title' => $title,
            'display_order' => 1,
            'status' => 'active',
        ]);
    }

    private function createCurriculumSubject(
        string $suffix = 'test'
    ): CurriculumSubject {
        $curriculum = Curriculum::create([
            'code' => 'RO-'.strtoupper($suffix),
            'name' => 'Curriculum '.$suffix,
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST',
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'grade-'.$suffix,
            'name' => 'Clasă '.$suffix,
            'ordinal' => 1,
            'education_stage' => 'gimnazial',
        ]);

        $subject = Subject::create([
            'code' => 'subject-'.$suffix,
            'name' => 'Disciplină '.$suffix,
            'status' => 'active',
        ]);

        return CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);
    }
}
