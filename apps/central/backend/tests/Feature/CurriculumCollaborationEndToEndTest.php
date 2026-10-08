<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Curriculum;
use App\Models\CurriculumAlias;
use App\Models\CurriculumFrameworkVariant;
use App\Models\CurriculumSubject;
use App\Models\CurriculumVersion;
use App\Models\EducationLevel;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubjectReputation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurriculumCollaborationEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_candidate_flows_end_to_end_through_api(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
        ]);

        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);

        $reputationUser = User::factory()->create();

        $source = Subject::create([
            'code' => 'math-community',
            'name' => 'Mate',
            'status' => 'active',
        ]);

        $target = Subject::create([
            'code' => 'mathematics',
            'name' => 'Matematică',
            'status' => 'active',
        ]);

        $curriculum = Curriculum::create([
            'code' => 'TEST-COLLAB',
            'name' => 'Curriculum colaborativ test',
            'country_code' => 'RO',
            'status' => 'active',
        ]);

        $version = CurriculumVersion::create([
            'curriculum_id' => $curriculum->id,
            'version' => 'TEST-1',
            'valid_from' => '2026-09-01',
            'status' => 'active',
        ]);

        $level = EducationLevel::create([
            'code' => 'test-grade',
            'name' => 'Clasa test',
            'ordinal' => 1,
            'education_stage' => 'test',
        ]);

        $variant = CurriculumFrameworkVariant::create([
            'curriculum_version_id' => $version->id,
            'code' => 'standard-test',
            'name' => 'Variantă test',
            'status' => 'active',
        ]);

        $curriculumSubject = CurriculumSubject::create([
            'curriculum_version_id' => $version->id,
            'curriculum_framework_variant_id' => $variant->id,
            'education_level_id' => $level->id,
            'subject_id' => $source->id,
            'component' => 'TC',
            'display_order' => 1,
            'status' => 'active',
        ]);

        CurriculumAlias::create([
            'entity_type' => 'subject',
            'entity_id' => $source->id,
            'alias' => 'Mate alternativ',
            'normalized_alias' => 'mate alternativ',
            'source' => 'community',
            'status' => 'approved',
        ]);

        UserSubjectReputation::create([
            'user_id' => $reputationUser->id,
            'subject_id' => $source->id,
            'score' => 10,
            'contribution_count' => 2,
            'review_count' => 1,
        ]);

        UserSubjectReputation::create([
            'user_id' => $reputationUser->id,
            'subject_id' => $target->id,
            'score' => 20,
            'contribution_count' => 3,
            'review_count' => 4,
        ]);

        /*
         * 1. Teacher proposes the merge.
         */
        Sanctum::actingAs($teacher);

        $proposalResponse = $this->postJson(
            '/api/v1/curriculum/proposals',
            [
                'entity_type' => 'subject',
                'entity_id' => $source->id,
                'proposal_type' => 'merge_candidate',
                'payload' => [
                    'duplicate_entity_id' => $target->id,
                ],
                'reason' => 'Aceeași materie, denumire alternativă.',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath(
                'data.proposal_type',
                'merge_candidate'
            );

        $proposalId = $proposalResponse->json('data.id');

        /*
         * 2. Moderator previews the real impact.
         */
        Sanctum::actingAs($moderator);

        $this->getJson(
            "/api/v1/curriculum/proposals/{$proposalId}/merge-preview"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.source_subject_id',
                $source->id
            )
            ->assertJsonPath(
                'data.target_subject_id',
                $target->id
            )
            ->assertJsonPath(
                'data.curriculum_subjects_to_move',
                1
            )
            ->assertJsonPath(
                'data.curriculum_subject_collisions',
                0
            )
            ->assertJsonPath(
                'data.aliases_to_move',
                1
            )
            ->assertJsonPath(
                'data.reputations_to_consolidate',
                1
            )
            ->assertJsonPath(
                'data.blocked',
                false
            );

        /*
         * 3. Moderator confirms the merge.
         */
        $this->postJson(
            "/api/v1/curriculum/proposals/{$proposalId}/confirm-merge",
            [
                'note' => 'Duplicat confirmat E2E.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.proposal.status',
                'merged'
            )
            ->assertJsonPath(
                'data.merge.source_entity_id',
                $source->id
            )
            ->assertJsonPath(
                'data.merge.target_entity_id',
                $target->id
            );

        /*
         * 4. Verify all persistent effects.
         */
        $this->assertSame(
            'merged',
            $source->refresh()->status
        );

        $this->assertSame(
            $target->id,
            $curriculumSubject->refresh()->subject_id
        );

        $this->assertDatabaseHas(
            'curriculum_aliases',
            [
                'entity_type' => 'subject',
                'entity_id' => $target->id,
                'normalized_alias' => 'mate alternativ',
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_aliases',
            [
                'entity_type' => 'subject',
                'entity_id' => $target->id,
                'normalized_alias' => 'mate',
                'source' => 'system',
                'status' => 'approved',
            ]
        );

        $reputation = UserSubjectReputation::query()
            ->where('user_id', $reputationUser->id)
            ->where('subject_id', $target->id)
            ->firstOrFail();

        $this->assertSame(30, $reputation->score);
        $this->assertSame(
            5,
            $reputation->contribution_count
        );
        $this->assertSame(
            5,
            $reputation->review_count
        );

        $this->assertDatabaseMissing(
            'user_subject_reputations',
            [
                'user_id' => $reputationUser->id,
                'subject_id' => $source->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_audit_events',
            [
                'event_type' => 'proposal_merged',
                'proposal_id' => $proposalId,
                'actor_id' => $moderator->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_audit_events',
            [
                'event_type' => 'merge_completed',
                'proposal_id' => $proposalId,
                'actor_id' => $moderator->id,
                'entity_type' => 'subject',
                'entity_id' => $target->id,
            ]
        );

        $this->assertDatabaseHas(
            'curriculum_entity_merges',
            [
                'entity_type' => 'subject',
                'source_entity_id' => $source->id,
                'target_entity_id' => $target->id,
                'merged_by' => $moderator->id,
                'reason' => 'Duplicat confirmat E2E.',
            ]
        );
    }
}
