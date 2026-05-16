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
        Schema::create('crm_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->index();
            $table->string('key')->unique();
            $table->string('label')->nullable();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_option_values', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->index();
            $table->string('key');
            $table->string('label');
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        Schema::create('report_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report')->index();
            $table->string('format', 20);
            $table->string('status', 20)->default('pending')->index();
            $table->json('filters')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('rows_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('crm_option_values');
        Schema::dropIfExists('crm_settings');
    }
};
