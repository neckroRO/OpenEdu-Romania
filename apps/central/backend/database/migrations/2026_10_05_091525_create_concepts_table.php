<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concepts', function (Blueprint $table) {
            $table->id();

            $table->string('code', 128)->unique();
            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status', 32)->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concepts');
    }
};
