<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('curriculum_subject_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code', 64);
            $table->string('title');
            $table->text('description')->nullable();

            $table->unsignedSmallInteger('display_order')
                ->default(0);

            $table->string('status', 32)
                ->default('active');

            $table->timestamps();

            $table->unique(
                [
                    'curriculum_subject_id',
                    'code',
                ],
                'lesson_curriculum_subject_code_unique'
            );

            $table->index([
                'curriculum_subject_id',
                'status',
                'display_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
