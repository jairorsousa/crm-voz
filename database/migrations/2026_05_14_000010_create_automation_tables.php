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
        Schema::create('commercial_automations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger', 80);
            $table->json('conditions')->nullable();
            $table->json('actions');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['trigger', 'is_active']);
        });

        Schema::create('automation_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commercial_automation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trigger', 80);
            $table->string('idempotency_key')->unique();
            $table->string('status', 30);
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['commercial_automation_id', 'status']);
            $table->index(['trigger', 'executed_at']);
            $table->index(['company_id', 'executed_at']);
        });

        Schema::create('internal_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('commercial_automation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_notifications');
        Schema::dropIfExists('automation_executions');
        Schema::dropIfExists('commercial_automations');
    }
};
