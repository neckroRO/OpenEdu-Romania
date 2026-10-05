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

class EditorialResourceIndexEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_list_own_editorial_resources(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $otherTeacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $concept = Concept::create([
            'code' => 'fractions',
            'title' => 'Fracții',
            'description' => 'Introducere în fracții.',
            'status' => 'active',
        ]);

        $ownResource = Resource::create([
            'code' => 'own-resource',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        $ownVersion = ResourceVersion::create([
            'resource_id' => $ownResource->id,
            'version_number' => 1,
            'title' => 'Resursa mea',
            'summary' => 'Rezumat',
            'content' => 'Conținut',
            'language_code' => 'ro',
            'difficulty_level' => 2,
            'complexity_level' => 3,
            'status' => 'draft',
            'created_by' => $teacher->id,
        ]);

        ResourceConcept::create([
            'resource_id' => $ownResource->id,
            'concept_id' => $concept->id,
            'is_primary' => true,
            'display_order' => 1,
        ]);

        $otherResource = Resource::create([
            'code' => 'other-resource',
            'type' => 'exercise',
            'status' => 'active',
        ]);

        ResourceVersion::create([
            'resource_id' => $otherResource->id,
            'version_number' => 1,
            'title' => 'Altă resursă',
            'summary' => null,
            'content' => 'Alt conținut',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'draft',
            'created_by' => $otherTeacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->getJson(
            '/api/v1/editor/resources'
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $ownResource->id
            )
            ->assertJsonPath(
                'data.0.version.id',
                $ownVersion->id
            )
            ->assertJsonPath(
                'data.0.version.title',
                'Resursa mea'
            )
            ->assertJsonPath(
                'data.0.version.status',
                'draft'
            )
            ->assertJsonPath(
                'data.0.concept.id',
                $concept->id
            )
            ->assertJsonPath(
                'data.0.concept.title',
                'Fracții'
            );
    }

    public function test_it_returns_latest_version_owned_by_user(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $resource = Resource::create([
            'code' => 'versioned-resource',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Versiunea 1',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $teacher->id,
        ]);

        $latest = ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 2,
            'title' => 'Versiunea 2',
            'language_code' => 'ro',
            'difficulty_level' => 2,
            'complexity_level' => 2,
            'status' => 'draft',
            'created_by' => $teacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $this->getJson('/api/v1/editor/resources')
            ->assertOk()
            ->assertJsonPath(
                'data.0.version.id',
                $latest->id
            )
            ->assertJsonPath(
                'data.0.version.version_number',
                2
            )
            ->assertJsonPath(
                'data.0.version.status',
                'draft'
            );
    }

    public function test_learner_cannot_access_editorial_resources(): void
    {
        $learner = User::factory()->create([
            'role' => 'learner',
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/v1/editor/resources')
            ->assertForbidden();
    }

    public function test_guest_cannot_access_editorial_resources(): void
    {
        $this->getJson('/api/v1/editor/resources')
            ->assertUnauthorized();
    }
}
