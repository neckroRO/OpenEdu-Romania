<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_concepts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resource_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('concept_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('is_primary')->default(false);

            $table->unsignedSmallInteger('display_order')->default(0);

            $table->timestamps();

            $table->unique(
                [
                    'resource_id',
                    'concept_id',
                ],
                'resource_concept_unique'
            );

            $table->index([
                'concept_id',
                'display_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_concepts');
    }
};
