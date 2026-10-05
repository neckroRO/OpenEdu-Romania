<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('curriculum_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('version', 64);

            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->string('status', 32)->default('draft');

            $table->timestamps();

            $table->unique([
                'curriculum_id',
                'version',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_versions');
    }
};
