<?php

namespace Tests\Feature\Api\V1;

use App\Enums\LessonReviewVerdict;
use App\Enums\LessonVersionStatus;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PedagogicalReviewEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_endpoint_requires_authentication(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/reviews",
            $this->reviewPayload()
        )
            ->assertStatus(401)
            ->assertJsonPath(
                'error.code',
                'UNAUTHENTICATED'
            );
    }

    public function test_teacher_can_review_another_teachers_submitted_lesson(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $reviewer = User::factory()
            ->teacher()
            ->create();

        Sanctum::actingAs($reviewer);

        $response = $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/reviews",
            $this->reviewPayload()
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.lesson_version_id',
                $version->id
            )
            ->assertJsonPath(
                'data.review_round',
                1
            )
            ->assertJsonPath(
                'data.reviewer_id',
                $reviewer->id
            )
            ->assertJsonPath(
                'data.verdict',
                'approve'
            )
            ->assertJsonPath(
                'data.scores.correctness',
                5
            )
            ->assertJsonPath(
                'data.scores.curriculum_alignment',
                5
            );

        $this->assertDatabaseHas(
            'lesson_version_reviews',
            [
                'lesson_version_id' => $version->id,
                'reviewer_id' => $reviewer->id,
                'review_round' => 1,
                'verdict' => 'approve',
            ]
        );
    }

    public function test_author_cannot_review_own_lesson(): void
    {
        [$version, , $author] =
            $this->makeSubmittedVersion();

        Sanctum::actingAs($author);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/reviews",
            $this->reviewPayload()
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'PEDAGOGICAL_REVIEW_CONFLICT'
            );

        $this->assertDatabaseCount(
            'lesson_version_reviews',
            0
        );
    }

    public function test_learner_cannot_submit_pedagogical_review(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $learner = User::factory()->create();

        Sanctum::actingAs($learner);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/reviews",
            $this->reviewPayload()
        )
            ->assertStatus(403)
            ->assertJsonPath(
                'error.code',
                'FORBIDDEN'
            );
    }

    public function test_negative_review_requires_comment(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $reviewer = User::factory()
            ->teacher()
            ->create();

        Sanctum::actingAs($reviewer);

        $payload = $this->reviewPayload(
            LessonReviewVerdict::ChangesRequested
        );

        unset($payload['comment']);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/reviews",
            $payload
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => [
                            'comment',
                        ],
                    ],
                ],
            ]);
    }

    public function test_same_reviewer_cannot_review_same_round_twice(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $reviewer = User::factory()
            ->teacher()
            ->create();

        Sanctum::actingAs($reviewer);

        $url =
            "/api/v1/lesson-versions/{$version->id}/reviews";

        $this->postJson(
            $url,
            $this->reviewPayload()
        )->assertCreated();

        $this->postJson(
            $url,
            $this->reviewPayload()
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'PEDAGOGICAL_REVIEW_CONFLICT'
            );

        $this->assertSame(
            1,
            $version->reviews()
                ->where('review_round', 1)
                ->count()
        );
    }

    public function test_moderator_can_read_reviews_and_consensus(): void
    {
        [$version] = $this->makeSubmittedVersion();

        $this->submitApiReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitApiReview(
            $version,
            LessonReviewVerdict::Approve
        );

        $this->submitApiReview(
            $version,
            LessonReviewVerdict::ChangesRequested
        );

        $moderator = User::factory()
            ->moderator()
            ->create();

        Sanctum::actingAs($moderator);

        $this->getJson(
            "/api/v1/lesson-versions/{$version->id}/reviews"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.lesson_version_id',
                $version->id
            )
            ->assertJsonPath(
                'data.review_round',
                1
            )
            ->assertJsonCount(
                3,
                'data.reviews'
            );

        $this->getJson(
            "/api/v1/lesson-versions/{$version->id}/consensus"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            )
            ->assertJsonPath(
                'data.reviewer_count',
                3
            )
            ->assertJsonPath(
                'data.weights.total',
                3
            )
            ->assertJsonPath(
                'data.weights.approve',
                2
            )
            ->assertJsonPath(
                'data.weights.changes_requested',
                1
            )
            ->assertJsonPath(
                'data.weights.reject',
                0
            );
    }

    public function test_approval_requires_reviews_and_publication_updates_reputation(): void
    {
        [$version, $subject, $author] =
            $this->makeSubmittedVersion();

        $moderator = User::factory()
            ->moderator()
            ->create();

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/approve"
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'error.code',
                'PEDAGOGICAL_VALIDATION_CONFLICT'
            );

        $version->refresh();

        $this->assertSame(
            LessonVersionStatus::Submitted,
            $version->status
        );

        $reviewers = [];

        for ($i = 0; $i < 3; $i++) {
            $reviewers[] = $this->submitApiReview(
                $version,
                LessonReviewVerdict::Approve
            );
        }

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/approve"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            );

        foreach ($reviewers as $reviewer) {
            $reputation = UserSubjectReputation::query()
                ->where('user_id', $reviewer->id)
                ->where('subject_id', $subject->id)
                ->firstOrFail();

            $this->assertSame(
                3,
                $reputation->score
            );

            $this->assertSame(
                1,
                $reputation->review_count
            );
        }

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/publish"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'published'
            );

        $authorReputation =
            UserSubjectReputation::query()
                ->where('user_id', $author->id)
                ->where('subject_id', $subject->id)
                ->firstOrFail();

        $this->assertSame(
            10,
            $authorReputation->score
        );

        $this->assertSame(
            1,
            $authorReputation->contribution_count
        );
    }

    private function submitApiReview(
        LessonVersion $version,
        LessonReviewVerdict $verdict
    ): User {
        $reviewer = User::factory()
            ->teacher()
            ->create();

        Sanctum::actingAs($reviewer);

        $this->postJson(
            "/api/v1/lesson-versions/{$version->id}/reviews",
            $this->reviewPayload($verdict)
        )->assertCreated();

        return $reviewer;
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewPayload(
        LessonReviewVerdict $verdict =
            LessonReviewVerdict::Approve
    ): array {
        $payload = [
            'verdict' => $verdict->value,
            'correctness_score' => 5,
            'curriculum_alignment_score' => 5,
            'clarity_score' => 4,
            'pedagogical_value_score' => 5,
            'difficulty_fit_score' => 4,
        ];

        if (
            $verdict
            !== LessonReviewVerdict::Approve
        ) {
            $payload['comment'] =
                'Sunt necesare ajustări pedagogice.';
        }

        return $payload;
    }

    /**
     * @return array{LessonVersion, Subject, User}
     */
    private function makeSubmittedVersion(): array
    {
        $author = User::factory()
            ->teacher()
            ->create();

        $suffix = uniqid();

        $curriculum = Curriculum::create([
            'code' => 'RO-'.$suffix,
            'name' => 'Curriculum test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $curriculumVersion = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-'.$suffix,
            'status' => 'active',
        ]);

        $educationLevel = EducationLevel::create([
            'code' => 'grade-'.$suffix,
            'name' => 'Clasă test',
            'ordinal' => 7,
            'education_stage' => 'gimnazial',
        ]);

        $subject = Subject::create([
            'code' => 'subject-'.$suffix,
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
            'code' => 'lesson-'.$suffix,
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
