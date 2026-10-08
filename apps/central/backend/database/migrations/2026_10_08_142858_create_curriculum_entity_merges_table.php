<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_entity_merges', function (Blueprint $table) {
            $table->id();

            $table->string('entity_type', 64);

            $table->unsignedBigInteger('source_entity_id');
            $table->unsignedBigInteger('target_entity_id');

            $table->foreignId('merged_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('merged_at');

            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique([
                'entity_type',
                'source_entity_id',
            ], 'curriculum_entity_merge_source_unique');

            $table->index([
                'entity_type',
                'target_entity_id',
            ], 'curriculum_entity_merge_target_lookup');

            $table->index('merged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_entity_merges');
    }
};
