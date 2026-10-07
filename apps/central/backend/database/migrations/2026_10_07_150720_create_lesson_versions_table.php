<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('version_number');

            $table->text('summary')->nullable();

            $table->json('learning_objectives')->nullable();

            $table->longText('content')->nullable();

            $table->unsignedSmallInteger(
                'estimated_duration_minutes'
            )->nullable();

            $table->char('language_code', 2)
                ->default('ro');

            $table->string('status', 32)
                ->default('draft');

            $table->timestampTz('published_at')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestampTz('submitted_at')
                ->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestampTz('reviewed_at')
                ->nullable();

            $table->text('review_note')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'lesson_id',
                    'version_number',
                ],
                'lesson_version_unique'
            );

            $table->index([
                'lesson_id',
                'status',
            ]);

            $table->index([
                'status',
                'published_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_versions');
    }
};
