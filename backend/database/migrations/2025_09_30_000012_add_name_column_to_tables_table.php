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
        if (Schema::hasTable('tables')) {
            Schema::table('tables', function (Blueprint $table) {
                if (!Schema::hasColumn('tables', 'name')) {
                    $table->string('name')->after('identify');
                }
                if (!Schema::hasColumn('tables', 'capacity')) {
                    $table->integer('capacity')->default(4)->after('name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tables')) {
            Schema::table('tables', function (Blueprint $table) {
                if (Schema::hasColumn('tables', 'name')) {
                    $table->dropColumn('name');
                }
                if (Schema::hasColumn('tables', 'capacity')) {
                    $table->dropColumn('capacity');
                }
            });
        }
    }
};
