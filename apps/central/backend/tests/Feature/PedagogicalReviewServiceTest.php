<?php

namespace Tests\Feature;

use App\Enums\LessonReviewVerdict;
use App\Enums\LessonVersionStatus;
use App\Enums\PedagogicalConsensusStatus;
use App\Exceptions\PedagogicalReviewException;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use App\Services\LessonEditorialWorkflow;
use App\Services\PedagogicalReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedagogicalReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_submit_pedagogical_review(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $reviewer = User::factory()
            ->teacher()
            ->create();

        $review = $this->service()->submitReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve,
            $this->validScores(),
            'Lecția este clară și corectă.'
        );

        $this->assertTrue($review->reviewer->is($reviewer));

        $this->assertSame(
            LessonReviewVerdict::Approve,
            $review->verdict
        );

        $this->assertSame(1, $review->review_round);
        $this->assertSame('1.00', $review->weight_snapshot);
    }

    public function test_learner_cannot_submit_review(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $learner = User::factory()->create();

        $this->expectException(
            PedagogicalReviewException::class
        );

        $this->service()->submitReview(
            $version,
            $learner,
            LessonReviewVerdict::Approve,
            $this->validScores()
        );
    }

    public function test_author_cannot_review_own_lesson(): void
    {
        [$version, , $author] = $this->makeSubmittedVersion();

        $this->expectException(
            PedagogicalReviewException::class
        );

        $this->service()->submitReview(
            $version,
            $author,
            LessonReviewVerdict::Approve,
            $this->validScores()
        );
    }

    public function test_only_submitted_version_can_be_reviewed(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $version->status = LessonVersionStatus::Approved;
        $version->save();

        $reviewer = User::factory()
            ->teacher()
            ->create();

        $this->expectException(
            PedagogicalReviewException::class
        );

        $this->service()->submitReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve,
            $this->validScores()
        );
    }

    public function test_reviewer_cannot_review_same_round_twice(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $reviewer = User::factory()
            ->teacher()
            ->create();

        $service = $this->service();

        $service->submitReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve,
            $this->validScores()
        );

        $this->expectException(
            PedagogicalReviewException::class
        );

        $service->submitReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve,
            $this->validScores()
        );
    }

    public function test_review_scores_must_be_between_one_and_five(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $reviewer = User::factory()
            ->teacher()
            ->create();

        $scores = $this->validScores();
        $scores['correctness_score'] = 6;

        $this->expectException(
            PedagogicalReviewException::class
        );

        $this->service()->submitReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve,
            $scores
        );
    }

    public function test_all_required_scores_must_be_present(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $reviewer = User::factory()
            ->teacher()
            ->create();

        $scores = $this->validScores();

        unset($scores['difficulty_fit_score']);

        $this->expectException(
            PedagogicalReviewException::class
        );

        $this->service()->submitReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve,
            $scores
        );
    }

    public function test_reputation_is_snapshotted_as_review_weight(): void
    {
        [$version, $subject] = $this->makeSubmittedVersion();

        $reviewer = User::factory()
            ->teacher()
            ->create();

        UserSubjectReputation::create([
            'user_id' => $reviewer->id,
            'subject_id' => $subject->id,
            'score' => 175,
        ]);

        $review = $this->service()->submitReview(
            $version,
            $reviewer,
            LessonReviewVerdict::Approve,
            $this->validScores()
        );

        $this->assertSame(
            '1.25',
            $review->weight_snapshot
        );

        UserSubjectReputation::query()
            ->where('user_id', $reviewer->id)
            ->where('subject_id', $subject->id)
            ->update([
                'score' => 400,
            ]);

        $review->refresh();

        $this->assertSame(
            '1.25',
            $review->weight_snapshot
        );
    }

    public function test_consensus_is_pending_with_fewer_than_three_reviews(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $result = $this->service()->consensus($version);

        $this->assertSame(
            PedagogicalConsensusStatus::Pending,
            $result['status']
        );

        $this->assertSame(2, $result['reviewer_count']);
    }

    public function test_two_approvals_and_one_changes_request_are_approved(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::ChangesRequested
        );

        $result = $this->service()->consensus($version);

        $this->assertSame(
            PedagogicalConsensusStatus::Approved,
            $result['status']
        );

        $this->assertSame(3, $result['reviewer_count']);
        $this->assertSame(3.0, $result['total_weight']);
        $this->assertSame(2.0, $result['approve_weight']);
        $this->assertSame(
            1.0,
            $result['changes_requested_weight']
        );
        $this->assertSame(0.0, $result['reject_weight']);
    }

    public function test_any_reject_prevents_approval(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Reject
        );

        $result = $this->service()->consensus($version);

        $this->assertSame(
            PedagogicalConsensusStatus::ChangesRequested,
            $result['status']
        );
    }

    public function test_majority_reject_weight_produces_rejected_consensus(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Reject
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Reject
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $result = $this->service()->consensus($version);

        $this->assertSame(
            PedagogicalConsensusStatus::Rejected,
            $result['status']
        );

        $this->assertSame(2.0, $result['reject_weight']);
    }

    public function test_without_approval_or_rejection_threshold_consensus_requests_changes(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::ChangesRequested
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::ChangesRequested
        );

        $result = $this->service()->consensus($version);

        $this->assertSame(
            PedagogicalConsensusStatus::ChangesRequested,
            $result['status']
        );
    }

    public function test_weighted_reputation_changes_consensus_weight(): void
    {
        [$version, $subject] = $this->makeSubmittedVersion();

        $expert = User::factory()
            ->teacher()
            ->create();

        UserSubjectReputation::create([
            'user_id' => $expert->id,
            'subject_id' => $subject->id,
            'score' => 350,
        ]);

        $this->service()->submitReview(
            $version,
            $expert,
            LessonReviewVerdict::Approve,
            $this->validScores()
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::ChangesRequested
        );

        $result = $this->service()->consensus($version);

        $this->assertSame(
            PedagogicalConsensusStatus::Approved,
            $result['status']
        );

        $this->assertSame(3.5, $result['total_weight']);
        $this->assertSame(2.5, $result['approve_weight']);
    }

    public function test_deleted_reviewer_does_not_reduce_historical_reviewer_count(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $first = $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitVerdict(
            $version,
            LessonReviewVerdict::ChangesRequested
        );

        $first->reviewer->delete();

        $result = $this->service()->consensus(
            $version->refresh()
        );

        $this->assertSame(3, $result['reviewer_count']);

        $this->assertSame(
            PedagogicalConsensusStatus::Approved,
            $result['status']
        );
    }

    private function submitVerdict(
        LessonVersion $version,
        LessonReviewVerdict $verdict
    ) {
        $reviewer = User::factory()
            ->teacher()
            ->create();

        return $this->service()->submitReview(
            $version,
            $reviewer,
            $verdict,
            $this->validScores()
        );
    }

    private function service(): PedagogicalReviewService
    {
        return app(PedagogicalReviewService::class);
    }

    /**
     * @return array{
     *     correctness_score: int,
     *     curriculum_alignment_score: int,
     *     clarity_score: int,
     *     pedagogical_value_score: int,
     *     difficulty_fit_score: int
     * }
     */
    private function validScores(): array
    {
        return [
            'correctness_score' => 5,
            'curriculum_alignment_score' => 5,
            'clarity_score' => 4,
            'pedagogical_value_score' => 5,
            'difficulty_fit_score' => 4,
        ];
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
