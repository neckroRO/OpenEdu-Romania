<?php

namespace Tests\Feature\Api\V1;

use App\Models\Concept;
use App\Models\Resource;
use App\Models\ResourceConcept;
use App\Models\ResourceVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModerationQueueEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_view_moderation_queue(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $concept = Concept::create([
            'code' => 'fractions',
            'title' => 'Fracții',
            'description' => null,
            'status' => 'active',
        ]);

        $submittedResource = Resource::create([
            'code' => 'submitted-resource',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        $submitted = ResourceVersion::create([
            'resource_id' => $submittedResource->id,
            'version_number' => 1,
            'title' => 'Resursă trimisă',
            'summary' => 'Rezumat',
            'content' => 'Conținut pentru review',
            'language_code' => 'ro',
            'difficulty_level' => 2,
            'complexity_level' => 3,
            'status' => 'submitted',
            'submitted_at' => now(),
            'created_by' => $teacher->id,
        ]);

        ResourceConcept::create([
            'resource_id' => $submittedResource->id,
            'concept_id' => $concept->id,
            'is_primary' => true,
            'display_order' => 1,
        ]);

        $approvedResource = Resource::create([
            'code' => 'approved-resource',
            'type' => 'lesson',
            'status' => 'active',
        ]);

        ResourceVersion::create([
            'resource_id' => $approvedResource->id,
            'version_number' => 1,
            'title' => 'Resursă aprobată',
            'summary' => null,
            'content' => 'Pregătită pentru publicare',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'approved',
            'submitted_at' => now(),
            'reviewed_by' => $moderator->id,
            'reviewed_at' => now(),
            'created_by' => $teacher->id,
        ]);

        $draftResource = Resource::create([
            'code' => 'draft-resource',
            'type' => 'exercise',
            'status' => 'active',
        ]);

        ResourceVersion::create([
            'resource_id' => $draftResource->id,
            'version_number' => 1,
            'title' => 'Draft invizibil',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'draft',
            'created_by' => $teacher->id,
        ]);

        Sanctum::actingAs($moderator);

        $response = $this->getJson(
            '/api/v1/editor/moderation'
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'data.0.id',
                $submitted->id
            )
            ->assertJsonPath(
                'data.0.status',
                'submitted'
            )
            ->assertJsonPath(
                'data.0.creator.id',
                $teacher->id
            )
            ->assertJsonPath(
                'data.0.creator.name',
                $teacher->name
            )
            ->assertJsonPath(
                'data.0.concept.title',
                'Fracții'
            )
            ->assertJsonPath(
                'data.0.content',
                'Conținut pentru review'
            );
    }

    public function test_teacher_cannot_view_moderation_queue(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        Sanctum::actingAs($teacher);

        $this->getJson('/api/v1/editor/moderation')
            ->assertForbidden();
    }

    public function test_admin_can_view_moderation_queue(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/editor/moderation')
            ->assertOk();
    }

    public function test_guest_cannot_view_moderation_queue(): void
    {
        $this->getJson('/api/v1/editor/moderation')
            ->assertUnauthorized();
    }
}
