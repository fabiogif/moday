# Security — práticas atuais

## Autenticação

- API tenant: JWT (`auth:api`), token no header `Authorization: Bearer`
- Frontend: token em `localStorage['auth-token']` + cookie `auth-token` espelhado (não httpOnly, lido pelo `middleware.ts`)
- Admin: guard **Sanctum** separado (`admin`/`admin_users`), fluxo isolado (`admin-api-client.ts` + `admin-token` + `admin-auth-context.tsx`) — o `middleware.ts` do Next não protege `/admin`, a proteção é client-side
- Cliente loja: guard JWT `client` (provider `clients`), token `client-auth-token` (`client-auth-context.tsx`)

## Autorização

- Permissões: middleware `acl.permission:{resource.action}` (ex.: `users.index`) + `$user->hasPermissionTo`
- Features/limites de plano: `plan.feature:{key}` (ex.: `plan.feature:reports`), `plan.order_limit`, `plan.user_limit` via `PlanFeatureService` / tabela `plan_features`
- Tenant isolation: trait `BelongsToTenant` + `AuthTenantService::requireAuthenticatedTenant()`; não vazar recursos de outro tenant (preferir 404)

## Multi-tenant

- `tenant_id` nos models de negócio
- Middlewares `tenant.blocked`, `trial.check`
- Uploads sob path `tenants/{tenant_uuid}/…` via `FileUploadService` (ex.: `tenants/{tenant_uuid}/products`, `tenants/{tenant_uuid}/logos`)

## Validação e input

- Validar no Form Request (padrão dominante, 108 classes) ou `validate()` nos poucos controllers que ainda usam inline
- Upload: tipos/tamanho via `FileUploadService` / Form Request
- Erros de domínio: `\DomainException` capturada no controller — não vazar mensagem interna sensível na resposta

## Secrets

- Credenciais só em `.env` / secrets de CI
- Nunca commitar `.env`, tokens JWT secret, credenciais iFood, chaves Reverb/Pusher, Mercado Pago
- Frontend: apenas `NEXT_PUBLIC_*` para valores públicos

## API

- HTTPS em produção
- Throttling real (`bootstrap/app.php` + `AppServiceProvider`/`RouteServiceProvider`): `api`, `login`, `register`, `password-reset`, `critical`, `read`, `write`, `events`
- CORS: `GlobalCorsMiddleware` (global, prepended)
- Headers de segurança: `SecurityHeadersMiddleware` (global, appended)
- `RequestIdMiddleware` para correlação de logs
- 401/403 padronizados via `ApiResponseClass` / `Handler` (mapeia exceções JWT: `TokenExpiredException`, `TokenInvalidException`/`JWTException`, `UnauthorizedHttpException`)

## Frontend

- Middleware protege rotas do dashboard tenant via allow-list (`src/lib/auth-routes.ts`); `/admin` **não** é protegido pelo middleware, só client-side
- `AuthGuard` no layout do dashboard + `useTrialGuard`
- Não expor dados sensíveis em `NEXT_PUBLIC_*`
- 401 dispara evento `auth:unauthorized` que força logout

## Dados sensíveis em logs

- `ApiResponseClass::rollback`/`throw` + `Log::` — evitar logar senha, token completo, payload de pagamento (Mercado Pago), credenciais iFood

## Checklist rápido antes de merge

- [ ] Endpoint autenticado com o guard/middleware adequado (`api`/`client`/`admin`)
- [ ] Escopo por tenant verificado
- [ ] Permissão (`acl.permission`) ou plan feature/limit quando o módulo exige
- [ ] Validação de input
- [ ] Sem secrets no código
