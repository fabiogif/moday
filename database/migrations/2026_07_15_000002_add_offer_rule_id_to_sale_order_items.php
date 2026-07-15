<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->foreignId('offer_rule_id')->nullable()->after('batch_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offer_rule_id');
        });
    }
};
