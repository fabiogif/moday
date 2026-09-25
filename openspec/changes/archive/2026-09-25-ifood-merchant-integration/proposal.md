# Proposal

## Why

Restaurants and food businesses using the Alba Tec (Moday) platform receive orders via iFood, Brazil's leading delivery marketplace. Currently, order intake occurs via internal channels, public store, and PDV. Reintegrating with the iFood Merchant API enables multi-tenant restaurant owners to authorize their iFood accounts, automatically ingest incoming marketplace orders into the unified kitchen/board pipeline, acknowledge order events within SLA windows, and advance fulfillment statuses (confirm, dispatch, ready to pickup) directly from the dashboard.

## What Changes

- **OAuth 2.0 Distributed Authorization**: Implement merchant account linking using iFood's userCode verification flow, exchanging the merchant authorization code for scoped access and refresh tokens per tenant.
- **Token Management & Refresh Loop**: Automatic background token refresh before the 6-hour expiration threshold, backed by concurrency locks in Redis.
- **Event Polling & Acknowledgment Queue**: Scheduled background polling (`/order/v1.0/orders:polling`) fetching active events, immediately acknowledging (`/orders:acknowledgment`), and dispatching asynchronous ingestion jobs.
- **Order Ingestion & Transformation**: Mapping iFood order payloads (customer, items, delivery details, payment method) into internal `Order` entities with `origin = 'ifood'`, triggering standard `OrderCreated` broadcasts and Kanban sync.
- **Order Fulfillment Synchronization**: Endpoints and event triggers advancing order state on iFood (`confirm`, `dispatch`, `readyToPickup`) aligned with kitchen board transitions.
- **Persistence Layer**: Restore dedicated tables (`ifood_api_tokens`, `ifood_oauth_sessions`, `ifood_orders`, `ifood_order_items`, `ifood_order_status_logs`, `ifood_events`, `ifood_api_logs`, `ifood_catalog_snapshots`) under tenant isolation.
- **Frontend Dashboard Management**: Connect the existing Next.js pages (`/integrations/ifood/oauth`, `/integrations/ifood/orders`, `/integrations/ifood/catalogs`) to the backend API.

## Capabilities

### New Capabilities
- `ifood-merchant-integration`: Merchant account authorization (OAuth 2.0), background event polling/ACK, order ingestion, status feedback loop, and multi-tenant token management.

### Modified Capabilities
- `order-intake`: Support `ifood` as a first-class order origin, handling marketplace-managed delivery and payment metadata.

## Impact

- **Database**: New migration creating tables with prefix `ifood_*` scoped by `tenant_id`.
- **Backend Architecture**: Re-registration of `IntegrationRepositoryServiceProvider`, HTTP adapters implementing `IfoodAuthPort` and `IfoodOrderPort`, and queued jobs (`ProcessIfoodEventJob`, `RefreshIfoodTokensJob`).
- **Scheduling**: Console scheduler executing event polling every 30-60 seconds and token refreshment every 30 minutes.
- **Frontend**: Activating the existing integration pages in `src/app/(dashboard)/integrations/ifood/`.
