<?php

namespace Tests\Feature\Api\V1;

use App\Models\Resource;
use App\Models\ResourceVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResourceVersionWorkflowEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_can_follow_submit_approve_publish_workflow(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $version = $this->makeDraftVersion($teacher);

        Sanctum::actingAs($teacher);

        $submitResponse = $this->postJson(
            "/api/v1/resource-versions/{$version->id}/submit"
        );

        $submitResponse
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('resource_versions', [
            'id' => $version->id,
            'status' => 'submitted',
        ]);

        $this->assertNotNull(
            ResourceVersion::findOrFail($version->id)->submitted_at
        );

        Sanctum::actingAs($moderator);

        $approveResponse = $this->postJson(
            "/api/v1/resource-versions/{$version->id}/approve"
        );

        $approveResponse
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('resource_versions', [
            'id' => $version->id,
            'status' => 'approved',
            'reviewed_by' => $moderator->id,
        ]);

        $this->assertNotNull(
            ResourceVersion::findOrFail($version->id)->reviewed_at
        );

        $publishResponse = $this->postJson(
            "/api/v1/resource-versions/{$version->id}/publish"
        );

        $publishResponse
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $publishedVersion = ResourceVersion::findOrFail(
            $version->id
        );

        $this->assertSame(
            'published',
            $publishedVersion->status->value
        );

        $this->assertNotNull(
            $publishedVersion->published_at
        );
    }

    public function test_submitted_version_can_be_rejected_with_feedback(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $version = $this->makeDraftVersion($teacher);

        Sanctum::actingAs($teacher);

        $this->postJson(
            "/api/v1/resource-versions/{$version->id}/submit"
        )->assertOk();

        Sanctum::actingAs($moderator);

        $response = $this->postJson(
            "/api/v1/resource-versions/{$version->id}/reject",
            [
                'review_note' =>
                    'Explicația trebuie simplificată pentru acest nivel.',
            ]
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath(
                'data.review_note',
                'Explicația trebuie simplificată pentru acest nivel.'
            );

        $this->assertDatabaseHas('resource_versions', [
            'id' => $version->id,
            'status' => 'rejected',
            'reviewed_by' => $moderator->id,
            'review_note' =>
                'Explicația trebuie simplificată pentru acest nivel.',
        ]);

        $rejectedVersion = ResourceVersion::findOrFail(
            $version->id
        );

        $this->assertNotNull(
            $rejectedVersion->reviewed_at
        );
    }

    public function test_rejected_version_can_be_returned_to_draft_for_revision(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $version = $this->makeDraftVersion($teacher);

        Sanctum::actingAs($teacher);

        $this->postJson(
            "/api/v1/resource-versions/{$version->id}/submit"
        )->assertOk();

        Sanctum::actingAs($moderator);

        $this->postJson(
            "/api/v1/resource-versions/{$version->id}/reject",
            [
                'review_note' => 'Clarifică exemplul de la final.',
            ]
        )->assertOk();

        Sanctum::actingAs($teacher);

        $response = $this->postJson(
            "/api/v1/resource-versions/{$version->id}/revise"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath(
                'data.review_note',
                'Clarifică exemplul de la final.'
            );

        $revisedVersion = ResourceVersion::findOrFail(
            $version->id
        );

        $this->assertSame(
            'draft',
            $revisedVersion->status->value
        );

        $this->assertNull(
            $revisedVersion->submitted_at
        );

        $this->assertSame(
            'Clarifică exemplul de la final.',
            $revisedVersion->review_note
        );

        $this->assertNotNull(
            $revisedVersion->reviewed_at
        );
    }

    public function test_invalid_editorial_transition_returns_conflict(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $version = $this->makeDraftVersion($teacher);

        Sanctum::actingAs($moderator);

        $response = $this->postJson(
            "/api/v1/resource-versions/{$version->id}/publish"
        );

        $response
            ->assertStatus(409)
            ->assertHeader('X-Request-ID')
            ->assertJsonPath(
                'error.code',
                'INVALID_EDITORIAL_TRANSITION'
            );

        $this->assertDatabaseHas('resource_versions', [
            'id' => $version->id,
            'status' => 'draft',
        ]);

        $this->assertNull(
            ResourceVersion::findOrFail($version->id)->published_at
        );
    }

    private function makeDraftVersion(User $owner): ResourceVersion
    {
        $resource = Resource::create([
            'code' => 'workflow-' . uniqid(),
            'type' => 'explanation',
            'status' => 'active',
        ]);

        return ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Resursă workflow',
            'summary' => 'Rezumat',
            'content' => 'Conținut educațional',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'draft',
            'created_by' => $owner->id,
        ]);
    }
}
