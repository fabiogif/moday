<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A tabela `permissions` deste ambiente foi originalmente criada por uma
 * migration mais antiga (substituída depois por 2025_11_09_000001_create_core_tables,
 * cujo `Schema::hasTable('permissions')` a torna um no-op quando a tabela já
 * existe). Essa versão antiga tem índices únicos globais em `name` e `slug`
 * em vez de tenant-scoped, o que impede provisionar ACL para um segundo
 * tenant sempre que dois tenants usam o mesmo catálogo fixo de permissões
 * (AclPermissionDefinitions) — cenário normal em qualquer registro novo.
 *
 * Esta migration remove os índices globais legados (se existirem) e garante
 * o índice composto ['slug', 'tenant_id'] já definido no código-fonte atual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            // SQLite (usado em testes) já cria a tabela com o índice correto
            // via 2025_11_09_000001_create_core_tables — nada a fazer aqui.
            return;
        }

        $legacyIndexes = DB::select(
            "SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions'
             AND INDEX_NAME IN ('permissions_name_unique', 'permissions_slug_unique')"
        );

        foreach ($legacyIndexes as $index) {
            DB::statement("ALTER TABLE `permissions` DROP INDEX `{$index->INDEX_NAME}`");
        }

        $hasCompositeUnique = (bool) DB::selectOne(
            "SELECT COUNT(*) AS total FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions'
             AND INDEX_NAME = 'permissions_slug_tenant_id_unique'"
        )->total;

        if (!$hasCompositeUnique) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->unique(['slug', 'tenant_id']);
            });
        }
    }

    public function down(): void
    {
        // Intencionalmente irreversível: os índices globais removidos eram a
        // causa raiz do bug (bloqueavam provisionamento multi-tenant). Não
        // recriamos esse estado incorreto.
    }
};
