<?php

namespace Tests\Feature\Api\V1;

use App\Models\Concept;
use App\Models\Resource;
use App\Models\ResourceConcept;
use App\Models\ResourceVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateResourceEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_the_current_draft_version(): void
    {
        $concept = Concept::create([
            'code' => 'fractions',
            'title' => 'Fracții',
            'description' => 'Introducere în fracții.',
            'status' => 'active',
        ]);

        $resource = Resource::create([
            'code' => 'fractions-introduction',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        $draft = ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Titlu inițial',
            'summary' => 'Rezumat inițial',
            'content' => 'Conținut inițial',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'draft',
        ]);

        ResourceConcept::create([
            'resource_id' => $resource->id,
            'concept_id' => $concept->id,
            'is_primary' => true,
            'display_order' => 1,
        ]);

        $response = $this->patchJson(
            "/api/v1/resources/{$resource->id}",
            [
                'title' => 'Titlu actualizat',
                'summary' => 'Rezumat actualizat',
                'difficulty_level' => 3,
            ]
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath(
                'data.id',
                $resource->id
            )
            ->assertJsonPath(
                'data.version.id',
                $draft->id
            )
            ->assertJsonPath(
                'data.version.version_number',
                1
            )
            ->assertJsonPath(
                'data.version.title',
                'Titlu actualizat'
            )
            ->assertJsonPath(
                'data.version.summary',
                'Rezumat actualizat'
            )
            ->assertJsonPath(
                'data.version.difficulty_level',
                3
            );

        $this->assertDatabaseHas('resource_versions', [
            'id' => $draft->id,
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Titlu actualizat',
            'summary' => 'Rezumat actualizat',
            'difficulty_level' => 3,
            'status' => 'draft',
        ]);
    }

    public function test_it_rejects_updates_when_resource_has_no_draft_version(): void
    {
        $resource = Resource::create([
            'code' => 'published-resource',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Versiune publicată',
            'summary' => 'Rezumat',
            'content' => 'Conținut publicat',
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->patchJson(
            "/api/v1/resources/{$resource->id}",
            [
                'title' => 'Titlu care nu trebuie salvat',
            ]
        );

        $response
            ->assertStatus(409)
            ->assertHeader('X-Request-ID')
            ->assertJsonPath(
                'error.code',
                'RESOURCE_NOT_EDITABLE'
            )
            ->assertJsonPath(
                'error.message',
                'Doar o versiune draft poate fi editată.'
            );

        $this->assertDatabaseMissing('resource_versions', [
            'resource_id' => $resource->id,
            'title' => 'Titlu care nu trebuie salvat',
        ]);
    }
}
