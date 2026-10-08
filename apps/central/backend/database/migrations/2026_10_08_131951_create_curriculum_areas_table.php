<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_areas', function (Blueprint $table) {
            $table->id();

            $table->string('code', 64)->unique();
            $table->string('name');

            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('status', 32)->default('active');

            $table->timestamps();

            $table->index([
                'status',
                'display_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_areas');
    }
};
