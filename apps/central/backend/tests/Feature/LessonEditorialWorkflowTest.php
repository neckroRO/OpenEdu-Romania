<?php

namespace Tests\Feature;

use App\Enums\LessonVersionStatus;
use App\Exceptions\InvalidEditorialTransitionException;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\Subject;
use App\Models\User;
use App\Services\LessonEditorialWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonEditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_can_be_submitted(): void
    {
        $version = $this->makeDraftVersion();

        $result = app(LessonEditorialWorkflow::class)->transition(
            $version,
            LessonVersionStatus::Submitted
        );

        $this->assertSame(
            LessonVersionStatus::Submitted,
            $result->status
        );

        $this->assertNotNull($result->submitted_at);
    }

    public function test_submitted_version_can_be_approved(): void
    {
        $reviewer = User::factory()->create();

        $workflow = app(LessonEditorialWorkflow::class);

        $version = $workflow->transition(
            $this->makeDraftVersion(),
            LessonVersionStatus::Submitted
        );

        $result = $workflow->transition(
            $version,
            LessonVersionStatus::Approved,
            $reviewer->id,
            'Lecție validată pedagogic.'
        );

        $this->assertSame(
            LessonVersionStatus::Approved,
            $result->status
        );

        $this->assertSame(
            $reviewer->id,
            $result->reviewed_by
        );

        $this->assertNotNull($result->reviewed_at);

        $this->assertSame(
            'Lecție validată pedagogic.',
            $result->review_note
        );
    }

    public function test_rejected_version_can_return_to_draft_and_keeps_feedback(): void
    {
        $reviewer = User::factory()->create();

        $workflow = app(LessonEditorialWorkflow::class);

        $version = $workflow->transition(
            $this->makeDraftVersion(),
            LessonVersionStatus::Submitted
        );

        $version = $workflow->transition(
            $version,
            LessonVersionStatus::Rejected,
            $reviewer->id,
            'Obiectivele trebuie reformulate.'
        );

        $result = $workflow->transition(
            $version,
            LessonVersionStatus::Draft
        );

        $this->assertSame(
            LessonVersionStatus::Draft,
            $result->status
        );

        $this->assertNull($result->submitted_at);

        $this->assertSame(
            $reviewer->id,
            $result->reviewed_by
        );

        $this->assertSame(
            'Obiectivele trebuie reformulate.',
            $result->review_note
        );
    }

    public function test_approved_version_can_be_published(): void
    {
        $reviewer = User::factory()->create();

        $workflow = app(LessonEditorialWorkflow::class);

        $version = $workflow->transition(
            $this->makeDraftVersion(),
            LessonVersionStatus::Submitted
        );

        $version = $workflow->transition(
            $version,
            LessonVersionStatus::Approved,
            $reviewer->id
        );

        $result = $workflow->transition(
            $version,
            LessonVersionStatus::Published
        );

        $this->assertSame(
            LessonVersionStatus::Published,
            $result->status
        );

        $this->assertNotNull($result->published_at);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $version = $this->makeDraftVersion();

        $this->expectException(
            InvalidEditorialTransitionException::class
        );

        app(LessonEditorialWorkflow::class)->transition(
            $version,
            LessonVersionStatus::Published
        );
    }

    private function makeDraftVersion(): LessonVersion
    {
        $curriculum = Curriculum::create([
            'code' => 'RO-'.uniqid(),
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $curriculumVersion = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST',
            'status' => 'active',
        ]);

        $educationLevel = EducationLevel::create([
            'code' => 'grade-'.uniqid(),
            'name' => 'Clasă test',
            'ordinal' => 7,
            'education_stage' => 'gimnazial',
        ]);

        $subject = Subject::create([
            'code' => 'subject-'.uniqid(),
            'name' => 'Disciplină test',
            'status' => 'active',
        ]);

        $curriculumSubject = CurriculumSubject::create([
            'curriculum_version_id' => $curriculumVersion->id,
            'education_level_id' => $educationLevel->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);

        $lesson = Lesson::create([
            'curriculum_subject_id' => $curriculumSubject->id,
            'code' => 'lesson-'.uniqid(),
            'title' => 'Lecție de test',
            'display_order' => 1,
            'status' => 'active',
        ]);

        return LessonVersion::create([
            'lesson_id' => $lesson->id,
            'version_number' => 1,
            'summary' => 'Rezumatul lecției.',
            'learning_objectives' => [
                'Identificarea conceptului.',
                'Aplicarea conceptului.',
            ],
            'content' => 'Conținutul lecției.',
            'estimated_duration_minutes' => 50,
            'language_code' => 'ro',
            'status' => LessonVersionStatus::Draft,
        ]);
    }
}
