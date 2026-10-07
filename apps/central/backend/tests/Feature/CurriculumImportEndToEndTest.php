<?php

namespace Tests\Feature;

use App\Models\CurriculumSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurriculumImportEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_import_is_idempotent_end_to_end(): void
    {
        Sanctum::actingAs(
            User::factory()->admin()->create()
        );

        $payload = $this->validPayload();

        $first = $this->postJson(
            '/api/v1/admin/curriculum/import',
            $payload
        );

        $first
            ->assertOk()
            ->assertJsonPath('data.mode', 'import')
            ->assertJsonPath('data.persisted', true);

        $firstCurriculumSubjectId =
            $first->json(
                'data.summary.curriculum_subject_id'
            );

        $second = $this->postJson(
            '/api/v1/admin/curriculum/import',
            $payload
        );

        $second
            ->assertOk()
            ->assertJsonPath('data.mode', 'import')
            ->assertJsonPath('data.persisted', true)
            ->assertJsonPath(
                'data.summary.curriculum_subject_id',
                $firstCurriculumSubjectId
            );

        $this->assertExpectedDatabaseState();
    }

    public function test_dry_run_followed_by_real_import_persists_once(): void
    {
        Sanctum::actingAs(
            User::factory()->admin()->create()
        );

        $payload = $this->validPayload();

        $dryRun = $this->postJson(
            '/api/v1/admin/curriculum/import?dry_run=1',
            $payload
        );

        $dryRun
            ->assertOk()
            ->assertJsonPath('data.mode', 'dry_run')
            ->assertJsonPath('data.persisted', false)
            ->assertJsonPath(
                'data.summary.competencies',
                12
            );

        $this->assertDatabaseCount('curricula', 0);
        $this->assertDatabaseCount(
            'curriculum_subjects',
            0
        );
        $this->assertDatabaseCount('competencies', 0);

        $import = $this->postJson(
            '/api/v1/admin/curriculum/import',
            $payload
        );

        $import
            ->assertOk()
            ->assertJsonPath('data.mode', 'import')
            ->assertJsonPath('data.persisted', true);

        $this->assertExpectedDatabaseState();
    }

    public function test_cli_and_api_share_the_same_idempotent_importer(): void
    {
        $path =
            'resources/curriculum-import/v1/examples/'
            .'ro-grade-7-mathematics.json';

        $this->artisan(
            'curriculum:import',
            ['path' => $path]
        )
            ->expectsOutput(
                'Curriculum imported successfully.'
            )
            ->assertSuccessful();

        $curriculumSubjectId =
            CurriculumSubject::query()->value('id');

        $this->assertNotNull($curriculumSubjectId);

        Sanctum::actingAs(
            User::factory()->admin()->create()
        );

        $response = $this->postJson(
            '/api/v1/admin/curriculum/import',
            $this->validPayload()
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.summary.curriculum_subject_id',
                $curriculumSubjectId
            );

        $this->assertExpectedDatabaseState();
    }

    private function assertExpectedDatabaseState(): void
    {
        $this->assertDatabaseCount('curricula', 1);
        $this->assertDatabaseCount(
            'curriculum_versions',
            1
        );
        $this->assertDatabaseCount(
            'education_levels',
            1
        );
        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseCount(
            'curriculum_subjects',
            1
        );
        $this->assertDatabaseCount('domains', 1);
        $this->assertDatabaseCount('concepts', 1);
        $this->assertDatabaseCount(
            'concept_placements',
            1
        );
        $this->assertDatabaseCount(
            'competencies',
            12
        );
        $this->assertDatabaseCount(
            'competency_concepts',
            6
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
