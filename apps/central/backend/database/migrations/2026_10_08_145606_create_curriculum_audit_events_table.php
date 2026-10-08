<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_audit_events', function (Blueprint $table) {
            $table->id();

            $table->string('event_type', 64);

            $table->string('entity_type', 64)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->foreignId('proposal_id')
                ->nullable()
                ->constrained('curriculum_proposals')
                ->nullOnDelete();

            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');

            $table->timestamps();

            $table->index(
                ['entity_type', 'entity_id'],
                'curriculum_audit_entity_lookup'
            );

            $table->index(
                ['proposal_id', 'occurred_at'],
                'curriculum_audit_proposal_lookup'
            );

            $table->index(
                ['event_type', 'occurred_at'],
                'curriculum_audit_event_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_audit_events');
    }
};
