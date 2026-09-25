# Tasks

## 1. Database & Models

- [x] 1.1 Create migration restoring `ifood_*` tables (`ifood_api_tokens`, `ifood_oauth_sessions`, `ifood_orders`, `ifood_order_items`, `ifood_order_status_logs`, `ifood_events`, `ifood_api_logs`, `ifood_catalog_snapshots`) with tenant scopes and foreign keys
- [x] 1.2 Implement Eloquent models in `app/Models/Integrations/Ifood/` with `BelongsToTenant`, fillables, casts and relationships

## 2. Ports, Adapters & DTOs

- [x] 2.1 Restore ports in `app/Ports/Integrations/Ifood/` (`IfoodAuthPort`, `IfoodOrderPort`, `IfoodCatalogPort`)
- [x] 2.2 Restore HTTP adapters in `app/Adapters/Integrations/Ifood/Http/` implementing ports via `Http` facade with timeout and error handling
- [x] 2.3 Restore DTOs in `app/DTO/Integrations/Ifood/` (`IfoodOrderDTO`, `IfoodOrderItemDTO`, `IfoodOrderStatusDTO`)

## 3. Repositories & Service Provider

- [x] 3.1 Restore repositories and interfaces in `app/Repositories/` and `app/Repositories/Contracts/` (`IfoodTokenRepository`, `IfoodOrderRepository`, `IfoodOauthSessionRepository`, `IfoodEventRepository`, `IfoodCatalogSnapshotRepository`, `IfoodApiLogRepository`)
- [x] 3.2 Ensure bindings in `app/Providers/IntegrationRepositoryServiceProvider.php` registered in `bootstrap/providers.php`

## 4. Services & Order Pipeline

- [x] 4.1 Restore `IfoodTokenService` with Redis concurrency lock for distributed token refresh
- [x] 4.2 Restore `IfoodOAuthService` for userCode request and authorizationCode exchange
- [x] 4.3 Restore `IfoodEventService` for deduplication and event persistence
- [x] 4.4 Restore `IfoodOrderService` normalizing marketplace orders to internal `Order` entities (`origin = 'ifood'`) and handling status callbacks (`confirm`, `dispatch`, `readyToPickup`)
- [x] 4.5 Restore `IfoodCatalogService` for catalog listing and category snapshots

## 5. Background Jobs & Console Commands

- [x] 5.1 Implement `ProcessIfoodEventJob` to ingest orders asynchronously upon event receipt
- [x] 5.2 Implement `RefreshIfoodTokensJob` to refresh expiring tokens
- [x] 5.3 Implement console commands `ifood:poll-events` (with immediate `/orders:acknowledgment`) and `ifood:refresh-tokens`

## 6. Controllers, Form Requests & API Routes

- [x] 6.1 Restore API controllers in `app/Http/Controllers/Api/Integrations/Ifood/` (`IfoodAuthController`, `IfoodOAuthController`, `IfoodOrderController`, `IfoodCatalogController`, `IfoodWebhookController`)
- [x] 6.2 Restore API resources in `app/Http/Resources/Integrations/Ifood/`
- [x] 6.3 Register routes in `routes/api.php` under `auth:api`, `tenant.blocked`, `trial.check` and public webhook route

## 7. Verification & Tests

- [x] 7.1 Execute migration on local MySQL/Docker and verify schema
- [x] 7.2 Restore and run Feature tests (`IfoodOAuthControllerTest`, `IfoodOrderIngestionTest`, `IfoodWebhookTest`) and Unit tests (`IfoodTokenServiceTest`)
- [x] 7.3 Run `php artisan audit:layers` and ensure zero new architectural violations
