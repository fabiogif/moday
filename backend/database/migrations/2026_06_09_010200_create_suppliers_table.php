<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // suppliers table already exists from financial module — add B2B distributor fields
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'cnpj'))
                $table->string('cnpj')->nullable()->after('id');
            if (!Schema::hasColumn('suppliers', 'company_name'))
                $table->string('company_name')->nullable();
            if (!Schema::hasColumn('suppliers', 'trade_name'))
                $table->string('trade_name')->nullable();
            if (!Schema::hasColumn('suppliers', 'state_registration'))
                $table->string('state_registration')->nullable();
            if (!Schema::hasColumn('suppliers', 'whatsapp'))
                $table->string('whatsapp')->nullable();
            if (!Schema::hasColumn('suppliers', 'contact_name'))
                $table->string('contact_name')->nullable();
            if (!Schema::hasColumn('suppliers', 'contact_phone'))
                $table->string('contact_phone')->nullable();
            if (!Schema::hasColumn('suppliers', 'payment_term_days'))
                $table->integer('payment_term_days')->default(30);
            if (!Schema::hasColumn('suppliers', 'min_order_value'))
                $table->decimal('min_order_value', 10, 2)->nullable();
            if (!Schema::hasColumn('suppliers', 'lead_time_days'))
                $table->integer('lead_time_days')->default(7);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $cols = ['cnpj', 'company_name', 'trade_name', 'state_registration',
                     'whatsapp', 'contact_name', 'contact_phone',
                     'payment_term_days', 'min_order_value', 'lead_time_days'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('suppliers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
