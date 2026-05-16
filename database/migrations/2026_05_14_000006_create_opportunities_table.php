<?php

use App\Enums\OpportunityStatus;
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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pipeline_stage_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->decimal('estimated_value', 14, 2)->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_close_date')->nullable()->index();
            $table->string('source')->nullable()->index();
            $table->string('status')->default(OpportunityStatus::Open->value)->index();
            $table->text('products_interests')->nullable();
            $table->text('notes')->nullable();
            $table->text('lost_reason')->nullable();
            $table->decimal('closed_value', 14, 2)->nullable();
            $table->date('closed_at')->nullable()->index();
            $table->timestamp('last_stage_changed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['pipeline_stage_id', 'status']);
            $table->index(['responsible_user_id', 'pipeline_stage_id']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
