<?php

namespace Tests\Feature\Api\V1;

use App\Models\Concept;
use App\Models\Resource;
use App\Models\ResourceConcept;
use App\Models\ResourceVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceIndexEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_active_resources_with_published_versions(): void
    {
        $published = $this->createPublishedResource(
            code: 'published-resource',
            title: 'Resursă publicată'
        );

        $draftOnly = Resource::create([
            'code' => 'draft-only-resource',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        ResourceVersion::create([
            'resource_id' => $draftOnly->id,
            'version_number' => 1,
            'title' => 'Resursă draft',
            'summary' => null,
            'content' => null,
            'source_url' => null,
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $inactive = $this->createPublishedResource(
            code: 'inactive-resource',
            title: 'Resursă inactivă',
            resource: [
                'status' => 'inactive',
            ]
        );

        $response = $this->getJson('/api/v1/resources');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonMissing([
                'id' => $draftOnly->id,
            ])
            ->assertJsonMissing([
                'id' => $inactive->id,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
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
                'meta' => [
                    'request_id',
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                        'from',
                        'to',
                    ],
                ],
            ]);
    }

    public function test_it_searches_published_version_title_and_summary(): void
    {
        $titleMatch = $this->createPublishedResource(
            code: 'solar-title',
            title: 'Energia solară'
        );

        $summaryMatch = $this->createPublishedResource(
            code: 'solar-summary',
            title: 'Surse regenerabile',
            version: [
                'summary' => 'Introducere în energia solară.',
            ]
        );

        $this->createPublishedResource(
            code: 'unrelated',
            title: 'Ecuații de gradul I',
            version: [
                'summary' => 'Noțiuni de algebră.',
            ]
        );

        $response = $this->getJson(
            '/api/v1/resources?q=solar'
        );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $ids = collect($response->json('data'))
            ->pluck('id');

        $this->assertTrue($ids->contains($titleMatch->id));
        $this->assertTrue($ids->contains($summaryMatch->id));
    }

    public function test_it_filters_resources_by_public_contract(): void
    {
        $conceptA = $this->createConcept(
            'fractions',
            'Fracții'
        );

        $conceptB = $this->createConcept(
            'geometry',
            'Geometrie'
        );

        $matching = $this->createPublishedResource(
            code: 'matching-resource',
            title: 'Fracții explicate',
            resource: [
                'type' => 'explanation',
            ],
            version: [
                'language_code' => 'ro',
                'difficulty_level' => 2,
                'complexity_level' => 3,
            ],
            concept: $conceptA
        );

        $this->createPublishedResource(
            code: 'wrong-type',
            title: 'Fracții video',
            resource: [
                'type' => 'video',
            ],
            version: [
                'language_code' => 'ro',
                'difficulty_level' => 2,
                'complexity_level' => 3,
            ],
            concept: $conceptA
        );

        $this->createPublishedResource(
            code: 'wrong-difficulty',
            title: 'Fracții avansate',
            resource: [
                'type' => 'explanation',
            ],
            version: [
                'language_code' => 'ro',
                'difficulty_level' => 5,
                'complexity_level' => 3,
            ],
            concept: $conceptA
        );

        $this->createPublishedResource(
            code: 'wrong-concept',
            title: 'Geometrie',
            resource: [
                'type' => 'explanation',
            ],
            version: [
                'language_code' => 'ro',
                'difficulty_level' => 2,
                'complexity_level' => 3,
            ],
            concept: $conceptB
        );

        $url = sprintf(
            '/api/v1/resources?type=explanation'
            . '&language_code=ro'
            . '&difficulty_level=2'
            . '&complexity_level=3'
            . '&concept_id=%d',
            $conceptA->id
        );

        $response = $this->getJson($url);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $matching->id
            );
    }

    public function test_it_paginates_resource_results(): void
    {
        foreach (range(1, 5) as $number) {
            $this->createPublishedResource(
                code: "resource-{$number}",
                title: "Resursa {$number}",
                version: [
                    'published_at' =>
                        now()->subMinutes($number),
                ]
            );
        }

        $response = $this->getJson(
            '/api/v1/resources?page=2&per_page=2'
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
            );
    }

    public function test_it_sorts_resources_by_title(): void
    {
        $this->createPublishedResource(
            code: 'z-resource',
            title: 'Zoologie'
        );

        $this->createPublishedResource(
            code: 'a-resource',
            title: 'Algebră'
        );

        $response = $this->getJson(
            '/api/v1/resources'
            . '?sort=title'
            . '&direction=asc'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.version.title',
                'Algebră'
            )
            ->assertJsonPath(
                'data.1.version.title',
                'Zoologie'
            );
    }

    public function test_search_uses_only_latest_published_version(): void
    {
        $resource = Resource::create([
            'code' => 'energy-history',
            'type' => 'explanation',
            'status' => 'active',
        ]);

        ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 1,
            'title' => 'Introducere în energia solară',
            'summary' => 'Versiunea publicată veche.',
            'content' => null,
            'source_url' => null,
            'language_code' => 'ro',
            'difficulty_level' => 1,
            'complexity_level' => 1,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        ResourceVersion::create([
            'resource_id' => $resource->id,
            'version_number' => 2,
            'title' => 'Introducere în energia eoliană',
            'summary' => 'Versiunea publicată curentă.',
            'content' => null,
            'source_url' => null,
            'language_code' => 'ro',
            'difficulty_level' => 2,
            'complexity_level' => 2,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $oldVersionSearch = $this->getJson(
            '/api/v1/resources?q=solar'
        );

        $oldVersionSearch
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath(
                'meta.pagination.total',
                0
            );

        $currentVersionSearch = $this->getJson(
            '/api/v1/resources?q=eolian'
        );

        $currentVersionSearch
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $resource->id
            )
            ->assertJsonPath(
                'data.0.version.version_number',
                2
            )
            ->assertJsonPath(
                'data.0.version.title',
                'Introducere în energia eoliană'
            );
    }

    public function test_it_rejects_invalid_query_parameters(): void
    {
        $response = $this->getJson(
            '/api/v1/resources'
            . '?per_page=101'
            . '&sort=unsupported'
            . '&direction=sideways'
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            )
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        'fields' => [
                            'per_page',
                            'sort',
                            'direction',
                        ],
                    ],
                ],
                'meta' => [
                    'request_id',
                ],
            ]);
    }

    private function createConcept(
        string $code,
        string $title
    ): Concept {
        return Concept::create([
            'code' => $code,
            'title' => $title,
            'description' => null,
            'status' => 'active',
        ]);
    }

    private function createPublishedResource(
        string $code,
        string $title,
        array $resource = [],
        array $version = [],
        ?Concept $concept = null
    ): Resource {
        $educationalResource = Resource::create(
            array_merge([
                'code' => $code,
                'type' => 'explanation',
                'status' => 'active',
            ], $resource)
        );

        ResourceVersion::create(
            array_merge([
                'resource_id' => $educationalResource->id,
                'version_number' => 1,
                'title' => $title,
                'summary' => null,
                'content' => null,
                'source_url' => null,
                'language_code' => 'ro',
                'difficulty_level' => 1,
                'complexity_level' => 1,
                'status' => 'published',
                'published_at' => now(),
            ], $version)
        );

        if ($concept !== null) {
            ResourceConcept::create([
                'resource_id' =>
                    $educationalResource->id,
                'concept_id' => $concept->id,
                'is_primary' => true,
                'display_order' => 1,
            ]);
        }

        return $educationalResource;
    }
}
