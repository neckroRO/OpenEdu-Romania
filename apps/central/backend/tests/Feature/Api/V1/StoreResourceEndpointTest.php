<?php

namespace Tests\Feature\Api\V1;

use App\Models\Concept;
use App\Models\Resource;
use App\Models\ResourceConcept;
use App\Models\ResourceVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreResourceEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_resource_with_initial_draft_version_and_primary_concept(): void
    {
        $concept = Concept::create([
            'code' => 'fractions',
            'title' => 'Fracții',
            'description' => 'Introducere în fracții.',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/resources', [
            'concept_id' => $concept->id,
            'type' => 'explanation',
            'title' => 'Fracțiile explicate simplu',
            'summary' => 'O introducere intuitivă în fracții.',
            'content' => 'Conținut educațional pentru fracții.',
            'language_code' => 'ro',
            'difficulty_level' => 2,
            'complexity_level' => 1,
        ]);

        $response
            ->assertCreated()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath(
                'data.type',
                'explanation'
            )
            ->assertJsonPath(
                'data.version.version_number',
                1
            )
            ->assertJsonPath(
                'data.version.title',
                'Fracțiile explicate simplu'
            )
            ->assertJsonPath(
                'data.version.summary',
                'O introducere intuitivă în fracții.'
            )
            ->assertJsonPath(
                'data.version.content',
                'Conținut educațional pentru fracții.'
            )
            ->assertJsonPath(
                'data.version.language_code',
                'ro'
            )
            ->assertJsonPath(
                'data.version.difficulty_level',
                2
            )
            ->assertJsonPath(
                'data.version.complexity_level',
                1
            );

        $resourceId = $response->json('data.id');

        $this->assertDatabaseHas('resources', [
            'id' => $resourceId,
            'type' => 'explanation',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('resource_versions', [
            'resource_id' => $resourceId,
            'version_number' => 1,
            'title' => 'Fracțiile explicate simplu',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('resource_concepts', [
            'resource_id' => $resourceId,
            'concept_id' => $concept->id,
            'is_primary' => 1,
            'display_order' => 1,
        ]);

        $this->assertSame(
            1,
            Resource::query()
                ->whereKey($resourceId)
                ->count()
        );

        $this->assertSame(
            1,
            ResourceVersion::query()
                ->where('resource_id', $resourceId)
                ->count()
        );

        $this->assertSame(
            1,
            ResourceConcept::query()
                ->where('resource_id', $resourceId)
                ->count()
        );

        $requestId = $response->headers->get('X-Request-ID');

        $response->assertJsonPath(
            'meta.request_id',
            $requestId
        );
    }
}
