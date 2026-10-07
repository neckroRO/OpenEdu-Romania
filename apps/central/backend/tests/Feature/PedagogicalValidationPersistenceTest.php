<?php

namespace Tests\Feature;

use App\Enums\ReputationEventType;
use App\Models\ReputationEvent;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedagogicalValidationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reputation_event_survives_deleted_references(): void
    {
        $user = User::factory()->create();

        $subject = Subject::create([
            'code' => 'subject-'.uniqid(),
            'name' => 'Disciplină test',
            'status' => 'active',
        ]);

        $event = ReputationEvent::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'event_type' => ReputationEventType::ManualAdjustment,
            'points' => 5,
            'metadata' => [
                'reason' => 'test',
            ],
        ]);

        $user->delete();
        $subject->delete();

        $event->refresh();

        $this->assertNull($event->user_id);
        $this->assertNull($event->subject_id);

        $this->assertDatabaseHas('reputation_events', [
            'id' => $event->id,
            'event_type' => ReputationEventType::ManualAdjustment->value,
            'points' => 5,
        ]);
    }

    public function test_new_subject_reputation_starts_from_zero(): void
    {
        $user = User::factory()->create();

        $subject = Subject::create([
            'code' => 'subject-'.uniqid(),
            'name' => 'Disciplină test',
            'status' => 'active',
        ]);

        $reputation = UserSubjectReputation::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
        ])->refresh();

        $this->assertSame(0, $reputation->score);
        $this->assertSame(0, $reputation->contribution_count);
        $this->assertSame(0, $reputation->review_count);
    }
}
