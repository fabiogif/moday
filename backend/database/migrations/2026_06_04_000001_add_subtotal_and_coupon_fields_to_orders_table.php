<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->nullable()->after('total');
            }

            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal');
            }

            if (!Schema::hasColumn('orders', 'coupon_id') && Schema::hasTable('coupons')) {
                $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'coupon_snapshot')) {
                $table->json('coupon_snapshot')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $columns = ['subtotal', 'discount_amount', 'coupon_id', 'coupon_snapshot'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    if ($column === 'coupon_id') {
                        $table->dropForeign(['coupon_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
