<?php

namespace Tests\Feature\Api\V1;

use App\Models\Concept;
use App\Models\Resource;
use App\Models\ResourceConcept;
use App\Models\ResourceVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConceptResourceEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_published_resources_for_a_concept(): void
    {
        $concept = Concept::create([
            'code' => 'linear-equations',
            'title' => 'Ecuații de gradul I',
            'description' => 'Ecuații de gradul I cu o necunoscută.',
            'status' => 'active',
        ]);

        $resource = Resource::create([
            'code' => 'linear-equations-introduction',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Ecuațiile de gradul I explicate simplu',
            'summary' => 'Introducere intuitivă.',
            'content' => 'Conținut publicat.',
            'source_url' => null,
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'published',
            'published_at' => now(),
        ]);

        ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 2,
            'title' => 'Versiune încă în lucru',
            'summary' => 'Draft.',
            'content' => 'Acest conținut nu trebuie expus.',
            'source_url' => null,
            'language_code' => 'ro',
            'difficulty_level' => 2,
            'complexity_level' => 2,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $resourceConcept = ResourceConcept::create([
            'resource_id' => $resource->id,
            'concept_id' => $concept->id,
            'is_primary' => true,
            'display_order' => 1,
        ]);

        $response = $this->getJson(
            "/api/v1/concepts/{$concept->id}/resources"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $resourceConcept->id
            )
            ->assertJsonPath(
                'data.0.is_primary',
                true
            )
            ->assertJsonPath(
                'data.0.display_order',
                1
            )
            ->assertJsonPath(
                'data.0.resource.id',
                $resource->id
            )
            ->assertJsonPath(
                'data.0.resource.code',
                'linear-equations-introduction'
            )
            ->assertJsonPath(
                'data.0.resource.type',
                'explanation'
            )
            ->assertJsonPath(
                'data.0.resource.version.version_number',
                1
            )
            ->assertJsonPath(
                'data.0.resource.version.title',
                'Ecuațiile de gradul I explicate simplu'
            )
            ->assertJsonPath(
                'data.0.resource.version.summary',
                'Introducere intuitivă.'
            )
            ->assertJsonPath(
                'data.0.resource.version.language_code',
                'ro'
            )
            ->assertJsonPath(
                'data.0.resource.version.difficulty_level',
                1
            )
            ->assertJsonPath(
                'data.0.resource.version.complexity_level',
                1
            )
            ->assertJsonMissing([
                'title' => 'Versiune încă în lucru',
            ])
            ->assertJsonMissing([
                'content' => 'Acest conținut nu trebuie expus.',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'is_primary',
                        'display_order',
                        'resource' => [
                            'id',
                            'code',
                            'type',
                            'version' => [
                                'id',
                                'version_number',
                                'title',
                                'summary',
                                'language_code',
                                'difficulty_level',
                                'complexity_level',
                                'published_at',
                            ],
                        ],
                    ],
                ],
                'meta' => [
                    'request_id',
                ],
            ]);

        $this->assertArrayNotHasKey(
            'content',
            $response->json('data.0.resource.version')
        );

        $this->assertArrayNotHasKey(
            'source_url',
            $response->json('data.0.resource.version')
        );

        $requestId = $response->headers->get('X-Request-ID');

        $response->assertJsonPath(
            'meta.request_id',
            $requestId
        );
    }

    public function test_it_paginates_resources_for_a_concept(): void
    {
        $concept = Concept::create([
            'code' => 'fractions',
            'title' => 'Fracții',
            'description' => null,
            'status' => 'active',
        ]);

        foreach (range(1, 5) as $number) {
            $resource = Resource::create([
                'code' => "fractions-resource-{$number}",
                'type' => 'explanation',
                'status' => 'active',
            ]);

            ResourceVersion::create([
                'resource_id' => $resource->id,
                'version_number' => 1,
                'title' => "Fracții {$number}",
                'summary' => null,
                'content' => null,
                'source_url' => null,
                'language_code' => 'ro',
                'difficulty_level' => 1,
                'complexity_level' => 1,
                'status' => 'published',
                'published_at' => now(),
            ]);

            ResourceConcept::create([
                'resource_id' => $resource->id,
                'concept_id' => $concept->id,
                'is_primary' => true,
                'display_order' => $number,
            ]);
        }

        $response = $this->getJson(
            "/api/v1/concepts/{$concept->id}/resources"
            . '?page=2&per_page=2'
        );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'meta.pagination.current_page',
                2
            )
            ->assertJsonPath(
                'meta.pagination.per_page',
                2
            )
            ->assertJsonPath(
                'meta.pagination.total',
                5
            )
            ->assertJsonPath(
                'meta.pagination.last_page',
                3
            )
            ->assertJsonPath(
                'meta.pagination.from',
                3
            )
            ->assertJsonPath(
                'meta.pagination.to',
                4
            )
            ->assertJsonPath(
                'data.0.display_order',
                3
            )
            ->assertJsonPath(
                'data.1.display_order',
                4
            );
    }

}
