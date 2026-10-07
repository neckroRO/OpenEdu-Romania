<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_concepts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('competency_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('concept_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('display_order')->default(0);

            $table->boolean('is_core')->default(true);

            $table->timestamps();

            $table->unique(
                [
                    'competency_id',
                    'concept_id',
                ],
                'competency_concept_unique'
            );

            $table->index([
                'competency_id',
                'display_order',
            ]);

            $table->index([
                'concept_id',
                'is_core',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_concepts');
    }
};
