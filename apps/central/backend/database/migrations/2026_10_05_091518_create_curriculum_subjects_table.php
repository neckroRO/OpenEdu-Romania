<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('curriculum_version_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('education_level_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('display_order')->default(0);

            $table->string('status', 32)->default('active');

            $table->timestamps();

            $table->unique(
                [
                    'curriculum_version_id',
                    'education_level_id',
                    'subject_id',
                ],
                'curriculum_subject_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_subjects');
    }
};
