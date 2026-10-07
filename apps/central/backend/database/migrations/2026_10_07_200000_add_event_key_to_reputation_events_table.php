<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reputation_events', function (Blueprint $table) {
            $table->string('event_key', 191)
                ->nullable()
                ->unique()
                ->after('event_type');
        });
    }

    public function down(): void
    {
        Schema::table('reputation_events', function (Blueprint $table) {
            $table->dropUnique([
                'event_key',
            ]);

            $table->dropColumn('event_key');
        });
    }
};
