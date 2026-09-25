<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Filtra automaticamente qualquer query por tenant_id quando existe um
 * usuário autenticado (guard `api`, usado pelo JwtMiddleware para
 * usuários do tenant) com tenant_id resolvido. Fora de um contexto HTTP
 * autenticado nesse guard (console, jobs, comandos, guards `client`/`admin`)
 * o escopo não aplica nenhum filtro — esses contextos já fazem filtro
 * manual explícito e continuam funcionando exatamente como antes.
 *
 * Importante: o guard padrão do Laravel neste projeto é `web`
 * (config/auth.php), mas o JwtMiddleware autentica explicitamente no
 * guard `api` (Auth::guard('api')->setUser($user)) sem tocar no guard
 * default. Por isso `Auth::user()` (sem guard) NÃO resolve o usuário do
 * tenant durante requisições de API — é obrigatório resolver via
 * `Auth::guard('api')` aqui, senão este scope nunca ativa.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Auth::guard('api')->user()?->tenant_id;

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}
