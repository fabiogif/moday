<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $cols = [
        'cnpj', 'company_name', 'trade_name', 'state_registration',
        'contact_name', 'contact_phone', 'contact_email', 'whatsapp',
        'client_type', 'credit_limit', 'payment_term_days', 'price_table_id',
        'is_blocked', 'blocked_reason', 'notes', 'deleted_at',
    ];

    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'cnpj'))
                $table->string('cnpj')->nullable()->after('name');
            if (!Schema::hasColumn('clients', 'company_name'))
                $table->string('company_name')->nullable()->after('cnpj');
            if (!Schema::hasColumn('clients', 'trade_name'))
                $table->string('trade_name')->nullable()->after('company_name');
            if (!Schema::hasColumn('clients', 'state_registration'))
                $table->string('state_registration')->nullable()->after('trade_name');
            if (!Schema::hasColumn('clients', 'contact_name'))
                $table->string('contact_name')->nullable();
            if (!Schema::hasColumn('clients', 'contact_phone'))
                $table->string('contact_phone')->nullable();
            if (!Schema::hasColumn('clients', 'contact_email'))
                $table->string('contact_email')->nullable();
            if (!Schema::hasColumn('clients', 'whatsapp'))
                $table->string('whatsapp')->nullable();
            if (!Schema::hasColumn('clients', 'client_type'))
                $table->string('client_type')->default('outro');
            if (!Schema::hasColumn('clients', 'credit_limit'))
                $table->decimal('credit_limit', 12, 2)->default(0);
            if (!Schema::hasColumn('clients', 'payment_term_days'))
                $table->integer('payment_term_days')->default(30);
            if (!Schema::hasColumn('clients', 'price_table_id'))
                $table->unsignedBigInteger('price_table_id')->nullable();
            if (!Schema::hasColumn('clients', 'is_blocked'))
                $table->boolean('is_blocked')->default(false);
            if (!Schema::hasColumn('clients', 'blocked_reason'))
                $table->text('blocked_reason')->nullable();
            if (!Schema::hasColumn('clients', 'notes'))
                $table->text('notes')->nullable();
            if (!Schema::hasColumn('clients', 'deleted_at'))
                $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach ($this->cols as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
