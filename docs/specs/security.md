# Security — práticas atuais

## Autenticação

- API dashboard/tenant: JWT (`auth:api`), token no header `Authorization: Bearer`
- Frontend: token em `localStorage['auth-token']` + cookie `auth-token` espelhado (não httpOnly, lido pelo `middleware.ts`)
- Loja pública: guard JWT `client` (provider `clients`), auth separada via `client-auth-context.tsx`
- **Não existe** guard `admin`/Sanctum ativo, nem painel admin protegido — os controllers em `Admin/*` não têm rota registrada

## Autorização

- Permissões: middleware `acl.permission` (`PermissionMiddleware`) + `User::hasPermission()`; também existem `CheckPermission`, `CheckAnyPermission`, `CheckRole`, `CheckAnyRole` como middlewares dedicados
- **Não existe** `plan.feature`, `plan.order_limit`, `plan.user_limit`, `PlanFeatureService`

## Multi-tenant

- `tenant_id` nos models de negócio; escopo aplicado manualmente (`where('tenant_id', ...)`) em cada repository/controller — **não existe** trait `BelongsToTenant` nem `AuthTenantService`
- Middlewares de tenant reais: `EnsureTenantAccess` (verifica `$user->tenant_id`), `EnsureCategoryTenant` — **não existem** `tenant.blocked`/`trial.check`
- Não vazar recursos de outro tenant (preferir 404/403 explícito, como em `EnsureTenantAccess`)

## Validação e input

- Validar no Form Request (padrão dominante, 42 classes) ou `validate()` nos poucos controllers que ainda usam inline
- Erros: não vazar mensagem interna sensível na resposta (`ApiResponseClass::rollback`/`throw` já filtram por `config('app.debug')`)

## Secrets

- Credenciais só em `.env` / secrets de CI
- Nunca commitar `.env`, JWT secret, credenciais de serviços externos
- Frontend: apenas `NEXT_PUBLIC_*` para valores públicos

## API

- Throttling real (`bootstrap/app.php`, `RateLimiter::for()`): `api`, `login`, `register`, `password-reset`, `critical`, `read`. `throttle:write` é usado em rotas mas **não tem limiter definido** — bug conhecido, não copiar sem corrigir
- CORS: `HandleCors` (padrão do Laravel), prepended no grupo `api` em `bootstrap/app.php`
- 401/403 padronizados via `ApiResponseClass`; `Handler.php` mapeia exceções JWT (`TokenExpiredException`, `TokenInvalidException`/`JWTException`, `UnauthorizedHttpException`) e `AuthenticationException`
- **Não existem** `SecurityHeadersMiddleware`, `GlobalCorsMiddleware`, `RequestIdMiddleware` customizados — não referenciar como padrão existente

## Frontend

- `src/middleware.ts` protege rotas do dashboard via listas hardcoded de rotas públicas/protegidas
- Não expor dados sensíveis em `NEXT_PUBLIC_*`
- Não há proteção de rota `/admin` porque não existe rota `/admin`

## Dados sensíveis em logs

- Evitar logar senha, token completo ou payload sensível ao usar `Log::`

## Checklist rápido antes de merge

- [ ] Endpoint autenticado com o guard adequado (`api`/`client`)
- [ ] Escopo por tenant verificado manualmente (sem trait automática)
- [ ] Permissão (`acl.permission`) quando o módulo exige
- [ ] Validação de input (Form Request)
- [ ] Sem secrets no código
