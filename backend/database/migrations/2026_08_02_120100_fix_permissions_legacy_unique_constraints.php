<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
 *
 * Usa Schema::getIndexes() (driver-agnostic, Laravel 11+) em vez de consultar
 * information_schema direto: a versão anterior só rodava em mysql e assumia
 * que o SQLite (usado nos testes) já tinha o índice certo via
 * 2025_11_09_000001_create_core_tables — suposição falsa, já que o
 * Schema::hasTable('permissions') pula esse bloco igualmente no SQLite,
 * deixando o mesmo índice legado global também nos testes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $indexes = Schema::getIndexes('permissions');

        foreach ($indexes as $index) {
            if ($index['unique'] && ! $index['primary'] && $index['columns'] === ['slug']) {
                Schema::table('permissions', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index['name']);
                });
            }
            if ($index['unique'] && ! $index['primary'] && $index['columns'] === ['name']) {
                Schema::table('permissions', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index['name']);
                });
            }
        }

        $hasCompositeUnique = collect(Schema::getIndexes('permissions'))
            ->contains(fn ($index) => $index['unique'] && in_array($index['columns'], [['slug', 'tenant_id'], ['tenant_id', 'slug']], true));

        if (! $hasCompositeUnique) {
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
