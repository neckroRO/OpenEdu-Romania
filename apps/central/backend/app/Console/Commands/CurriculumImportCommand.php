<?php

namespace App\Console\Commands;

use App\Services\Curriculum\CurriculumImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

class CurriculumImportCommand extends Command
{
    protected $signature = 'curriculum:import
                            {path : Path to the curriculum JSON file}
                            {--dry-run : Validate and simulate the import without persisting changes}';

    protected $description =
        'Import a versioned OpenEdu curriculum JSON document';

    public function handle(
        CurriculumImportService $importService
    ): int {
        $path = $this->resolvePath(
            (string) $this->argument('path')
        );

        if (!is_file($path) || !is_readable($path)) {
            $this->error(
                "Curriculum import file not found or unreadable: {$path}"
            );

            return self::FAILURE;
        }

        try {
            $payload = json_decode(
                file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            $this->error('Invalid JSON document.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        if (!is_array($payload)) {
            $this->error(
                'Curriculum import document must contain a JSON object.'
            );

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            return $this->runDryRun(
                $importService,
                $payload
            );
        }

        try {
            $result = $importService->import($payload);
        } catch (ValidationException $exception) {
            return $this->renderValidationFailure(
                $exception
            );
        }

        $this->info('Curriculum imported successfully.');

        $this->renderSummary($result);

        return self::SUCCESS;
    }

    private function runDryRun(
        CurriculumImportService $importService,
        array $payload
    ): int {
        DB::beginTransaction();

        try {
            $result = $importService->import($payload);

            DB::rollBack();
        } catch (ValidationException $exception) {
            DB::rollBack();

            return $this->renderValidationFailure(
                $exception
            );
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        $this->info(
            'Curriculum dry-run completed successfully.'
        );

        $this->comment(
            'No database changes were persisted.'
        );

        $this->renderSummary($result);

        return self::SUCCESS;
    }

    private function renderValidationFailure(
        ValidationException $exception
    ): int {
        $this->error(
            'Curriculum import validation failed.'
        );

        $rows = [];

        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $rows[] = [$field, $message];
            }
        }

        $this->table(
            ['Field', 'Error'],
            $rows
        );

        return self::FAILURE;
    }

    private function renderSummary(array $result): void
    {
        $this->table(
            ['Item', 'Value'],
            [
                [
                    'Curriculum ID',
                    $result['curriculum_id'],
                ],
                [
                    'Curriculum version ID',
                    $result['curriculum_version_id'],
                ],
                [
                    'Education level ID',
                    $result['education_level_id'],
                ],
                [
                    'Subject ID',
                    $result['subject_id'],
                ],
                [
                    'Curriculum subject ID',
                    $result['curriculum_subject_id'],
                ],
                [
                    'Domains',
                    $result['domains'],
                ],
                [
                    'Concepts',
                    $result['concepts'],
                ],
                [
                    'Competencies',
                    $result['competencies'],
                ],
            ]
        );
    }

    private function resolvePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return true;
        }

        return (bool) preg_match(
            '/^[A-Za-z]:[\\\\\/]/',
            $path
        );
    }
}
