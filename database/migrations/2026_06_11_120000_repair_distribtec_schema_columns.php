<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repara colunas DistribTec ausentes quando migrations constam como executadas
 * mas o schema SQLite/MySQL ficou inconsistente (ex.: uso de ->after() no SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_order_items')) {
            Schema::table('sale_order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_order_items', 'item_type')) {
                    $table->string('item_type')->default('venda');
                }
                if (!Schema::hasColumn('sale_order_items', 'quantity_picked')) {
                    $table->decimal('quantity_picked', 12, 3)->default(0);
                }
                if (!Schema::hasColumn('sale_order_items', 'picked_at')) {
                    $table->timestamp('picked_at')->nullable();
                }
                if (!Schema::hasColumn('sale_order_items', 'picked_by')) {
                    $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('sale_orders')) {
            Schema::table('sale_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_orders', 'parent_sale_order_id')) {
                    $table->foreignId('parent_sale_order_id')->nullable()
                        ->constrained('sale_orders')->nullOnDelete();
                }
                if (!Schema::hasColumn('sale_orders', 'prescription_verified')) {
                    $table->boolean('prescription_verified')->default(false);
                }
            });
        }

        if (Schema::hasTable('accounts_receivable') && !Schema::hasColumn('accounts_receivable', 'sale_order_id')) {
            Schema::table('accounts_receivable', function (Blueprint $table) {
                $table->foreignId('sale_order_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        if (Schema::hasTable('accounts_payable') && !Schema::hasColumn('accounts_payable', 'purchase_order_id')) {
            Schema::table('accounts_payable', function (Blueprint $table) {
                $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        if (!Schema::hasTable('stock_reservations')) {
            Schema::create('stock_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sale_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sale_order_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
                $table->decimal('quantity', 12, 3);
                $table->string('status')->default('reserved');
                $table->timestamps();

                $table->index(['sale_order_id', 'status']);
                $table->index(['batch_id', 'status']);
            });
        }

        if (Schema::hasTable('orders') && Schema::hasTable('coupons')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'subtotal')) {
                    $table->decimal('subtotal', 10, 2)->nullable()->after('total');
                }
                if (!Schema::hasColumn('orders', 'discount_amount')) {
                    $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal');
                }
                if (!Schema::hasColumn('orders', 'coupon_id')) {
                    $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
                }
                if (!Schema::hasColumn('orders', 'coupon_snapshot')) {
                    $table->json('coupon_snapshot')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Reparo não revertido automaticamente — evita perda de dados em produção.
    }
};
