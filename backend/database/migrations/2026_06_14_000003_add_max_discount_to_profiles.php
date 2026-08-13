<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'max_discount_percent')) {
                // null = no limit; 0 = cannot give any discount; 100 = can give up to 100%
                $table->decimal('max_discount_percent', 5, 2)->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'max_discount_percent')) {
                $table->dropColumn('max_discount_percent');
            }
        });
    }
};
