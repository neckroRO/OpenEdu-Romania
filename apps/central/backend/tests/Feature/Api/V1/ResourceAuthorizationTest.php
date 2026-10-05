<?php

namespace Tests\Feature\Api\V1;

use App\Models\Resource;
use App\Models\ResourceVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_editorial_endpoints(): void
    {
        $response = $this->postJson('/api/v1/resources', []);

        $response->assertUnauthorized();
    }

    public function test_teacher_cannot_update_another_users_draft(): void
    {
        $owner = User::factory()->create([
            'role' => 'teacher',
        ]);

        $otherTeacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $resource = $this->makeResource();

        ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Versiune inițială',
            'summary' => 'Rezumat',
            'content' => 'Conținut',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'draft',
            'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($otherTeacher);

        $response = $this->patchJson(
            "/api/v1/resources/{$resource->id}",
            [
                'title' => 'Modificare neautorizată',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('resource_versions', [
            'resource_id' => $resource->id,
            'title' => 'Modificare neautorizată',
        ]);
    }

    public function test_teacher_can_update_own_draft(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $resource = $this->makeResource();

        $version = ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Versiune proprie',
            'summary' => 'Rezumat',
            'content' => 'Conținut',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'draft',
            'created_by' => $teacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->patchJson(
            "/api/v1/resources/{$resource->id}",
            [
                'title' => 'Versiune actualizată',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('resource_versions', [
            'id' => $version->id,
            'title' => 'Versiune actualizată',
            'created_by' => $teacher->id,
        ]);
    }

    public function test_teacher_cannot_approve_a_submitted_version(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $resource = $this->makeResource();

        $version = ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Versiune trimisă',
            'summary' => 'Rezumat',
            'content' => 'Conținut',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'submitted',
            'created_by' => $teacher->id,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->postJson(
            "/api/v1/resource-versions/{$version->id}/approve"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('resource_versions', [
            'id' => $version->id,
            'status' => 'submitted',
        ]);
    }

    public function test_moderator_can_approve_a_submitted_version(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
        ]);

        $resource = $this->makeResource();

        $version = ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Versiune trimisă',
            'summary' => 'Rezumat',
            'content' => 'Conținut',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'submitted',
            'created_by' => $teacher->id,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($moderator);

        $response = $this->postJson(
            "/api/v1/resource-versions/{$version->id}/approve"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('resource_versions', [
            'id' => $version->id,
            'status' => 'approved',
            'reviewed_by' => $moderator->id,
        ]);
    }

    public function test_admin_bypasses_resource_version_policy(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $resource = $this->makeResource();

        $version = ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Draft profesor',
            'summary' => 'Rezumat',
            'content' => 'Conținut',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'draft',
            'created_by' => $teacher->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/resources/{$resource->id}",
            [
                'title' => 'Actualizat de administrator',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('resource_versions', [
            'id' => $version->id,
            'title' => 'Actualizat de administrator',
        ]);
    }

    private function makeResource(): Resource
    {
        return Resource::create([
            'code' => 'authorization-' . uniqid(),
            'type' => 'explanation',
            'status' => 'active',
        ]);
    }
}
