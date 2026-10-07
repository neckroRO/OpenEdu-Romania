<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subject_reputations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('score')
                ->default(0);

            $table->unsignedInteger('contribution_count')
                ->default(0);

            $table->unsignedInteger('review_count')
                ->default(0);

            $table->timestamps();

            $table->unique(
                [
                    'user_id',
                    'subject_id',
                ],
                'user_subject_reputation_unique'
            );

            $table->index([
                'subject_id',
                'score',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subject_reputations');
    }
};
