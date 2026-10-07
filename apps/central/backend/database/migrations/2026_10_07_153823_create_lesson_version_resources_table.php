<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'lesson_version_resources',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('lesson_version_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('resource_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('role', 32)
                    ->default('supplementary');

                $table->unsignedSmallInteger('display_order')
                    ->default(0);

                $table->boolean('is_required')
                    ->default(true);

                $table->timestamps();

                $table->unique(
                    [
                        'lesson_version_id',
                        'resource_id',
                    ],
                    'lesson_version_resource_unique'
                );

                $table->index([
                    'lesson_version_id',
                    'display_order',
                ]);

                $table->index([
                    'resource_id',
                    'role',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'lesson_version_resources'
        );
    }
};
