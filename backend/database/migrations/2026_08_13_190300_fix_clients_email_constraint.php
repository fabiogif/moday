<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `clients` foi criada por 2023_06_17_150230_create_clients_table com
 * email NOT NULL + unique global. A versão pretendida em
 * 2025_11_09_000001_create_core_tables (email nullable, sem unique — cada
 * tenant tem seu próprio client_request_id) nunca roda pelo mesmo motivo
 * das outras tabelas deste lote. Isso impedia dois tenants diferentes de
 * terem clientes com o mesmo email, e impedia cliente sem email.
 */
return new class extends Migration
{
    public function up(): void
    {
        $legacyUnique = collect(Schema::getIndexes('clients'))
            ->first(fn ($index) => $index['unique'] && !$index['primary'] && $index['columns'] === ['email']);

        if ($legacyUnique) {
            Schema::table('clients', function (Blueprint $table) use ($legacyUnique) {
                $table->dropUnique($legacyUnique['name']);
            });
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intencionalmente não reverte: dados existentes podem já ter clients
        // com email duplicado entre tenants ou nulo.
    }
};
