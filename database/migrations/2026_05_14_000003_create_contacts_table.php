<?php

use App\Enums\ContactType;
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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name')->index();
            $table->string('position')->nullable();
            $table->string('department')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->string('whatsapp', 20)->nullable()->index();
            $table->string('linkedin_url')->nullable();
            $table->string('type')->default(ContactType::Other->value)->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->boolean('receives_automations')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_primary']);
            $table->index(['company_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
