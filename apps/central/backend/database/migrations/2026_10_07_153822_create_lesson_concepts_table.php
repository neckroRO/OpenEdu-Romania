<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_concepts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('concept_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('display_order')
                ->default(0);

            $table->boolean('is_core')
                ->default(false);

            $table->timestamps();

            $table->unique(
                [
                    'lesson_id',
                    'concept_id',
                ],
                'lesson_concept_unique'
            );

            $table->index([
                'lesson_id',
                'display_order',
            ]);

            $table->index([
                'concept_id',
                'display_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_concepts');
    }
};
