<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('position');
            $table->string('color')->default('primary');
            $table->boolean('is_won')->default(false)->index();
            $table->boolean('is_lost')->default(false)->index();
            $table->timestamps();

            $table->unique(['pipeline_id', 'slug']);
            $table->index(['pipeline_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');
    }
};
