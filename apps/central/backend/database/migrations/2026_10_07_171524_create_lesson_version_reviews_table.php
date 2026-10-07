<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_version_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_version_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('reviewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unsignedInteger('review_round');

            $table->string('verdict', 32);

            $table->unsignedTinyInteger('correctness_score');
            $table->unsignedTinyInteger('curriculum_alignment_score');
            $table->unsignedTinyInteger('clarity_score');
            $table->unsignedTinyInteger('pedagogical_value_score');
            $table->unsignedTinyInteger('difficulty_fit_score');

            $table->text('comment')
                ->nullable();

            $table->decimal('weight_snapshot', 5, 2)
                ->default(1.00);

            $table->timestamps();

            $table->unique(
                [
                    'lesson_version_id',
                    'review_round',
                    'reviewer_id',
                ],
                'lesson_version_review_unique'
            );

            $table->index([
                'lesson_version_id',
                'review_round',
                'verdict',
            ]);

            $table->index([
                'reviewer_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_version_reviews');
    }
};