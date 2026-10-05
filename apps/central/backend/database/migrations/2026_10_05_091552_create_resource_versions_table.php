<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resource_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('version_number');

            $table->string('title');
            $table->text('summary')->nullable();

            $table->longText('content')->nullable();
            $table->text('source_url')->nullable();

            $table->char('language_code', 2)->default('ro');

            $table->unsignedSmallInteger('difficulty_level')->default(1);
            $table->unsignedSmallInteger('complexity_level')->default(1);

            $table->string('status', 32)->default('draft');

            $table->timestampTz('published_at')->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'resource_id',
                    'version_number',
                ],
                'resource_version_unique'
            );

            $table->index([
                'resource_id',
                'status',
            ]);

            $table->index([
                'status',
                'published_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_versions');
    }
};
