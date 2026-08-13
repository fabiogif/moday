<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('ordered_at');
            $table->boolean('is_scheduled')->default(false)->after('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'is_scheduled']);
        });
    }
};
