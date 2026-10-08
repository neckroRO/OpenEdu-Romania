<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Enums\UserRole;
use App\Models\CurriculumProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurriculumProposalEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_curriculum_proposal(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'subject',
                'proposal_type' => 'create',
                'payload' => [
                    'code' => 'astronomy',
                    'name' => 'Astronomie',
                ],
                'reason' => 'Lipsește din structură.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.entity_type',
                'subject'
            )
            ->assertJsonPath(
                'data.proposal_type',
                'create'
            )
            ->assertJsonPath(
                'data.status',
                'pending'
            )
            ->assertJsonPath(
                'data.proposed_by',
                $teacher->id
            );

        $this->assertDatabaseHas(
            'curriculum_proposals',
            [
                'entity_type' => 'subject',
                'proposal_type' => 'create',
                'status' => 'pending',
                'proposed_by' => $teacher->id,
            ]
        );
    }

    public function test_teacher_can_only_list_own_proposals(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        $otherTeacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        CurriculumProposal::create([
            'entity_type' => 'subject',
            'proposal_type' => CurriculumProposalType::Create,
            'payload' => ['name' => 'Astronomie'],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        CurriculumProposal::create([
            'entity_type' => 'subject',
            'proposal_type' => CurriculumProposalType::Create,
            'payload' => ['name' => 'Geologie'],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $otherTeacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->getJson(
            '/api/v1/curriculum/proposals'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.proposed_by',
                $teacher->id
            );
    }

    public function test_moderator_can_list_all_proposals(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);

        $teacherA = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        $teacherB = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        foreach ([$teacherA, $teacherB] as $teacher) {
            CurriculumProposal::create([
                'entity_type' => 'subject',
                'proposal_type' =>
                    CurriculumProposalType::Create,
                'payload' => ['name' => 'Test'],
                'status' =>
                    CurriculumProposalStatus::Pending,
                'proposed_by' => $teacher->id,
            ]);
        }

        Sanctum::actingAs($moderator);

        $this->getJson('/api/v1/curriculum/proposals')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_list_all_proposals(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        CurriculumProposal::create([
            'entity_type' => 'subject',
            'proposal_type' => CurriculumProposalType::Create,
            'payload' => ['name' => 'Astronomie'],
            'status' => CurriculumProposalStatus::Pending,
            'proposed_by' => $teacher->id,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/curriculum/proposals')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_learner_cannot_create_proposal(): void
    {
        $learner = User::factory()->create([
            'role' => UserRole::Learner,
        ]);

        Sanctum::actingAs($learner);

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'subject',
                'proposal_type' => 'create',
                'payload' => [
                    'name' => 'Astronomie',
                ],
            ]
        )->assertForbidden();
    }

    public function test_create_proposal_rejects_entity_id(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'subject',
                'entity_id' => 12,
                'proposal_type' => 'create',
                'payload' => [
                    'name' => 'Astronomie',
                ],
            ]
        )->assertUnprocessable();
    }

    public function test_merge_candidate_requires_target_entity(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'subject',
                'entity_id' => 10,
                'proposal_type' => 'merge_candidate',
                'payload' => [
                    'reason' => 'Duplicate',
                ],
            ]
        )->assertUnprocessable();
    }

    public function test_unauthenticated_user_cannot_access_proposals(): void
    {
        $this->getJson(
            '/api/v1/curriculum/proposals'
        )->assertUnauthorized();

        $this->postJson(
            '/api/v1/curriculum/proposals',
            []
        )->assertUnauthorized();
    }
}
