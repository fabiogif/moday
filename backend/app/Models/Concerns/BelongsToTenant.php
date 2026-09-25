<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;

/**
 * Aplica isolamento automático por tenant_id a qualquer query do model,
 * fechando a classe de bug de IDOR cross-tenant identificada na auditoria
 * (o filtro de tenant deixa de depender de cada repositório lembrar de
 * aplicar `where('tenant_id', ...)` manualmente).
 *
 * Para os poucos pontos legítimos de acesso cross-tenant (painel
 * administrativo da plataforma), use `Model::withoutTenantScope()`.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public static function withoutTenantScope()
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
