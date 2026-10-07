<?php

namespace Tests\Feature;

use App\Models\Competency;
use App\Models\CurriculumSubject;
use App\Models\Domain;
use App\Models\Subject;
use App\Services\Curriculum\CurriculumImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CurriculumImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_the_real_curriculum_example(): void
    {
        $result = $this->service()->import(
            $this->validPayload()
        );

        $this->assertSame(1, $result['domains']);
        $this->assertSame(1, $result['concepts']);
        $this->assertSame(12, $result['competencies']);

        $this->assertDatabaseHas('curricula', [
            'code' => 'RO-NATIONAL',
            'name' => 'Curriculum național România',
        ]);

        $this->assertDatabaseHas('education_levels', [
            'code' => 'grade-7',
            'ordinal' => 7,
        ]);

        $this->assertDatabaseHas('subjects', [
            'code' => 'mathematics',
            'name' => 'Matematică',
        ]);

        $this->assertDatabaseHas('concepts', [
            'code' => 'linear-equations',
            'title' => 'Ecuații de gradul I',
        ]);

        $curriculumSubject = CurriculumSubject::query()
            ->findOrFail($result['curriculum_subject_id']);

        $this->assertSame(
            'OMEN nr. 3393/28.02.2017',
            $curriculumSubject->program_reference
        );

        $this->assertSame(
            '2017-02-28',
            $curriculumSubject
                ->program_approved_at
                ->toDateString()
        );

        $general = Competency::query()
            ->where('curriculum_subject_id', $curriculumSubject->id)
            ->where('code', '1')
            ->firstOrFail();

        $specific = Competency::query()
            ->where('curriculum_subject_id', $curriculumSubject->id)
            ->where('code', '1.2')
            ->firstOrFail();

        $this->assertSame(
            $general->id,
            $specific->parent_competency_id
        );

        $this->assertDatabaseHas('competency_concepts', [
            'competency_id' => $specific->id,
            'is_core' => 1,
        ]);

        $this->assertDatabaseCount('competencies', 12);
        $this->assertDatabaseCount('competency_concepts', 6);
        $this->assertDatabaseCount('concept_placements', 1);
    }

    public function test_import_is_idempotent(): void
    {
        $payload = $this->validPayload();

        $first = $this->service()->import($payload);
        $second = $this->service()->import($payload);

        $this->assertSame(
            $first['curriculum_subject_id'],
            $second['curriculum_subject_id']
        );

        $this->assertDatabaseCount('curricula', 1);
        $this->assertDatabaseCount('curriculum_versions', 1);
        $this->assertDatabaseCount('education_levels', 1);
        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseCount('curriculum_subjects', 1);
        $this->assertDatabaseCount('domains', 1);
        $this->assertDatabaseCount('concepts', 1);
        $this->assertDatabaseCount('concept_placements', 1);
        $this->assertDatabaseCount('competencies', 12);
        $this->assertDatabaseCount('competency_concepts', 6);
    }

    public function test_reimport_updates_existing_records(): void
    {
        $payload = $this->validPayload();

        $this->service()->import($payload);

        $payload['subject']['name'] = 'Matematică actualizată';
        $payload['competencies'][1]['title'] =
            'Competență specifică actualizată';

        $this->service()->import($payload);

        $this->assertDatabaseCount('subjects', 1);

        $subject = Subject::query()
            ->where('code', 'mathematics')
            ->firstOrFail();

        $this->assertSame(
            'Matematică actualizată',
            $subject->name
        );

        $specific = Competency::query()
            ->where('code', '1.2')
            ->firstOrFail();

        $this->assertSame(
            'Competență specifică actualizată',
            $specific->title
        );

        $this->assertDatabaseCount('competencies', 12);
    }

    public function test_invalid_payload_does_not_write_to_database(): void
    {
        $payload = $this->validPayload();

        $payload['domains'][] = $payload['domains'][0];

        try {
            $this->service()->import($payload);

            $this->fail(
                'Expected curriculum import validation to fail.'
            );
        } catch (ValidationException) {
            $this->assertDatabaseCount('curricula', 0);
            $this->assertDatabaseCount('curriculum_subjects', 0);
            $this->assertDatabaseCount('domains', 0);
            $this->assertDatabaseCount('concepts', 0);
            $this->assertDatabaseCount('competencies', 0);
        }
    }

    private function service(): CurriculumImportService
    {
        return app(CurriculumImportService::class);
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
