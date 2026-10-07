<?php

namespace Tests\Unit;

use App\Services\Curriculum\CurriculumImportValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CurriculumImportValidatorTest extends TestCase
{
    public function test_real_example_is_valid(): void
    {
        $payload = $this->validPayload();

        $validated = $this->validator()->validate($payload);

        $this->assertSame('1.0', $validated['schema_version']);
        $this->assertCount(1, $validated['domains']);
        $this->assertCount(1, $validated['concepts']);
        $this->assertCount(12, $validated['competencies']);
    }

    public function test_it_rejects_unknown_schema_version(): void
    {
        $payload = $this->validPayload();
        $payload['schema_version'] = '2.0';

        $this->assertValidationError(
            $payload,
            'schema_version'
        );
    }

    public function test_it_rejects_duplicate_domain_keys(): void
    {
        $payload = $this->validPayload();

        $payload['domains'][] = $payload['domains'][0];

        $this->assertValidationError(
            $payload,
            'domains.1.key'
        );
    }

    public function test_it_rejects_unknown_domain_in_concept_placement(): void
    {
        $payload = $this->validPayload();

        $payload['concepts'][0]['placements'][0]['domain_key'] =
            'missing-domain';

        $this->assertValidationError(
            $payload,
            'concepts.0.placements.0.domain_key'
        );
    }

    public function test_it_rejects_duplicate_concept_codes(): void
    {
        $payload = $this->validPayload();

        $payload['concepts'][] = $payload['concepts'][0];

        $this->assertValidationError(
            $payload,
            'concepts.1.code'
        );
    }

    public function test_it_rejects_unknown_competency_parent(): void
    {
        $payload = $this->validPayload();

        $payload['competencies'][1]['parent_code'] = 'missing-parent';

        $this->assertValidationError(
            $payload,
            'competencies.1.parent_code'
        );
    }

    public function test_it_rejects_competency_hierarchy_cycles(): void
    {
        $payload = $this->validPayload();

        $payload['competencies'][0]['parent_code'] = '1.2';
        $payload['competencies'][1]['parent_code'] = '1';

        $this->assertValidationError(
            $payload,
            'competencies.0.parent_code'
        );
    }

    public function test_it_rejects_unknown_concept_in_competency_link(): void
    {
        $payload = $this->validPayload();

        $payload['competencies'][1]['concepts'][0]['code'] =
            'missing-concept';

        $this->assertValidationError(
            $payload,
            'competencies.1.concepts.0.code'
        );
    }

    private function validator(): CurriculumImportValidator
    {
        return app(CurriculumImportValidator::class);
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

    private function assertValidationError(
        array $payload,
        string $key
    ): void {
        try {
            $this->validator()->validate($payload);

            $this->fail(
                "Expected validation error for [{$key}]."
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                $key,
                $exception->errors()
            );
        }
    }
}
