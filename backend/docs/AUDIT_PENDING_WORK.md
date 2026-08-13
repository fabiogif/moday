# Pendências da auditoria de arquitetura — backlog para plano futuro

> Contexto: em 2026-07-22 foi feita uma auditoria completa de arquitetura do backend (SOLID, Clean Architecture, DRY, segurança, performance) e, na mesma sessão, foi implementado o Sprint 1 completo e partes dos Sprints 2-4 do roadmap. Este documento lista especificamente o que **não** foi feito, com o motivo de cada corte de escopo, para servir de entrada a um plano de implementação futuro. Não é um relatório do que já foi feito — para isso, ver o histórico de commits/diff a partir de 2026-07-22.

---

## 1. RBAC nas rotas de negócio (maior prioridade, maior impacto de segurança)

**Status:** infraestrutura pronta, enforcement **não ativado**.

O que já existe e pode ser reaproveitado:
- `TenantAclBootstrapService::permissionDefinitions()` já tem as definições de permissão para `coupons`, `loyalty`, `financial-categories`, `suppliers`, `expenses`, `accounts-payable`, `accounts-receivable` (além dos módulos que já existiam: users, profiles, permissions, products, orders, categories, tables, clients, reports).
- `app/Console/Commands/AclBackfillTenants.php` (`php artisan acl:backfill`) — idempotente, sincroniza as permissões de todos os tenants existentes com o perfil Administrador.
- O mapeamento módulo→slug para cada rota já foi todo pensado (ver histórico do diff de `routes/api.php` em 2026-07-22, revertido nesta sessão) — é só reaplicar.

**Por que não foi ativado:** ao aplicar `acl.permission:*` nas rotas de Produto/Pedido/Cupom/Financeiro/Fidelidade, **189 testes quebraram** (19% da suíte). Causa raiz: `UserService`/o fluxo de criação de usuário só atribui um `Profile` ao novo usuário se o payload da requisição explicitamente mandar um array `profiles`. Isso não é só um problema de fixture de teste — é um gap real de produção: hoje, um dono de conta que cria um novo funcionário pelo painel, sem marcar manualmente as permissões, cria um usuário **sem nenhuma permissão**, que ficaria travado em qualquer rota gateada.

**Pré-requisitos antes de ativar o RBAC nessas rotas:**
1. Decidir e implementar um comportamento padrão de "perfil ao criar usuário": ou (a) todo novo usuário criado sem `profiles` explícito recebe automaticamente o perfil Administrador/padrão do tenant (via `TenantAclBootstrapService`), ou (b) a criação de usuário passa a exigir `profiles` explicitamente no payload (mudança de contrato de API, precisa de coordenação com o frontend).
2. Rodar `php artisan acl:backfill` em produção para garantir que todos os tenants existentes tenham as novas permissões (coupons/loyalty/financial) sincronizadas no perfil Administrador **antes** do deploy do enforcement.
3. Atualizar os ~25 arquivos de teste que criam `User::factory()->create(['tenant_id' => ...])` diretamente sem passar por `TenantAclBootstrapService` (padrão usado em `LoyaltyProgramApiTest`, `SupplierApiTest`, `FinancialCategoryApiTest`, `AccountPayableApiTest`, `AccountReceivableApiTest`, `ExpenseApiTest`, `Admin/CouponApiTest`, entre outros — rodar a suíte completa com o RBAC habilitado mostra a lista exata a cada tentativa).
4. Só então reaplicar `acl.permission:<module>.<action>` em `routes/api.php` nos grupos de Produto (linhas ~213-222), Pedido (~230-249), Cupom (`marketing/coupons`), Financeiro (financial-categories/suppliers/expenses/accounts-payable/accounts-receivable) e Fidelidade (`loyalty`), seguindo exatamente o padrão já usado no grupo `users` (`Route::middleware(['acl.permission:users.index', 'throttle:read'])`).

---

## 2. Refatorações de qualidade não concluídas (do Sprint 3 planejado)

### 2.1 `OrderLifecycleService::updateOrder` continua um método de ~150 linhas
Não foi extraído em métodos menores (`resolveStatus()`, `sanitizeAddress()`) como planejado — deu tempo só para os itens de maior risco de segurança. Continua fazendo autorização, resolução de status, whitelist de campos, limpeza de endereço, recálculo de total e persistência tudo num método só. Refatoração é segura (mesmo comportamento, só reorganização), mas precisa de atenção para não alterar a ordem de validações.

### 2.2 Cálculo de total de pedido não foi unificado
`OrderCreationService::getTotalOrder()` (simples: `qty * price`) e `PublicOrderCalculationService::calculateOrder()` (mais completo: soma preço promocional + optionals) continuam duplicados e divergentes. **Não foi unificado nesta sessão porque é uma mudança em lógica financeira usada em dois fluxos (PDV e loja pública) sem cobertura de teste suficiente para garantir que a unificação não mudaria valores reais de pedidos já em produção.** Antes de mexer, mapear: o PDV hoje permite adicionar optionals a um pedido? Se sim, `OrderCreationService::getTotalOrder` pode já estar subestimando o total em pedidos com optionals — vale investigar como bug real antes de tratar como só um problema de DRY.

### 2.3 `ClientService::calculateClientStats` continua com ~127 linhas de `DB::table`/joins no Service
Não foi movido para `ClientRepository`. Repository já existe (`ClientRepositoryInterface`), só falta criar `getClientStats(int $tenantId)` lá e fazer o Service delegar.

### 2.4 Controllers ainda injetam Repository diretamente, pulando o Service
Não mexido nesta sessão:
- `ReviewApiController` injeta `TenantRepositoryInterface` (usado em `publicIndex`/`publicFeatured`/`publicStats` para resolver tenant por slug)
- `Http/Controllers/Api/Integrations/Ifood/IfoodAuthController` injeta `IfoodTokenRepositoryInterface`
- `Admin/PermissionController` e `Admin/ProfileController` injetam o Model Eloquent como se fosse repositório

### 2.5 Hierarquia de exceções de domínio não foi criada
Ainda existem ~89 ocorrências de `throw new \Exception(...)` genérico espalhadas pelo projeto (fora dos arquivos tocados nesta sessão). Não foi criada `App\Exceptions\Domain\{BusinessRuleException, ResourceNotFoundException, UnauthorizedActionException}`. Escopo completo (varrer as 89 ocorrências) é grande demais para uma sessão só — melhor abordar por módulo.

---

## 3. Performance/padronização não concluídas (do Sprint 4 planejado)

### 3.1 `CacheService::invalidateOrderListCache` continua enumerando combinações de `per_page` manualmente
Não convertido para `SCAN` como foi feito em `CacheAnalyze.php`. Motivo: esse método é usado em fluxos com cobertura de teste que verificam mensagens de log específicas (`ListingCacheTest`), e a implementação via `Cache::forget()` (não `Redis::` direto) tornaria a conversão para SCAN mais delicada — precisa garantir que funciona igual nos drivers `array` (testes) e `redis` (produção) antes de trocar.

### 3.2 `ReportService::shouldProcessAsync()` continua sem uso
Não foi ligado a um fluxo real. Ativar isso de verdade exige: criar um Job de geração de relatório, um endpoint de consulta de status/download quando pronto, e mudar o contrato de resposta da API de relatórios (hoje síncrono, retorna o arquivo direto) — o que precisa de coordenação com o frontend. Maior que um ajuste pontual de backend.

### 3.3 Padronização do envelope de resposta HTTP não foi feita
Projeto inteiro ainda tem 3 padrões coexistindo: `ApiResponseClass` (maioria), `response()->json()` cru (~17 controllers, incl. todo `AdminTenantController`), e helpers do `BaseController` (usado só por 2 controllers). Fora de escopo de uma sessão — precisa de uma varredura dedicada.

### 3.4 Enums não foram expandidos
Status de pedido continua como string solta em vários arquivos (`OrderWorkflowService`, `OrderCreationService`, `IfoodOrderHttpAdapter`). Único Enum real do projeto continua sendo `CategoryStatus`. Renomear/tipar isso em todo o projeto é arriscado sem suíte de testes E2E rodando — não incluído.

---

## 4. Itens do relatório de auditoria original (2026-07-22) nunca abordados nesta sessão

Esses itens estavam no relatório publicado como artifact, mas fora do escopo dos Sprints 1-4 que foram efetivamente planejados e executados:

- **God Objects** não quebrados em responsabilidades menores: `PublicStoreController` (533 linhas, 9 dependências), `OrderApiController` (531 linhas, inclui geração manual de iCalendar que deveria virar um Formatter dedicado), `CacheService` (540 linhas), `FileUploadService` (480 linhas), `ReviewApiController` (481 linhas).
- **Validação inline** (`$request->validate()` em vez de FormRequest) em ~10 arquivos, concentrada no módulo iFood (`IfoodCatalogController`) e em telas Admin legadas.
- **Mass assignment inconsistente**: `ClientApiController::store/update` e `ProductApiController::store/update` ainda usam `$request->all()`/`->except()` em vez de `->validated()` mesmo tendo FormRequest tipado.
- **Divergência 404 vs 403** para acesso cross-tenant entre `ProductApiController` (404, esconde existência) e `ClientApiController` (403, revela existência) — inconsistência de padrão de segurança.
- **Policies faltando** para os demais Models de negócio (Order, Client, Coupon, AccountPayable/Receivable, Expense, Supplier, LoyaltyProgram/Reward, Event, Table) — só `ProductPolicy` foi implementada nesta sessão.
- **`AuthService`** ainda chama `Cache::put/get/forget` diretamente para chaves de usuário, em paralelo ao `CacheService` centralizado usado pelo resto do sistema; `login()` usa o repositório mas `sendPasswordResetLink()` faz `User::where('email', ...)` direto.
- **Report Builders** (`DailySalesReportBuilder`, `MonthlyFinancialReportBuilder`, `TableOccupancyReportBuilder`) ainda reexecutam a query em `getMetadata()` além de `build()` — hoje inofensivo porque `getMetadata()` não é chamado por ninguém, mas seria um problema se algum endpoint futuro passar a usá-lo.
- **`Reports\Queries\ClientsReportQuery`** ainda carrega a coleção inteira de pedidos de cada cliente só para achar a última compra em memória, em vez de um `withMax`/subselect agregado em SQL.
- **`AppServiceProvider`** ainda tem fail-open silencioso no binding de `IfoodAuthPort`: se a config do iFood estiver incompleta, cria o Adapter mesmo assim com strings vazias em vez de falhar rápido no boot.
- **Organização de `app/Http/Requests`**: Produto e Cliente têm FormRequests soltos na raiz da pasta além de outros dentro de `Api/`, enquanto Cupom está inteiramente em `Api/` — inconsistência de convenção, não crítico.

---

## Como usar este documento

Cada seção acima é independente e pode virar um item de plano isolado. A ordem sugerida de prioridade para um próximo ciclo:

1. Seção 1 (RBAC) — maior impacto de segurança, mas precisa da decisão de produto sobre atribuição de perfil padrão antes de começar a codar.
2. Seção 2.2 (total de pedido) — investigar primeiro se é bug real de produção (optionals não contados no PDV), não só debt de DRY.
3. Seções 2.1, 2.3, 2.4, 2.5 — refatorações de qualidade, baixo risco, podem ser feitas incrementalmente.
4. Seção 3 e 4 — performance/padronização, sem urgência.
