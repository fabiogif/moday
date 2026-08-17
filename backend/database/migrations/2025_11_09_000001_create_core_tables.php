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
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('url')->unique();
                $table->decimal('price', 10, 2)->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('max_users')->nullable();
                $table->integer('max_products')->nullable();
                $table->integer('max_orders_per_month')->nullable();
                $table->boolean('has_marketing')->default(false);
                $table->boolean('has_reports')->default(false);
                $table->timestamps();
            });
        } else {
            // plans já pode existir via 0000_02_23 (schema mínimo) — completa colunas do core
            Schema::table('plans', function (Blueprint $table) {
                if (!Schema::hasColumn('plans', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!Schema::hasColumn('plans', 'max_users')) {
                    $table->integer('max_users')->nullable();
                }
                if (!Schema::hasColumn('plans', 'max_products')) {
                    $table->integer('max_products')->nullable();
                }
                if (!Schema::hasColumn('plans', 'max_orders_per_month')) {
                    $table->integer('max_orders_per_month')->nullable();
                }
                if (!Schema::hasColumn('plans', 'has_marketing')) {
                    $table->boolean('has_marketing')->default(false);
                }
                if (!Schema::hasColumn('plans', 'has_reports')) {
                    $table->boolean('has_reports')->default(false);
                }
            });
        }

        if (!Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
                $table->uuid('uuid')->unique();
                $table->string('cnpj')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state', 2)->nullable();
                $table->string('zipcode')->nullable();
                $table->string('country')->nullable();
                $table->string('url')->nullable();
                $table->string('logo')->nullable();
                $table->string('active', 1)->default('Y');
                $table->boolean('is_active')->default(true);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('avatar')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->json('preferences')->nullable();
                $table->rememberToken();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->uuid('uuid')->unique();
                $table->string('slug')->nullable();
                $table->string('url')->nullable();
                $table->string('status', 1)->default('A');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->uuid('uuid')->unique();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('price_cost', 10, 2)->nullable();
                $table->decimal('promotional_price', 10, 2)->nullable();
                $table->integer('qtd_stock')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('flag')->nullable();
                $table->string('brand')->nullable();
                $table->string('sku')->nullable();
                $table->decimal('weight', 10, 3)->nullable();
                $table->decimal('height', 10, 2)->nullable();
                $table->decimal('width', 10, 2)->nullable();
                $table->decimal('depth', 10, 2)->nullable();
                $table->text('shipping_info')->nullable();
                $table->string('warehouse_location')->nullable();
                $table->json('variations')->nullable();
                $table->json('optionals')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('category_product')) {
            Schema::create('category_product', function (Blueprint $table) {
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->primary(['category_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('cpf')->nullable();
                $table->string('email')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->uuid('uuid')->unique();
                $table->string('url')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('zip_code')->nullable();
                $table->string('neighborhood')->nullable();
                $table->string('number')->nullable();
                $table->string('complement')->nullable();
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tables')) {
            Schema::create('tables', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('description')->nullable();
                $table->integer('seats')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('code');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('discount_value', 10, 2)->default(0);
                $table->decimal('max_discount_amount', 10, 2)->nullable();
                $table->decimal('minimum_order_amount', 10, 2)->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('usage_limit_per_client')->nullable();
                $table->integer('times_redeemed')->default(0);
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_featured')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'code']);
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('table_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
                $table->string('identify')->nullable();
                $table->decimal('total', 10, 2)->default(0);
                $table->decimal('subtotal', 10, 2)->nullable();
                $table->decimal('discount_amount', 10, 2)->nullable();
                $table->string('status')->default('Pendente');
                $table->boolean('is_delivery')->default(false);
                $table->boolean('use_client_address')->default(false);
                $table->json('coupon_snapshot')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_product')) {
            Schema::create('order_product', function (Blueprint $table) {
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->integer('qty')->default(1);
                $table->decimal('price', 10, 2)->default(0);
                $table->primary(['order_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->string('module')->nullable();
                $table->string('action')->nullable();
                $table->string('resource')->nullable();
                $table->string('group')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['slug', 'tenant_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('plans');
    }
};

