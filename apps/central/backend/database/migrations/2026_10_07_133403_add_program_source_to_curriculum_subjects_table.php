<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            $table->string('program_reference')
                ->nullable()
                ->after('status');

            $table->text('program_source_url')
                ->nullable()
                ->after('program_reference');

            $table->date('program_approved_at')
                ->nullable()
                ->after('program_source_url');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            $table->dropColumn([
                'program_reference',
                'program_source_url',
                'program_approved_at',
            ]);
        });
    }
};
