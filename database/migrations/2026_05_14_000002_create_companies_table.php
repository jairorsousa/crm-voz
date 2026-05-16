<?php

use App\Enums\CompanyStatus;
use App\Enums\LeadTemperature;
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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('cnpj', 14)->unique();
            $table->string('segment')->nullable()->index();
            $table->string('site')->nullable();
            $table->string('phone', 20)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('whatsapp', 20)->nullable()->index();
            $table->string('city')->nullable()->index();
            $table->char('state', 2)->nullable()->index();
            $table->string('address')->nullable();
            $table->string('status')->default(CompanyStatus::NewLead->value)->index();
            $table->string('lead_source')->nullable()->index();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_interaction_at')->nullable()->index();

            $table->decimal('average_collection_ticket', 12, 2)->nullable();
            $table->unsignedInteger('overdue_customers_count')->nullable();
            $table->decimal('total_default_amount', 14, 2)->nullable();
            $table->unsignedInteger('approx_customers_count')->nullable();
            $table->string('current_system')->nullable();
            $table->boolean('has_internal_collection_team')->nullable();
            $table->boolean('has_erp_integration')->nullable();
            $table->text('portfolio_notes')->nullable();

            $table->string('company_type')->nullable()->index();
            $table->string('company_size')->nullable()->index();
            $table->string('commercial_potential')->nullable();
            $table->string('lead_temperature')->default(LeadTemperature::Cold->value)->index();
            $table->string('priority')->default(PriorityLevel::Medium->value)->index();
            $table->string('pain_profile')->nullable();
            $table->unsignedTinyInteger('closing_probability')->default(0);

            $table->timestamps();

            $table->index(['status', 'responsible_user_id']);
            $table->index(['lead_temperature', 'priority']);
            $table->index(['legal_name', 'trade_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
