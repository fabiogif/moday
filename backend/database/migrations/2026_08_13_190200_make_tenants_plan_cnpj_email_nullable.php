<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `tenants` foi criada por 0000_03_02_184902_create_tenants_table com
 * plan_id/cnpj/email NOT NULL. A versão em 2025_11_09_000001_create_core_tables
 * (plan_id nullable) nunca roda pelo mesmo motivo das outras tabelas deste
 * lote (Schema::hasTable já true). Fluxos reais de negócio precisam de tenant
 * sem plano (trial expirado, downgrade) e sem email/cnpj cadastrado ainda
 * (onboarding incompleto) — mantém unique (SQLite/MySQL permitem múltiplos
 * NULL em coluna unique), só remove o NOT NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
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
