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
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->uuid('uuid');
            $table->string('flag')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->string('image')->nullable();
            $table->integer('qtd_stock');
            $table->double('price', 10,2);
            $table->double('price_cost', 10,2)->nullable();
            $table->double('promotional_price', 10,2)->nullable();
            $table->string('brand')->nullable();
            $table->string('sku')->nullable();
            $table->double('weight', 10,2)->nullable();
            $table->double('height', 10,2)->nullable();
            $table->double('width', 10,2)->nullable();
            $table->double('depth', 10,2)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
    }
};
