<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reputation_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('subject_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('event_type', 64);

            $table->integer('points');

            $table->foreignId('lesson_version_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('lesson_version_review_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->json('metadata')
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'subject_id',
                'created_at',
            ]);

            $table->index([
                'event_type',
                'created_at',
            ]);

            $table->index('lesson_version_id');
            $table->index('lesson_version_review_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reputation_events');
    }
};
