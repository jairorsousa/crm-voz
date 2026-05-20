<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable()->index();
            $table->text('description')->nullable();
            $table->decimal('base_price', 14, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('opportunity_product', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['opportunity_id', 'product_id'], 'opportunity_product_unique');
            $table->index(['product_id', 'opportunity_id'], 'product_opportunity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_product');
        Schema::dropIfExists('products');
    }
};
