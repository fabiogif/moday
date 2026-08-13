<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_order_items')) {
            return;
        }

        if (!Schema::hasColumn('sale_order_items', 'offer_rule_id')) {
            Schema::table('sale_order_items', function (Blueprint $table) {
                $table->foreignId('offer_rule_id')->nullable()->after('batch_id')
                    ->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_order_items') && Schema::hasColumn('sale_order_items', 'offer_rule_id')) {
            Schema::table('sale_order_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('offer_rule_id');
            });
        }
    }
};
