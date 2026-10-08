<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            $table->dropUnique('curriculum_subject_unique');

            $table->foreignId('curriculum_framework_variant_id')
                ->nullable()
                ->after('curriculum_version_id')
                ->constrained('curriculum_framework_variants')
                ->nullOnDelete();

            $table->foreignId('curriculum_area_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('curriculum_areas')
                ->nullOnDelete();

            $table->string('component', 32)
                ->default('TC')
                ->after('curriculum_area_id');

            $table->unsignedSmallInteger('hours_min')
                ->nullable()
                ->after('component');

            $table->unsignedSmallInteger('hours_max')
                ->nullable()
                ->after('hours_min');

            $table->unique([
                'curriculum_version_id',
                'curriculum_framework_variant_id',
                'education_level_id',
                'subject_id',
            ], 'curriculum_subject_framework_unique');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            $table->dropUnique('curriculum_subject_framework_unique');

            $table->dropForeign([
                'curriculum_framework_variant_id',
            ]);

            $table->dropForeign([
                'curriculum_area_id',
            ]);

            $table->dropColumn([
                'curriculum_framework_variant_id',
                'curriculum_area_id',
                'component',
                'hours_min',
                'hours_max',
            ]);

            $table->unique([
                'curriculum_version_id',
                'education_level_id',
                'subject_id',
            ], 'curriculum_subject_unique');
        });
    }
};
