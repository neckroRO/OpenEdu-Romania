<?php

namespace Tests\Feature\Api\V1;

use App\Models\Resource;
use App\Models\ResourceVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceDetailEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_latest_published_resource_version(): void
    {
        $resource = Resource::create([
            'code' => 'linear-equations-introduction',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        $publishedVersion = ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Ecuațiile de gradul I explicate simplu',
            'summary' => 'O introducere intuitivă.',
            'content' => 'Conținutul complet publicat.',
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
            'title' => 'Versiune nouă în lucru',
            'summary' => 'Draft.',
            'content' => 'Conținut care nu trebuie expus.',
            'source_url' => null,
            'language_code' => 'ro',
            'difficulty_level' => 2,
            'complexity_level' => 2,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->getJson(
            "/api/v1/resources/{$resource->id}"
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.id', $resource->id)
            ->assertJsonPath(
                'data.code',
                'linear-equations-introduction'
            )
            ->assertJsonPath(
                'data.type',
                'explanation'
            )
            ->assertJsonPath(
                'data.version.id',
                $publishedVersion->id
            )
            ->assertJsonPath(
                'data.version.version_number',
                1
            )
            ->assertJsonPath(
                'data.version.title',
                'Ecuațiile de gradul I explicate simplu'
            )
            ->assertJsonPath(
                'data.version.content',
                'Conținutul complet publicat.'
            )
            ->assertJsonMissing([
                'title' => 'Versiune nouă în lucru',
            ])
            ->assertJsonMissing([
                'content' => 'Conținut care nu trebuie expus.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'type',
                    'version' => [
                        'id',
                        'version_number',
                        'title',
                        'summary',
                        'content',
                        'source_url',
                        'language_code',
                        'difficulty_level',
                        'complexity_level',
                        'published_at',
                    ],
                ],
                'meta' => [
                    'request_id',
                ],
            ]);

        $requestId = $response->headers->get('X-Request-ID');

        $response->assertJsonPath(
            'meta.request_id',
            $requestId
        );
    }

    public function test_it_hides_resources_without_a_published_version(): void
    {
        $resource = Resource::create([
            'code' => 'draft-resource',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Material în lucru',
            'summary' => 'Draft.',
            'content' => 'Conținut nepublicat.',
            'source_url' => null,
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->getJson(
            "/api/v1/resources/{$resource->id}"
        );

        $response
            ->assertNotFound()
            ->assertJsonPath(
                'error.code',
                'RESOURCE_NOT_FOUND'
            );
    }
}
