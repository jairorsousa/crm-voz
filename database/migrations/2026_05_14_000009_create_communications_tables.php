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
        Schema::create('communication_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 30);
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['channel', 'name']);
            $table->index(['channel', 'is_active']);
        });

        Schema::create('communication_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('communication_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 30);
            $table->string('direction', 30);
            $table->string('status', 30);
            $table->string('origin', 30)->default('manual');
            $table->string('provider', 50)->nullable();
            $table->string('external_id')->nullable();
            $table->string('from_address')->nullable();
            $table->string('to_address');
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->json('provider_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status', 'created_at']);
            $table->index(['company_id', 'channel', 'created_at']);
            $table->index(['contact_id', 'channel', 'created_at']);
            $table->index(['user_id', 'channel', 'created_at']);
            $table->index(['provider', 'external_id']);
        });

        Schema::create('communication_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 50);
            $table->string('event_type', 80);
            $table->string('external_event_id')->nullable();
            $table->string('external_message_id')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_event_id']);
            $table->index(['provider', 'external_message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_webhook_events');
        Schema::dropIfExists('communication_messages');
        Schema::dropIfExists('communication_templates');
    }
};
