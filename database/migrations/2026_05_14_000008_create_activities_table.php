<?php

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\PriorityLevel;
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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default(ActivityType::Task->value)->index();
            $table->string('status')->default(ActivityStatus::Pending->value)->index();
            $table->string('priority')->default(PriorityLevel::Medium->value)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('canceled_at')->nullable()->index();
            $table->timestamps();

            $table->index(['assigned_to_user_id', 'status', 'due_at']);
            $table->index(['company_id', 'status', 'due_at']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
