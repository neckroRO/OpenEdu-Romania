<?php

namespace Tests\Feature;

use App\Enums\LessonReviewVerdict;
use App\Enums\LessonVersionStatus;
use App\Enums\ReputationEventType;
use App\Exceptions\PedagogicalValidationException;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\ReputationEvent;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use App\Services\LessonEditorialWorkflow;
use App\Services\PedagogicalReviewService;
use App\Services\PedagogicalValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedagogicalValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_make_editorial_decision(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $teacher = User::factory()
            ->teacher()
            ->create();

        $this->expectException(
            PedagogicalValidationException::class
        );

        $this->service()->moderate(
            $version,
            $teacher,
            LessonVersionStatus::Approved
        );
    }

    public function test_moderation_requires_minimum_three_reviews(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $moderator = User::factory()
            ->moderator()
            ->create();

        try {
            $this->service()->moderate(
                $version,
                $moderator,
                LessonVersionStatus::Approved
            );

            $this->fail(
                'Expected pedagogical validation exception.'
            );
        } catch (PedagogicalValidationException) {
            $version->refresh();

            $this->assertSame(
                LessonVersionStatus::Submitted,
                $version->status
            );
        }
    }

    public function test_approval_rewards_matching_reviews_and_penalizes_opposing_review(): void
    {
        [$version, $subject] = $this->makeSubmittedVersion();

        [$reviewA, $reviewerA] = $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        [$reviewB, $reviewerB] = $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        [$reviewC, $reviewerC] = $this->submitReview(
            $version,
            LessonReviewVerdict::ChangesRequested
        );

        $moderator = User::factory()
            ->moderator()
            ->create();

        $result = $this->service()->moderate(
            $version,
            $moderator,
            LessonVersionStatus::Approved,
            'Validare editorială.'
        );

        $this->assertSame(
            LessonVersionStatus::Approved,
            $result->status
        );

        $this->assertSame(
            $moderator->id,
            $result->reviewed_by
        );

        $this->assertSame(
            'Validare editorială.',
            $result->review_note
        );

        $this->assertSame(
            3,
            $this->reputation($reviewerA, $subject)->score
        );

        $this->assertSame(
            3,
            $this->reputation($reviewerB, $subject)->score
        );

        $this->assertSame(
            -3,
            $this->reputation($reviewerC, $subject)->score
        );

        $this->assertSame(
            1,
            $this->reputation($reviewerA, $subject)->review_count
        );

        $this->assertDatabaseHas('reputation_events', [
            'lesson_version_review_id' => $reviewA->id,
            'event_type' =>
                ReputationEventType::ReviewConfirmed->value,
            'points' => 3,
        ]);

        $this->assertDatabaseHas('reputation_events', [
            'lesson_version_review_id' => $reviewB->id,
            'event_type' =>
                ReputationEventType::ReviewConfirmed->value,
            'points' => 3,
        ]);

        $this->assertDatabaseHas('reputation_events', [
            'lesson_version_review_id' => $reviewC->id,
            'event_type' =>
                ReputationEventType::ReviewContradicted->value,
            'points' => -3,
        ]);
    }

    public function test_rejected_decision_confirms_reject_and_changes_requested_reviews(): void
    {
        [$version, $subject] = $this->makeSubmittedVersion();

        [, $approveReviewer] = $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        [, $changesReviewer] = $this->submitReview(
            $version,
            LessonReviewVerdict::ChangesRequested
        );

        [, $rejectReviewer] = $this->submitReview(
            $version,
            LessonReviewVerdict::Reject
        );

        $moderator = User::factory()
            ->moderator()
            ->create();

        $result = $this->service()->moderate(
            $version,
            $moderator,
            LessonVersionStatus::Rejected
        );

        $this->assertSame(
            LessonVersionStatus::Rejected,
            $result->status
        );

        $this->assertSame(
            -3,
            $this->reputation(
                $approveReviewer,
                $subject
            )->score
        );

        $this->assertSame(
            3,
            $this->reputation(
                $changesReviewer,
                $subject
            )->score
        );

        $this->assertSame(
            3,
            $this->reputation(
                $rejectReviewer,
                $subject
            )->score
        );
    }

    public function test_moderator_can_override_pedagogical_consensus(): void
    {
        [$version, $subject] = $this->makeSubmittedVersion();

        [, $approveReviewer] = $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        [, $rejectReviewerA] = $this->submitReview(
            $version,
            LessonReviewVerdict::Reject
        );

        [, $rejectReviewerB] = $this->submitReview(
            $version,
            LessonReviewVerdict::Reject
        );

        $moderator = User::factory()
            ->moderator()
            ->create();

        $result = $this->service()->moderate(
            $version,
            $moderator,
            LessonVersionStatus::Approved,
            'Aprobare motivată de verificarea editorială.'
        );

        $this->assertSame(
            LessonVersionStatus::Approved,
            $result->status
        );

        $this->assertSame(
            3,
            $this->reputation(
                $approveReviewer,
                $subject
            )->score
        );

        $this->assertSame(
            -3,
            $this->reputation(
                $rejectReviewerA,
                $subject
            )->score
        );

        $this->assertSame(
            -3,
            $this->reputation(
                $rejectReviewerB,
                $subject
            )->score
        );
    }

    public function test_only_current_review_round_affects_reputation(): void
    {
        [$version, $subject] = $this->makeSubmittedVersion();

        [, $oldReviewerA] = $this->submitReview(
            $version,
            LessonReviewVerdict::Reject
        );

        [, $oldReviewerB] = $this->submitReview(
            $version,
            LessonReviewVerdict::Reject
        );

        [, $oldReviewerC] = $this->submitReview(
            $version,
            LessonReviewVerdict::Reject
        );

        $moderator = User::factory()
            ->moderator()
            ->create();

        $workflow = app(LessonEditorialWorkflow::class);

        $version = $workflow->transition(
            $version,
            LessonVersionStatus::Rejected,
            $moderator->id,
            'Necesită revizuire.'
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

        [, $newReviewerA] = $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        [, $newReviewerB] = $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        [, $newReviewerC] = $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $result = $this->service()->moderate(
            $version,
            $moderator,
            LessonVersionStatus::Approved
        );

        $this->assertSame(
            LessonVersionStatus::Approved,
            $result->status
        );

        foreach (
            [$oldReviewerA, $oldReviewerB, $oldReviewerC]
            as $oldReviewer
        ) {
            $this->assertNull(
                UserSubjectReputation::query()
                    ->where('user_id', $oldReviewer->id)
                    ->where('subject_id', $subject->id)
                    ->first()
            );
        }

        foreach (
            [$newReviewerA, $newReviewerB, $newReviewerC]
            as $newReviewer
        ) {
            $this->assertSame(
                3,
                $this->reputation(
                    $newReviewer,
                    $subject
                )->score
            );
        }
    }

    public function test_publishing_rewards_lesson_author(): void
    {
        [$version, $subject, $author] =
            $this->makeSubmittedVersion();

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $moderator = User::factory()
            ->moderator()
            ->create();

        $version = $this->service()->moderate(
            $version,
            $moderator,
            LessonVersionStatus::Approved
        );

        $version = $this->service()->publish(
            $version,
            $moderator
        );

        $this->assertSame(
            LessonVersionStatus::Published,
            $version->status
        );

        $this->assertNotNull($version->published_at);

        $reputation = $this->reputation(
            $author,
            $subject
        );

        $this->assertSame(10, $reputation->score);
        $this->assertSame(
            1,
            $reputation->contribution_count
        );

        $this->assertDatabaseHas('reputation_events', [
            'user_id' => $author->id,
            'subject_id' => $subject->id,
            'lesson_version_id' => $version->id,
            'event_type' =>
                ReputationEventType::LessonPublished->value,
            'points' => 10,
        ]);
    }

    public function test_teacher_cannot_publish_lesson(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $moderator = User::factory()
            ->moderator()
            ->create();

        $version = $this->service()->moderate(
            $version,
            $moderator,
            LessonVersionStatus::Approved
        );

        $teacher = User::factory()
            ->teacher()
            ->create();

        $this->expectException(
            PedagogicalValidationException::class
        );

        $this->service()->publish(
            $version,
            $teacher
        );
    }

    public function test_moderation_creates_auditable_metadata(): void
    {
        [$version] = $this->makeSubmittedVersion();

        [$review] = $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitReview(
            $version,
            LessonReviewVerdict::ChangesRequested
        );

        $moderator = User::factory()
            ->admin()
            ->create();

        $this->service()->moderate(
            $version,
            $moderator,
            LessonVersionStatus::Approved
        );

        $event = ReputationEvent::query()
            ->where(
                'lesson_version_review_id',
                $review->id
            )
            ->firstOrFail();

        $this->assertSame(
            [
                'review_round' => 1,
                'review_verdict' => 'approve',
                'editorial_decision' => 'approved',
                'consensus_status' => 'approved',
            ],
            $event->metadata
        );
    }

    private function service(): PedagogicalValidationService
    {
        return app(PedagogicalValidationService::class);
    }

    /**
     * @return array{
     *     \App\Models\LessonVersionReview,
     *     User
     * }
     */
    private function submitReview(
        LessonVersion $version,
        LessonReviewVerdict $verdict
    ): array {
        $reviewer = User::factory()
            ->teacher()
            ->create();

        $review = app(PedagogicalReviewService::class)
            ->submitReview(
                $version,
                $reviewer,
                $verdict,
                [
                    'correctness_score' => 5,
                    'curriculum_alignment_score' => 5,
                    'clarity_score' => 4,
                    'pedagogical_value_score' => 5,
                    'difficulty_fit_score' => 4,
                ]
            );

        return [$review, $reviewer];
    }

    private function reputation(
        User $user,
        Subject $subject
    ): UserSubjectReputation {
        return UserSubjectReputation::query()
            ->where('user_id', $user->id)
            ->where('subject_id', $subject->id)
            ->firstOrFail();
    }

    /**
     * @return array{LessonVersion, Subject, User}
     */
    private function makeSubmittedVersion(): array
    {
        $author = User::factory()
            ->teacher()
            ->create();

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
            'curriculum_version_id' =>
                $curriculumVersion->id,
            'education_level_id' =>
                $educationLevel->id,
            'subject_id' => $subject->id,
            'display_order' => 1,
            'status' => 'active',
        ]);

        $lesson = Lesson::create([
            'curriculum_subject_id' =>
                $curriculumSubject->id,
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
            'created_by' => $author->id,
        ]);

        $version = app(LessonEditorialWorkflow::class)
            ->transition(
                $version,
                LessonVersionStatus::Submitted
            );

        return [$version, $subject, $author];
    }
}
