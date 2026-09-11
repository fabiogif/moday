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

## Testes de autorização (OWASP: Authorization Regression / Authorization Testing Automation)

- Hoje a cobertura é via `backend/tests/Feature/**` (ex.: `Admin/AdminTenantTest.php`, `AccountPayableApiTest.php`) — cada suíte testa permissão/tenant do próprio módulo, mas não existe uma matriz de autorização centralizada (papel × recurso × ação) nem teste dedicado de escalonamento
- Ao criar/alterar endpoint com `acl.permission` ou escopo de tenant, cobrir no teste de Feature:
  - **Horizontal**: usuário do tenant A não acessa recurso do tenant B (esperar `403`/`404`, nunca `200`)
  - **Vertical**: usuário sem a permissão não acessa a rota (esperar `403`)
  - A validação de autorização é sempre no backend (middleware/`hasPermissionTo`) — nunca confiar em esconder botão/rota no frontend como controle de acesso
- Não há gate de CI que rode essa suíte automaticamente a cada PR (ver seção CI/CD abaixo) — rodar `php artisan test` localmente antes de abrir PR em endpoint sensível

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

## Criptografia (OWASP A02)

- Senhas: hash via `Hash::` (bcrypt, padrão Laravel) — nunca comparar/armazenar senha em texto puro
- Payload de sessão do backend passa por `unserialize()` em `HybridSessionHandler::extractUserId` — dado é interno (gravado pelo próprio driver de sessão do Laravel), não input de usuário; não reaproveitar esse padrão para desserializar dado externo (OWASP A08)
- Dados sensíveis (tokens de pagamento, credenciais de integração) só em trânsito/`.env`, nunca em coluna de banco sem necessidade

## Dependências (OWASP A06)

- Sem verificação automatizada de dependências vulneráveis no CI hoje (`composer audit` / `npm audit` / Dependabot)
- Rodar `composer audit` (backend) e `npm audit` (frontend) manualmente antes de releases até haver step de CI dedicado

## API

- HTTPS em produção
- Throttling real (`bootstrap/app.php` + `AppServiceProvider`/`RouteServiceProvider`): `api`, `login`, `register`, `password-reset`, `critical`, `read`, `write`, `events`
- CORS: `GlobalCorsMiddleware` (global, prepended)
- Headers de segurança: `SecurityHeadersMiddleware` (global, appended) — `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy: frame-ancestors 'none'`, `Cache-Control: no-store`, `Strict-Transport-Security` (quando HTTPS). Conjunto alinhado ao OWASP REST Security Cheat Sheet para API consumida por browser (frontend Next.js) — resposta de API é JSON, nunca deve ser cacheada nem enquadrada em `<iframe>`.
- `RequestIdMiddleware` para correlação de logs
- 401/403 padronizados via `ApiResponseClass` / `Handler` (mapeia exceções JWT: `TokenExpiredException`, `TokenInvalidException`/`JWTException`, `UnauthorizedHttpException`)
- SSRF (OWASP A10): nenhuma chamada HTTP hoje usa URL vinda de input do usuário. Se uma integração futura (webhook, avatar/logo por URL, callback iFood/Mercado Pago) precisar buscar uma URL fornecida pelo tenant, validar/whitelistar host e bloquear ranges internos (RFC1918, `169.254.169.254`) antes do fetch.
- Anti-bot (OWASP Bot Management): throttle por rota (`login`, `register`, `password-reset`) já existe, mas é só por IP/rota — sem captcha, sem limite por identidade (username) separado do limite por IP, sem honeypot em formulários públicos (cadastro, login, loja do cliente). Se abuso automatizado virar problema real (credential stuffing, criação de conta em massa), adicionar limite por identidade além do por IP antes de partir para captcha.

## Avaliação de segurança de API REST (OWASP REST Assessment)

Guia para quem for fazer teste de penetração/red-team na API (`backend/routes/api.php`):

- **Superfície de ataque não vem de inspecionar o frontend** — o Next.js só chama o subconjunto de rotas que a UI usa. Fonte de verdade real: `php artisan route:list` no backend (equivalente a um WADL, e mais confiável que ele) e os arquivos `routes/api.php`/`routes/auth.php`.
- **Capturar tráfego completo com proxy** (Burp/mitmproxy) direto no backend, não só o que a UI dispara — corpo JSON, headers customizados e query string, não só a URL.
- **Autenticação é customizada e tripla** — não existe um único mecanismo a reverse-engenheirar: JWT tenant (`Authorization: Bearer`, guard `api`), Sanctum admin (`admin`/`admin_users`), JWT client (guard `client`). Qualquer ferramenta de scan automatizado precisa emular os três separadamente; um scan que só segue o login do dashboard tenant não cobre `/admin` nem a loja do cliente.
- **Parâmetros não padronizados a procurar**: segmentos de UUID/tenant na URL (`tenants/{tenant_uuid}/...` em upload), headers customizados (`X-Request-ID`, `x-signature`/`x-request-id` no webhook do Mercado Pago, `X-CSRF-TOKEN` quando a rota usa o middleware `csrf.api`).
- **Validar suspeita de parâmetro vs. path fixo**: enviar um tenant_uuid/ID inválido — resposta `404` do framework indica path physical; resposta de erro de negócio (ex.: JSON de validação) indica que é parâmetro de aplicação.
- Ao fazer fuzzing, emular corretamente o mecanismo de auth do endpoint alvo (guard errado retorna 401 sempre, mascarando falso-positivo/negativo).

## REST Security Cheat Sheet — aplicação ao backend

- **HTTPS obrigatório** — já coberto em `## API`. Sem mTLS hoje; não é necessário para o perfil atual (SaaS multi-tenant via browser/app, não serviço-a-serviço de alto privilégio).
- **Controle de acesso local por endpoint** — cada rota decide via middleware (`auth:api`/`auth:admin`/`auth:client` + `acl.permission`), não depende de gateway central. Consistente com a seção `## Autorização`.
- **JWT**: algoritmo fixo `HS256` (`config/jwt.php`, não aceita `alg:none`), `blacklist_enabled=true` (permite invalidar token no logout), `ttl`/`refresh_ttl` configurados. Claims `exp`/`iss` são validados pela lib `tymon/jwt-auth`; garantir que isso não seja customizado para pular validação.
- **Chaves de API**: não há endpoint público sem controle de acesso hoje que dependa só de API key — throttling (`## API`) cobre o caso de abuso de rota pública (ex.: loja do cliente).
- **Restringir métodos HTTP**: rotas usam `Route::get/post/put/delete` explícitos (não `Route::any`) — método fora da lista já recebe `405` do próprio Laravel. Manter esse padrão ao adicionar rota nova; nunca usar `Route::any`/`match` amplo sem necessidade.
- **Execução de API fora de ordem**: fluxos de workflow (checkout/pedido) já têm teste dedicado de validação de etapa (`OrderValidateStepApiTest`, `ClientValidateStepApiTest`) — ao adicionar novo fluxo multi-etapa (ex.: novo método de pagamento), replicar o padrão: validar estado atual no servidor antes de aceitar a transição, nunca confiar que o frontend chamou os passos em ordem.
- **Validação de entrada**: Form Request é o padrão (`## Validação e input`). Reforçar: usar tipo forte (int/enum/date) no Form Request em vez de string solta sempre que possível; `\App\Exceptions\Handler` não expõe stack trace ao cliente quando `APP_DEBUG=false` — **confirmar que `APP_DEBUG=false` em produção**, nunca setar `true` fora de dev/CI.
- **Tipos de conteúdo**: API responde JSON; não há parser XML em uso (sem risco de XXE hoje). Se uma integração futura aceitar XML, usar parser com entidades externas desabilitadas.
- **Endpoints de gerenciamento**: sem Telescope/Horizon roteado hoje (nada encontrado em `routes/*.php`) — se forem adicionados no futuro, nunca expor na mesma rota pública sem autenticação forte e, preferencialmente, atrás de allow-list de IP.
- **Tratamento de erro**: mensagens genéricas via `ApiResponseClass`/`Handler` (`## API`) — manter, nunca vazar `getMessage()` de exceção interna direto na resposta.
- **Dados sensíveis em URL**: nenhuma rota hoje passa senha/token/API key em query string — manter esse padrão; credencial só em header/body.
- **Códigos de resposta HTTP**: já padronizados via `ApiResponseClass` (`## API`) — usar o código semântico correto (`401` vs `403` vs `404` vs `429`), nunca `200` com `{"success": false}` para erro de autorização.
- CORS: único middleware ativo é `GlobalCorsMiddleware` (`bootstrap/app.php`, `prepend`). Isso é deliberado, não descuido — confirmado também no produto irmão (distribtec, mesma origem de código):
  - `HandleCors` nativo do Laravel está **comentado** em `app/Http/Kernel.php` — dois middlewares CORS ativos ao mesmo tempo causavam headers duplicados/inconsistentes.
  - `config/cors.php` está **desabilitado de propósito** (`allowed_origins: []`, `supports_credentials: false`).
  - `public/.htaccess` **não define** headers CORS — um `Access-Control-Allow-Origin: *` hardcoded aqui já causou incidente de produção (conflito com `credentials: include` do frontend); não reintroduzir.
  - `CustomCorsMiddleware` era código morto de uma correção antiga (não registrado em nenhum lugar) e foi removido — não recriar essa segunda implementação; qualquer ajuste de CORS entra em `GlobalCorsMiddleware`.

## Frontend

- Middleware protege rotas do dashboard tenant via allow-list (`src/lib/auth-routes.ts`); `/admin` **não** é protegido pelo middleware, só client-side
- `AuthGuard` no layout do dashboard + `useTrialGuard`
- Não expor dados sensíveis em `NEXT_PUBLIC_*`
- 401 dispara evento `auth:unauthorized` que força logout

## Dados sensíveis em logs (OWASP A09)

- `ApiResponseClass::rollback`/`throw` + `Log::` — evitar logar senha, token completo, payload de pagamento (Mercado Pago), credenciais iFood
- Sem alerta/monitoramento automatizado de eventos de segurança (ex.: pico de 401/403, falhas de login repetidas) — hoje depende de leitura manual de log

## CI/CD (OWASP CI/CD Security)

- Não há pipeline de CI hoje (sem `.github/workflows/`) — testes, `composer audit`/`npm audit` e lint rodam só manualmente; deploy é manual no servidor de produção
- Até existir CI: branch `main` deve ser protegida (exigir PR + review antes de merge), nunca commitar secret (ver seção Secrets), nunca dar `git push --force` em `main`
- Quando um pipeline for criado, aplicar o básico: rodar `php artisan test` + `composer audit` + `npm audit`/build do frontend como check obrigatório, secrets do deploy só como CI secret (nunca hardcoded no workflow), permissão do runner com o mínimo necessário

## Checklist rápido antes de merge

- [ ] Endpoint autenticado com o guard/middleware adequado (`api`/`client`/`admin`)
- [ ] Escopo por tenant verificado
- [ ] Permissão (`acl.permission`) ou plan feature/limit quando o módulo exige
- [ ] Validação de input
- [ ] Sem secrets no código

## Referências

- OWASP Cheat Sheet Series — Index Top Ten: https://cheatsheetseries.owasp.org/IndexTopTen.html
- Authorization Regression Testing: https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Regression_Testing_Cheat_Sheet.html
- Authorization Testing Automation: https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Testing_Automation_Cheat_Sheet.html
- Bot Management and Anti-Automation: https://cheatsheetseries.owasp.org/cheatsheets/Bot_Management_and_Anti-Automation_Cheat_Sheet.html
- CI/CD Security: https://cheatsheetseries.owasp.org/cheatsheets/CI_CD_Security_Cheat_Sheet.html
- REST Assessment (guia de teste de penetração REST): https://cheatsheetseries.owasp.org/cheatsheets/REST_Assessment_Cheat_Sheet.html
- REST Security: https://cheatsheetseries.owasp.org/cheatsheets/REST_Security_Cheat_Sheet.html
