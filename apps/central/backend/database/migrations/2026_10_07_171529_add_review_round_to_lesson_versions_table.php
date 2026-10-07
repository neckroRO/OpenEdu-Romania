<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_versions', function (Blueprint $table) {
            $table->unsignedInteger('review_round')
                ->default(0)
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_versions', function (Blueprint $table) {
            $table->dropColumn('review_round');
        });
    }
};
