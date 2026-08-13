# Security — práticas atuais

## Autenticação

- API tenant: JWT (`auth:api`), token no header `Authorization: Bearer`
- Frontend: token em `localStorage` + cookie `auth-token` (middleware)
- Admin: fluxo/`admin-token` separado
- Cliente loja: `client-auth-token`

## Autorização

- Permissões: middleware `acl.permission:{name}` + `$user->hasPermissionTo`
- Features de plano: `plan.feature:{key}` via `PlanFeatureService` / tabela `plan_features`
- Tenant isolation: queries/`forTenant` + `AuthTenantService`; não vazar recursos de outro tenant (preferir 404)

## Multi-tenant

- `tenant_id` nos models de negócio
- Middlewares `tenant.blocked`, `trial.check`
- Uploads sob path `tenants/{tenant_uuid}/…` (ex.: produtos)

## Validação e input

- Validar no Form Request ou `validate()`
- Upload: tipos/tamanho via `FileUploadService` / Form Request (`image`, mime, max size)
- URLs remotas (imagem barcode): validar scheme http/https antes de baixar

## Secrets

- Credenciais só em `.env` / secrets de CI
- Nunca commitar `.env`, tokens Cosmos/Maps/JWT secret
- Frontend: apenas `NEXT_PUBLIC_*` para valores públicos

## API

- HTTPS em produção
- Throttling: `throttle:login`, `throttle:critical`, `throttle:read`, etc.
- CORS configurado no backend
- 401/403 padronizados via `ApiResponseClass` / Handler JWT

## Frontend

- Middleware protege rotas dashboard
- `AuthGuard` no layout
- Não expor dados sensíveis em `NEXT_PUBLIC_*`
- Proxy de imagem (`internal-api`) limita a http(s) e content-type image

## Dados sensíveis em logs

- `ApiResponseClass::rollback` / `Log::` — evitar logar senha, token completo, payload de cartão

## Checklist rápido antes de merge

- [ ] Endpoint autenticado com middleware adequado
- [ ] Escopo por tenant verificado
- [ ] Permissão ou plan feature quando o módulo exige
- [ ] Validação de input
- [ ] Sem secrets no código
