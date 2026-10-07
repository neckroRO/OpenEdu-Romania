<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_imports_valid_curriculum_file(): void
    {
        $this->artisan(
            'curriculum:import',
            [
                'path' =>
                    'resources/curriculum-import/v1/examples/'
                    .'ro-grade-7-mathematics.json',
            ]
        )
            ->expectsOutput(
                'Curriculum imported successfully.'
            )
            ->assertSuccessful();

        $this->assertDatabaseCount('curricula', 1);
        $this->assertDatabaseCount('curriculum_subjects', 1);
        $this->assertDatabaseCount('domains', 1);
        $this->assertDatabaseCount('concepts', 1);
        $this->assertDatabaseCount('competencies', 12);
        $this->assertDatabaseCount(
            'competency_concepts',
            6
        );
    }

    public function test_command_rejects_missing_file(): void
    {
        $this->artisan(
            'curriculum:import',
            [
                'path' => 'missing-curriculum.json',
            ]
        )
            ->expectsOutputToContain(
                'Curriculum import file not found or unreadable'
            )
            ->assertFailed();

        $this->assertDatabaseCount('curricula', 0);
    }

    public function test_command_rejects_invalid_json(): void
    {
        $path = $this->temporaryFile(
            '{ invalid json'
        );

        try {
            $this->artisan(
                'curriculum:import',
                ['path' => $path]
            )
                ->expectsOutput(
                    'Invalid JSON document.'
                )
                ->assertFailed();

            $this->assertDatabaseCount('curricula', 0);
        } finally {
            @unlink($path);
        }
    }

    public function test_command_rejects_invalid_payload(): void
    {
        $payload = $this->validPayload();

        $payload['schema_version'] = '999.0';

        $path = $this->temporaryFile(
            json_encode(
                $payload,
                JSON_THROW_ON_ERROR
            )
        );

        try {
            $this->artisan(
                'curriculum:import',
                ['path' => $path]
            )
                ->expectsOutput(
                    'Curriculum import validation failed.'
                )
                ->assertFailed();

            $this->assertDatabaseCount('curricula', 0);
        } finally {
            @unlink($path);
        }
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

    private function temporaryFile(string $contents): string
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'openedu-curriculum-'
        );

        if ($path === false) {
            $this->fail(
                'Unable to create temporary curriculum file.'
            );
        }

        file_put_contents($path, $contents);

        return $path;
    }
}
