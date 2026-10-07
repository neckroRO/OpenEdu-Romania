<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurriculumImportEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_import_curriculum(): void
    {
        $this->postJson(
            '/api/v1/admin/curriculum/import',
            $this->validPayload()
        )->assertUnauthorized();
    }

    public function test_teacher_cannot_import_curriculum(): void
    {
        Sanctum::actingAs(
            User::factory()->teacher()->create()
        );

        $this->postJson(
            '/api/v1/admin/curriculum/import',
            $this->validPayload()
        )->assertForbidden();
    }

    public function test_moderator_cannot_import_curriculum(): void
    {
        Sanctum::actingAs(
            User::factory()->moderator()->create()
        );

        $this->postJson(
            '/api/v1/admin/curriculum/import',
            $this->validPayload()
        )->assertForbidden();
    }

    public function test_admin_can_dry_run_curriculum_import(): void
    {
        Sanctum::actingAs(
            User::factory()->admin()->create()
        );

        $response = $this->postJson(
            '/api/v1/admin/curriculum/import?dry_run=1',
            $this->validPayload()
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath(
                'data.mode',
                'dry_run'
            )
            ->assertJsonPath(
                'data.persisted',
                false
            )
            ->assertJsonPath(
                'data.summary.domains',
                1
            )
            ->assertJsonPath(
                'data.summary.concepts',
                1
            )
            ->assertJsonPath(
                'data.summary.competencies',
                12
            );

        $this->assertDatabaseCount('curricula', 0);
        $this->assertDatabaseCount(
            'curriculum_subjects',
            0
        );
        $this->assertDatabaseCount('domains', 0);
        $this->assertDatabaseCount('concepts', 0);
        $this->assertDatabaseCount('competencies', 0);
    }

    public function test_admin_can_import_curriculum(): void
    {
        Sanctum::actingAs(
            User::factory()->admin()->create()
        );

        $response = $this->postJson(
            '/api/v1/admin/curriculum/import',
            $this->validPayload()
        );

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath(
                'data.mode',
                'import'
            )
            ->assertJsonPath(
                'data.persisted',
                true
            )
            ->assertJsonPath(
                'data.summary.domains',
                1
            )
            ->assertJsonPath(
                'data.summary.concepts',
                1
            )
            ->assertJsonPath(
                'data.summary.competencies',
                12
            );

        $this->assertDatabaseCount('curricula', 1);
        $this->assertDatabaseCount(
            'curriculum_subjects',
            1
        );
        $this->assertDatabaseCount('domains', 1);
        $this->assertDatabaseCount('concepts', 1);
        $this->assertDatabaseCount('competencies', 12);
        $this->assertDatabaseCount(
            'competency_concepts',
            6
        );
    }

    public function test_invalid_import_returns_api_validation_error(): void
    {
        Sanctum::actingAs(
            User::factory()->admin()->create()
        );

        $payload = $this->validPayload();
        $payload['schema_version'] = '999.0';

        $response = $this->postJson(
            '/api/v1/admin/curriculum/import',
            $payload
        );

        $response
            ->assertUnprocessable()
            ->assertHeader('X-Request-ID')
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
                            'schema_version',
                        ],
                    ],
                ],
                'meta' => [
                    'request_id',
                ],
            ]);

        $this->assertDatabaseCount('curricula', 0);
        $this->assertDatabaseCount(
            'curriculum_subjects',
            0
        );
    }

    private function validPayload(): array
    {
        $path = base_path(
            'resources/curriculum-import/v1/examples/'
            .'ro-grade-7-mathematics.json'
        );

        return json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
