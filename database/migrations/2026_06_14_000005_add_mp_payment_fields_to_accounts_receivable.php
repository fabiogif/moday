<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_receivable', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts_receivable', 'mp_preference_id')) {
                $table->string('mp_preference_id')->nullable()->after('notes');
                $table->string('mp_payment_link')->nullable()->after('mp_preference_id');
                $table->string('mp_payment_id')->nullable()->after('mp_payment_link');
                $table->timestamp('mp_link_generated_at')->nullable()->after('mp_payment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->dropColumn(['mp_preference_id', 'mp_payment_link', 'mp_payment_id', 'mp_link_generated_at']);
        });
    }
};
