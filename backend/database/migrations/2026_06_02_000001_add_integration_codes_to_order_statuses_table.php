<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        Schema::table('order_statuses', function (Blueprint $table) {
            if (! Schema::hasColumn('order_statuses', 'integration_codes')) {
                $table->json('integration_codes')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        Schema::table('order_statuses', function (Blueprint $table) {
            if (Schema::hasColumn('order_statuses', 'integration_codes')) {
                $table->dropColumn('integration_codes');
            }
        });
    }
};
