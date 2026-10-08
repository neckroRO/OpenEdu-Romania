<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_proposals', function (Blueprint $table) {
            $table->id();

            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->string('proposal_type', 32);
            $table->json('payload');

            $table->text('reason')->nullable();

            $table->string('status', 32)->default('pending');

            $table->foreignId('proposed_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();

            $table->index([
                'entity_type',
                'entity_id',
            ]);

            $table->index([
                'status',
                'created_at',
            ]);

            $table->index([
                'proposal_type',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_proposals');
    }
};
