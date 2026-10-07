<?php

namespace Tests\Feature;

use App\Enums\ReputationEventType;
use App\Models\ReputationEvent;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use App\Services\ReputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReputationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_lesson_adds_ten_points_and_contribution(): void
    {
        [$user, $subject] = $this->makeUserAndSubject();

        $event = $this->service()->record(
            $user,
            $subject,
            ReputationEventType::LessonPublished,
            'lesson-published:1'
        );

        $this->assertSame(10, $event->points);

        $reputation = $this->reputation($user, $subject);

        $this->assertSame(10, $reputation->score);
        $this->assertSame(1, $reputation->contribution_count);
        $this->assertSame(0, $reputation->review_count);
    }

    public function test_confirmed_review_adds_three_points_and_review_count(): void
    {
        [$user, $subject] = $this->makeUserAndSubject();

        $this->service()->record(
            $user,
            $subject,
            ReputationEventType::ReviewConfirmed,
            'review-confirmed:1'
        );

        $reputation = $this->reputation($user, $subject);

        $this->assertSame(3, $reputation->score);
        $this->assertSame(0, $reputation->contribution_count);
        $this->assertSame(1, $reputation->review_count);
    }

    public function test_contradicted_review_subtracts_three_points(): void
    {
        [$user, $subject] = $this->makeUserAndSubject();

        $this->service()->record(
            $user,
            $subject,
            ReputationEventType::ReviewContradicted,
            'review-contradicted:1'
        );

        $reputation = $this->reputation($user, $subject);

        $this->assertSame(-3, $reputation->score);
        $this->assertSame(1, $reputation->review_count);
    }

    public function test_withdrawn_lesson_subtracts_eight_without_erasing_contribution_history(): void
    {
        [$user, $subject] = $this->makeUserAndSubject();

        $service = $this->service();

        $service->record(
            $user,
            $subject,
            ReputationEventType::LessonPublished,
            'lesson-published:1'
        );

        $service->record(
            $user,
            $subject,
            ReputationEventType::LessonWithdrawn,
            'lesson-withdrawn:1'
        );

        $reputation = $this->reputation($user, $subject);

        $this->assertSame(2, $reputation->score);
        $this->assertSame(1, $reputation->contribution_count);
    }

    public function test_manual_adjustment_uses_explicit_points(): void
    {
        [$user, $subject] = $this->makeUserAndSubject();

        $event = $this->service()->record(
            $user,
            $subject,
            ReputationEventType::ManualAdjustment,
            'manual:1',
            metadata: [
                'reason' => 'Corecție administrativă.',
            ],
            manualPoints: 7
        );

        $this->assertSame(7, $event->points);

        $this->assertSame(
            ['reason' => 'Corecție administrativă.'],
            $event->metadata
        );

        $this->assertSame(
            7,
            $this->reputation($user, $subject)->score
        );
    }

    public function test_manual_adjustment_requires_explicit_points(): void
    {
        [$user, $subject] = $this->makeUserAndSubject();

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->service()->record(
            $user,
            $subject,
            ReputationEventType::ManualAdjustment,
            'manual:missing-points'
        );
    }

    public function test_fixed_event_type_rejects_manual_points(): void
    {
        [$user, $subject] = $this->makeUserAndSubject();

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->service()->record(
            $user,
            $subject,
            ReputationEventType::LessonPublished,
            'lesson-published:invalid',
            manualPoints: 99
        );
    }

    public function test_same_event_key_is_idempotent(): void
    {
        [$user, $subject] = $this->makeUserAndSubject();

        $service = $this->service();

        $first = $service->record(
            $user,
            $subject,
            ReputationEventType::LessonPublished,
            'lesson-published:42'
        );

        $second = $service->record(
            $user,
            $subject,
            ReputationEventType::LessonPublished,
            'lesson-published:42'
        );

        $this->assertSame($first->id, $second->id);

        $this->assertSame(
            1,
            ReputationEvent::query()
                ->where(
                    'event_key',
                    'lesson-published:42'
                )
                ->count()
        );

        $reputation = $this->reputation($user, $subject);

        $this->assertSame(10, $reputation->score);
        $this->assertSame(1, $reputation->contribution_count);
    }

    public function test_reputation_is_independent_for_each_subject(): void
    {
        $user = User::factory()
            ->teacher()
            ->create();

        $math = $this->makeSubject('math');
        $physics = $this->makeSubject('physics');

        $service = $this->service();

        $service->record(
            $user,
            $math,
            ReputationEventType::LessonPublished,
            'math:lesson:1'
        );

        $service->record(
            $user,
            $physics,
            ReputationEventType::ReviewConfirmed,
            'physics:review:1'
        );

        $this->assertSame(
            10,
            $this->reputation($user, $math)->score
        );

        $this->assertSame(
            3,
            $this->reputation($user, $physics)->score
        );
    }

    public function test_same_event_key_cannot_describe_different_event(): void
    {
        [$user, $subject] = $this->makeUserAndSubject();

        $service = $this->service();

        $service->record(
            $user,
            $subject,
            ReputationEventType::LessonPublished,
            'shared:event:key'
        );

        $otherUser = User::factory()
            ->teacher()
            ->create();

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->record(
            $otherUser,
            $subject,
            ReputationEventType::LessonPublished,
            'shared:event:key'
        );
    }

    private function service(): ReputationService
    {
        return app(ReputationService::class);
    }

    /**
     * @return array{User, Subject}
     */
    private function makeUserAndSubject(): array
    {
        return [
            User::factory()->teacher()->create(),
            $this->makeSubject('subject'),
        ];
    }

    private function makeSubject(string $prefix): Subject
    {
        return Subject::create([
            'code' => $prefix.'-'.uniqid(),
            'name' => 'Disciplină test',
            'status' => 'active',
        ]);
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
}
