<?php

namespace App\Console\Commands;

use App\Services\Curriculum\CurriculumImportService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use JsonException;

class CurriculumImportCommand extends Command
{
    protected $signature = 'curriculum:import
                            {path : Path to the curriculum JSON file}';

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

        try {
            $result = $importService->import($payload);
        } catch (ValidationException $exception) {
            $this->error('Curriculum import validation failed.');

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

        $this->info('Curriculum imported successfully.');

        $this->table(
            ['Item', 'Value'],
            [
                ['Curriculum ID', $result['curriculum_id']],
                [
                    'Curriculum version ID',
                    $result['curriculum_version_id'],
                ],
                [
                    'Education level ID',
                    $result['education_level_id'],
                ],
                ['Subject ID', $result['subject_id']],
                [
                    'Curriculum subject ID',
                    $result['curriculum_subject_id'],
                ],
                ['Domains', $result['domains']],
                ['Concepts', $result['concepts']],
                ['Competencies', $result['competencies']],
            ]
        );

        return self::SUCCESS;
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
