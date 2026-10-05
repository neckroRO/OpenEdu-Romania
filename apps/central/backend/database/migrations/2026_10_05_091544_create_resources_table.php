<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();

            $table->string('code', 128)->unique();

            $table->string('type', 64);

            $table->string('status', 32)->default('active');

            $table->timestamps();

            $table->index([
                'type',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
