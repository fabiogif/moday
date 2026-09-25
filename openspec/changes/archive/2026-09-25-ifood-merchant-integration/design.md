# Design

## Context

See `proposal.md` for business motivation and high-level scope.
The platform already features a hexagonal architecture precedent for iFood (documented in `docs/specs/backend.md` and historically tested in `backend_moday`), alongside UI screens in `frontend/src/app/(dashboard)/integrations/ifood/` (`oauth`, `orders`, `catalogs`).
The integration requires restoring database persistence, re-registering repository service providers, and implementing background event polling and status feedback loops while preserving tenant data isolation (`BelongsToTenant`).

## Goals / Non-Goals

**Goals:**
- Provide a robust OAuth 2.0 distributed flow (`userCode` + `authorizationCode` exchange) storing per-tenant credentials.
- Automatic scheduled token refresh with concurrency locking via Redis.
- Background polling (`/order/v1.0/orders:polling`) with immediate ACK (`/orders:acknowledgment`) and asynchronous job dispatch.
- Convert iFood orders into native `Order` records (`origin = 'ifood'`) with customer snapshot preservation and real-time dashboard events.
- Implement bidirectional status advancement: confirming orders within the 8-minute SLA, dispatching delivery orders, and marking takeout orders ready for pickup.

**Non-Goals:**
- Two-way deep catalog synchronization (full menu synchronization via Catalog v2.0 will be handled in a follow-up iteration; snapshotting metadata is supported).
- Financial reconciliation of iFood voucher subsidies or payment settlement files.
- Arbitrary order cancellations without adhering to iFood's cancellation negotiation protocol.

## Decisions

### 1. Hexagonal Architecture (Ports & Adapters)
- **Decision**: Keep external HTTP communication strictly isolated in `app/Ports/Integrations/Ifood/` (`IfoodAuthPort`, `IfoodOrderPort`, `IfoodCatalogPort`) and `app/Adapters/Integrations/Ifood/Http/`.
- **Rationale**: Aligns directly with `docs/specs/architecture.md` and `docs/specs/backend.md`. Services never call `Http::` directly; repositories and models remain decoupled from external payload formats.

### 2. Native Order Creation with `origin = 'ifood'`
- **Decision**: Ingested orders are normalized into the core `Order` model with `origin = 'ifood'`.
- **Rationale**: Automatically leverages existing kitchen display Kanban, real-time Reverb broadcasts (`OrderCreated`), order printouts, and reporting without creating parallel order management pipelines.

### 3. Immediate ACK + Asynchronous Idempotent Ingestion
- **Decision**: The polling process sends `/orders:acknowledgment` immediately upon receiving event batches and writes rows to `ifood_events` with unique `(tenant_id, event_id)`. Actual payload retrieval and order creation run asynchronously in `ProcessIfoodEventJob`.
- **Rationale**: Prevents iFood from re-delivering duplicate events in subsequent polls while ensuring transient local job failures do not block the polling loop.

### 4. Distributed Token Refresh with Redis Locks
- **Decision**: Token refresh in `IfoodTokenService` is guarded by an atomic Redis lock (`Cache::lock("ifood:refresh:{$tenantId}", 30)`).
- **Rationale**: Prevents race conditions when concurrent scheduler workers or web requests attempt to exchange the same single-use `refresh_token`.

### 5. Separate Fulfillment Actions for Delivery vs Takeout
- **Decision**: Status transitions check `order_type` to route to `POST /orders/{id}/dispatch` for delivery orders and `POST /orders/{id}/readyToPickup` for takeout orders.
- **Rationale**: iFood API explicitly forbids `/dispatch` for takeout orders and throws 400 Bad Request.

## Risks / Trade-offs

- **Risk: Token Revocation**: If a merchant disconnects the app via the iFood Partner Portal, refresh calls will fail (401/400).
  - *Mitigation*: Catch authentication exceptions, mark `ifood_api_tokens.status = 'revoked'`, and alert the operator in the dashboard.
- **Risk: Polling Rate Limits**: Running polling across many tenants could hit iFood rate limits if scheduled too aggressively.
  - *Mitigation*: Limit polling queries to a 30-second cadence per active tenant with jitter and backoff on empty responses (204 No Content).
- **Risk: 8-Minute Confirmation SLA**: Marketplace auto-cancels unconfirmed orders after 8 minutes.
  - *Mitigation*: Support tenant-configurable automatic confirmation on order ingestion (`auto_confirm = true`).
