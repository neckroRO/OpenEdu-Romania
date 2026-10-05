<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();

            $table->foreignId('curriculum_subject_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('parent_domain_id')
                ->nullable()
                ->constrained('domains')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->unsignedSmallInteger('display_order')->default(0);

            $table->timestamps();

            $table->index([
                'curriculum_subject_id',
                'display_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
