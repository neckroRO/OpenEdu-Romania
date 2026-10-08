<?php

namespace Tests\Feature;

use App\Models\CurriculumAlias;
use App\Models\CurriculumProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumCollaborationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_curriculum_proposal(): void
    {
        $user = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => null,
            'proposal_type' => 'create',
            'payload' => [
                'code' => 'astronomy',
                'name' => 'Astronomie',
            ],
            'reason' => 'Propunere de disciplină.',
            'status' => 'pending',
            'proposed_by' => $user->id,
        ]);

        $this->assertDatabaseHas('curriculum_proposals', [
            'id' => $proposal->id,
            'entity_type' => 'subject',
            'proposal_type' => 'create',
            'status' => 'pending',
            'proposed_by' => $user->id,
        ]);

        $this->assertSame(
            'Astronomie',
            $proposal->payload['name']
        );

        $this->assertTrue(
            $proposal->proposer->is($user)
        );
    }

    public function test_proposal_can_store_review_information(): void
    {
        $proposer = User::factory()->create();
        $reviewer = User::factory()->create();

        $proposal = CurriculumProposal::create([
            'entity_type' => 'subject',
            'entity_id' => 10,
            'proposal_type' => 'update',
            'payload' => [
                'name' => 'Matematică',
            ],
            'reason' => 'Corectare propusă.',
            'status' => 'approved',
            'proposed_by' => $proposer->id,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => 'Aprobat.',
        ]);

        $this->assertTrue(
            $proposal->reviewer->is($reviewer)
        );

        $this->assertNotNull(
            $proposal->reviewed_at
        );
    }

    public function test_curriculum_alias_can_be_created_and_approved(): void
    {
        $creator = User::factory()->create();
        $approver = User::factory()->create();

        $alias = CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => 15,
            'alias' => 'Mate',
            'normalized_alias' => 'mate',
            'source' => 'community',
            'status' => 'approved',
            'created_by' => $creator->id,
            'approved_by' => $approver->id,
        ]);

        $this->assertDatabaseHas('curriculum_aliases', [
            'id' => $alias->id,
            'entity_type' => 'subject',
            'entity_id' => 15,
            'alias' => 'Mate',
            'normalized_alias' => 'mate',
            'status' => 'approved',
        ]);

        $this->assertTrue(
            $alias->creator->is($creator)
        );

        $this->assertTrue(
            $alias->approver->is($approver)
        );
    }

    public function test_same_normalized_alias_cannot_be_duplicated_for_same_entity(): void
    {
        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => 15,
            'alias' => 'Mate',
            'normalized_alias' => 'mate',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => 15,
            'alias' => 'MATE',
            'normalized_alias' => 'mate',
            'source' => 'community',
            'status' => 'pending',
        ]);
    }

    public function test_same_alias_can_reference_different_entities(): void
    {
        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => 15,
            'alias' => 'Științe',
            'normalized_alias' => 'stiinte',
            'source' => 'community',
            'status' => 'approved',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'curriculum_area',
            'entity_id' => 3,
            'alias' => 'Științe',
            'normalized_alias' => 'stiinte',
            'source' => 'community',
            'status' => 'approved',
        ]);

        $this->assertDatabaseCount(
            'curriculum_aliases',
            2
        );
    }
}
