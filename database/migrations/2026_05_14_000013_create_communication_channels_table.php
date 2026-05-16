<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_channels', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type', 30);
            $table->string('provider', 50);
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_shared')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'provider', 'is_active']);
            $table->index(['type', 'is_shared', 'is_default']);
        });

        Schema::create('communication_channel_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('communication_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['communication_channel_id', 'user_id']);
            $table->index(['user_id', 'communication_channel_id']);
        });

        Schema::table('communication_messages', function (Blueprint $table): void {
            $table
                ->foreignId('communication_channel_id')
                ->nullable()
                ->after('communication_template_id')
                ->constrained('communication_channels')
                ->nullOnDelete();

            $table->index(['communication_channel_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('communication_messages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('communication_channel_id');
        });

        Schema::dropIfExists('communication_channel_user');
        Schema::dropIfExists('communication_channels');
    }
};
