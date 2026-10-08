<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_aliases', function (Blueprint $table) {
            $table->id();

            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id');

            $table->string('alias');
            $table->string('normalized_alias');

            $table->string('source', 32)->default('community');
            $table->string('status', 32)->default('pending');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'entity_type',
                'entity_id',
                'normalized_alias',
            ], 'curriculum_alias_entity_unique');

            $table->index([
                'entity_type',
                'normalized_alias',
            ], 'curriculum_alias_lookup');

            $table->index([
                'status',
                'source',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_aliases');
    }
};
