<?php

namespace Tests\Feature;

use App\Enums\CurriculumProposalStatus;
use App\Enums\CurriculumProposalType;
use App\Models\User;
use App\Services\Curriculum\CurriculumProposalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumProposalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_proposal_has_no_entity_reference(): void
    {
        $user = User::factory()->create();

        $proposal = app(CurriculumProposalService::class)->create(
            $user,
            'subject',
            CurriculumProposalType::Create,
            [
                'code' => 'astronomy',
                'name' => 'Astronomie',
            ],
            null,
            'Propunere disciplină.'
        );

        $this->assertNull($proposal->entity_id);

        $this->assertSame(
            CurriculumProposalStatus::Pending,
            $proposal->status
        );
    }

    public function test_create_proposal_cannot_reference_existing_entity(): void
    {
        $user = User::factory()->create();

        $this->expectException(DomainException::class);

        app(CurriculumProposalService::class)->create(
            $user,
            'subject',
            CurriculumProposalType::Create,
            [
                'name' => 'Astronomie',
            ],
            10
        );
    }

    public function test_update_proposal_requires_existing_entity(): void
    {
        $user = User::factory()->create();

        $this->expectException(DomainException::class);

        app(CurriculumProposalService::class)->create(
            $user,
            'subject',
            CurriculumProposalType::Update,
            [
                'name' => 'Matematică',
            ]
        );
    }

    public function test_alias_proposal_requires_existing_entity(): void
    {
        $user = User::factory()->create();

        $this->expectException(DomainException::class);

        app(CurriculumProposalService::class)->create(
            $user,
            'subject',
            CurriculumProposalType::Alias,
            [
                'alias' => 'Mate',
            ]
        );
    }

    public function test_merge_candidate_requires_existing_entity(): void
    {
        $user = User::factory()->create();

        $this->expectException(DomainException::class);

        app(CurriculumProposalService::class)->create(
            $user,
            'subject',
            CurriculumProposalType::MergeCandidate,
            [
                'duplicate_entity_id' => 11,
            ]
        );
    }

    public function test_proposal_payload_cannot_be_empty(): void
    {
        $user = User::factory()->create();

        $this->expectException(DomainException::class);

        app(CurriculumProposalService::class)->create(
            $user,
            'subject',
            CurriculumProposalType::Update,
            [],
            10
        );
    }

    public function test_non_create_proposal_is_stored_as_pending(): void
    {
        $user = User::factory()->create();

        $proposal = app(CurriculumProposalService::class)->create(
            $user,
            'subject',
            CurriculumProposalType::Update,
            [
                'name' => 'Matematică',
            ],
            10
        );

        $this->assertSame(
            CurriculumProposalType::Update,
            $proposal->proposal_type
        );

        $this->assertSame(
            CurriculumProposalStatus::Pending,
            $proposal->status
        );

        $this->assertSame(
            $user->id,
            $proposal->proposed_by
        );
    }
}
