<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('accounts_receivable', 'sale_order_id')) {
            Schema::table('accounts_receivable', function (Blueprint $table) {
                $table->foreignId('sale_order_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('accounts_payable', 'purchase_order_id')) {
            Schema::table('accounts_payable', function (Blueprint $table) {
                $table->foreignId('purchase_order_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('sale_order_items', 'item_type')) {
            Schema::table('sale_order_items', function (Blueprint $table) {
                $table->string('item_type')->default('venda');
                // venda | bonificacao | devolucao
            });
        }

        if (!Schema::hasColumn('sale_orders', 'parent_sale_order_id')) {
            Schema::table('sale_orders', function (Blueprint $table) {
                $table->foreignId('parent_sale_order_id')->nullable()->after('client_id')
                    ->constrained('sale_orders')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sale_orders', 'parent_sale_order_id')) {
                $table->dropForeign(['parent_sale_order_id']);
                $table->dropColumn('parent_sale_order_id');
            }
        });

        Schema::table('sale_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_order_items', 'item_type')) {
                $table->dropColumn('item_type');
            }
        });

        Schema::table('accounts_payable', function (Blueprint $table) {
            if (Schema::hasColumn('accounts_payable', 'purchase_order_id')) {
                $table->dropForeign(['purchase_order_id']);
                $table->dropColumn('purchase_order_id');
            }
        });

        Schema::table('accounts_receivable', function (Blueprint $table) {
            if (Schema::hasColumn('accounts_receivable', 'sale_order_id')) {
                $table->dropForeign(['sale_order_id']);
                $table->dropColumn('sale_order_id');
            }
        });
    }
};
