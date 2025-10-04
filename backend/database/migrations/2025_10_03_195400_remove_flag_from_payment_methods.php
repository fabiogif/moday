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
        // Remove flag column if it exists
        if (Schema::hasColumn('payment_methods', 'flag')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropColumn('flag');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('flag')->nullable();
        });
    }
};