<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `tenants` foi criada por 0000_03_02_184902_create_tenants_table com
 * plan_id/cnpj/email NOT NULL e cnpj/email/name unique global. A versão em
 * 2025_11_09_000001_create_core_tables (plan_id/cnpj/email nullable, sem
 * unique em cnpj/email) nunca roda pelo mesmo motivo das outras tabelas
 * deste lote (Schema::hasTable já true). Fluxos reais de negócio precisam
 * de tenant sem plano (trial expirado, downgrade), sem email/cnpj
 * cadastrado ainda (onboarding incompleto) — e a checagem de "documento já
 * usado" em trial é feita via TrialFingerprint (app-level), não por unique
 * constraint em cnpj, então cnpj não pode ser unique no banco. email
 * mantém unique (SQLite/MySQL permitem múltiplos NULL em coluna unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        $legacyCnpjUnique = collect(Schema::getIndexes('tenants'))
            ->first(fn ($index) => $index['unique'] && !$index['primary'] && $index['columns'] === ['cnpj']);

        if ($legacyCnpjUnique) {
            Schema::table('tenants', function (Blueprint $table) use ($legacyCnpjUnique) {
                $table->dropUnique($legacyCnpjUnique['name']);
            });
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->change();
            $table->string('cnpj')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intencionalmente não reverte para NOT NULL: dados existentes podem
        // já ter sido gravados com plan_id/cnpj/email nulos.
    }
};
