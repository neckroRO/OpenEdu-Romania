<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_framework_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('curriculum_version_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code', 64);
            $table->string('name');

            $table->string('source_reference', 255)->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_annex', 64)->nullable();
            $table->date('approved_at')->nullable();

            $table->string('status', 32)->default('active');

            $table->timestamps();

            $table->unique([
                'curriculum_version_id',
                'code',
            ], 'curriculum_framework_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_framework_variants');
    }
};
