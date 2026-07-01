<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['shop_id', 'slug']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->decimal('last_purchase_price', 12, 2)->nullable();
            $table->decimal('last_sale_price', 12, 2)->nullable();
            $table->integer('last_stock_quantity')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['shop_id', 'slug']);
            $table->unique(['shop_id', 'sku']);
        });

        Schema::create('product_stock_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('update');
            $table->integer('previous_stock_quantity')->default(0);
            $table->integer('new_stock_quantity')->default(0);
            $table->integer('quantity_delta')->default(0);
            $table->decimal('previous_purchase_price', 12, 2)->nullable();
            $table->decimal('new_purchase_price', 12, 2)->nullable();
            $table->decimal('previous_sale_price', 12, 2)->nullable();
            $table->decimal('new_sale_price', 12, 2)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_logs');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
