<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_levels', function (Blueprint $table) {
            $table->id();

            $table->string('code', 64)->unique();
            $table->string('name');

            $table->unsignedSmallInteger('ordinal');
            $table->string('education_stage', 64);

            $table->timestamps();

            $table->index([
                'education_stage',
                'ordinal',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_levels');
    }
};
