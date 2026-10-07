<?php

namespace Tests\Feature;

use App\Enums\LessonReviewVerdict;
use App\Enums\LessonVersionStatus;
use App\Enums\ReputationEventType;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\LessonVersionReview;
use App\Models\ReputationEvent;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use App\Services\LessonEditorialWorkflow;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedagogicalValidationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_submission_starts_review_round_one(): void
    {
        [$version] = $this->makeDraftVersion();

        $result = app(LessonEditorialWorkflow::class)->transition(
            $version,
            LessonVersionStatus::Submitted
        );

        $this->assertSame(1, $result->review_round);
    }

    public function test_resubmission_starts_new_round_and_keeps_old_reviews(): void
    {
        [$version] = $this->makeDraftVersion();

        $reviewer = User::factory()->create();

        $workflow = app(LessonEditorialWorkflow::class);

        $version = $workflow->transition(
            $version,
            LessonVersionStatus::Submitted
        );

        $this->createReview(
            $version,
            $reviewer,
            LessonReviewVerdict::ChangesRequested
        );

        $version = $workflow->transition(
            $version,
            LessonVersionStatus::Rejected,
            $reviewer->id,
            'Necesită modificări.'
        );

        $version = $workflow->transition(
            $version,
            LessonVersionStatus::Draft
        );

        $version = $workflow->transition(
            $version,
            LessonVersionStatus::Submitted
        );

        $this->assertSame(2, $version->review_round);

        $this->createReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve
        );

        $reviews = $version->reviews()
            ->orderBy('review_round')
            ->get();

        $this->assertCount(2, $reviews);

        $this->assertSame(
            [1, 2],
            $reviews->pluck('review_round')->all()
        );

        $this->assertSame(
            LessonReviewVerdict::ChangesRequested,
            $reviews[0]->verdict
        );

        $this->assertSame(
            LessonReviewVerdict::Approve,
            $reviews[1]->verdict
        );
    }

    public function test_same_reviewer_cannot_review_same_round_twice(): void
    {
        [$version] = $this->makeDraftVersion();

        $reviewer = User::factory()->create();

        $version = app(LessonEditorialWorkflow::class)->transition(
            $version,
            LessonVersionStatus::Submitted
        );

        $this->createReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve
        );

        $this->expectException(QueryException::class);

        $this->createReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve
        );
    }

    public function test_reputation_is_unique_per_user_and_subject(): void
    {
        [, $subject] = $this->makeDraftVersion();

        $user = User::factory()->create();

        UserSubjectReputation::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'score' => 25,
            'contribution_count' => 2,
            'review_count' => 3,
        ]);

        $this->expectException(QueryException::class);

        UserSubjectReputation::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'score' => 30,
        ]);
    }

    public function test_reputation_event_casts_type_and_metadata(): void
    {
        [$version, $subject] = $this->makeDraftVersion();

        $user = User::factory()->create();

        $event = ReputationEvent::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'event_type' => ReputationEventType::LessonPublished,
            'points' => 10,
            'lesson_version_id' => $version->id,
            'metadata' => [
                'reason' => 'lesson_published',
            ],
        ])->refresh();

        $this->assertSame(
            ReputationEventType::LessonPublished,
            $event->event_type
        );

        $this->assertSame(10, $event->points);

        $this->assertSame(
            ['reason' => 'lesson_published'],
            $event->metadata
        );

        $this->assertTrue($event->user->is($user));
        $this->assertTrue($event->subject->is($subject));
        $this->assertTrue($event->lessonVersion->is($version));
    }

    public function test_review_uses_verdict_and_weight_casts(): void
    {
        [$version] = $this->makeDraftVersion();

        $reviewer = User::factory()->create();

        $version = app(LessonEditorialWorkflow::class)->transition(
            $version,
            LessonVersionStatus::Submitted
        );

        $review = $this->createReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve
        )->refresh();

        $this->assertSame(
            LessonReviewVerdict::Approve,
            $review->verdict
        );

        $this->assertSame(1, $review->review_round);
        $this->assertSame('1.25', $review->weight_snapshot);

        $this->assertTrue(
            $review->lessonVersion->is($version)
        );

        $this->assertTrue(
            $review->reviewer->is($reviewer)
        );
    }

    private function createReview(
        LessonVersion $version,
        User $reviewer,
        LessonReviewVerdict $verdict
    ): LessonVersionReview {
        return LessonVersionReview::create([
            'lesson_version_id' => $version->id,
            'reviewer_id' => $reviewer->id,
            'review_round' => $version->review_round,
            'verdict' => $verdict,
            'correctness_score' => 5,
            'curriculum_alignment_score' => 5,
            'clarity_score' => 4,
            'pedagogical_value_score' => 5,
            'difficulty_fit_score' => 4,
            'comment' => 'Evaluare pedagogică de test.',
            'weight_snapshot' => 1.25,
        ]);
    }

    /**
     * @return array{LessonVersion, Subject}
     */
    private function makeDraftVersion(): array
    {
        $curriculum = Curriculum::create([
            'code' => 'RO-'.uniqid(),
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $curriculumVersion = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-'.uniqid(),
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

        $version = LessonVersion::create([
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

        return [$version, $subject];
    }
}
