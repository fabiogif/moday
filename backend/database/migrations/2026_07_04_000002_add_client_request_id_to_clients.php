<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('clients', 'client_request_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('client_request_id', 64)->nullable()->after('uuid');
                $table->unique(['tenant_id', 'client_request_id'], 'clients_tenant_request_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'client_request_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropUnique('clients_tenant_request_id_unique');
                $table->dropColumn('client_request_id');
            });
        }
    }
};
