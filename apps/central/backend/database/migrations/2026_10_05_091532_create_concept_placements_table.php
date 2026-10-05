<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concept_placements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('concept_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('domain_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('display_order')->default(0);

            $table->boolean('is_core')->default(true);

            $table->timestamps();

            $table->unique(
                [
                    'concept_id',
                    'domain_id',
                ],
                'concept_placement_unique'
            );

            $table->index([
                'domain_id',
                'display_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concept_placements');
    }
};
