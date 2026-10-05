<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_versions', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestampTz('submitted_at')
                ->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestampTz('reviewed_at')
                ->nullable();

            $table->text('review_note')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('resource_versions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('reviewed_by');

            $table->dropColumn([
                'submitted_at',
                'reviewed_at',
                'review_note',
            ]);
        });
    }
};
