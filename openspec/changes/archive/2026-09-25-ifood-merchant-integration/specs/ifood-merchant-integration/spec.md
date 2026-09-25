# Spec Delta

## Purpose
Enables multi-tenant restaurant owners to link their iFood merchant account via OAuth 2.0 distributed flow, continuously ingest incoming delivery and pickup orders via background polling with immediate event acknowledgment, and advance order fulfillment states (confirm within SLA, dispatch, ready to pickup) directly from the kitchen operations board.

## ADDED Requirements

### Requirement: Merchant OAuth 2.0 distributed linking
The system SHALL allow an authenticated tenant user to initiate iFood account linking by requesting a `userCode` and verification URL from iFood. The user SHALL provide the `authorizationCode` obtained from the iFood Partner Portal, which the system SHALL exchange for an access token and refresh token, stored securely under the authenticated tenant.

#### Scenario: Request user code
- **WHEN** an authenticated tenant requests iFood authorization linking
- **THEN** the system calls the iFood OAuth userCode endpoint and returns `user_code`, `verification_url` and expiry metadata

#### Scenario: Exchange authorization code
- **WHEN** the user submits the valid `authorizationCode`
- **THEN** the system exchanges it for tokens, persists the tenant's `ifood_api_tokens` record with expiry timestamp, and returns a connected status

#### Scenario: Invalid or expired authorization code
- **WHEN** the user submits an expired or invalid `authorizationCode`
- **THEN** the system returns an authorization error (422) and no active token is saved

### Requirement: Background token refresh
The system SHALL automatically refresh expiring iFood access tokens before their expiration window using the stored `refresh_token`. If token refresh fails due to revoked merchant access, the connection status SHALL be flagged as disconnected/expired.

#### Scenario: Token near expiration
- **WHEN** the scheduled refresh job detects a token expiring within the configured threshold
- **THEN** it sends a `refresh_token` grant request, updates the stored access and refresh tokens, and resets the expiration timestamp

### Requirement: Order event polling and acknowledgment
The system SHALL query iFood orders polling endpoint (`/order/v1.0/orders:polling`) periodically for active tenants with a valid connection. For every batch of events received, the system SHALL immediately send an acknowledgment payload (`/orders:acknowledgment`) for all received event IDs and dispatch background processing jobs to ingest order data.

#### Scenario: Polling returns events
- **WHEN** the polling job fetches 3 pending order events from iFood
- **THEN** the system immediately sends a POST to `/orders:acknowledgment` with the 3 event IDs, saves the events to `ifood_events` with status pending, and queues processing jobs

#### Scenario: Polling returns no content
- **WHEN** the polling endpoint returns 204 No Content
- **THEN** the job completes without errors and sends no acknowledgment

### Requirement: Order ingestion and internal conversion
When an order event (`PLACED` or `CONFIRMED`) is processed, the system SHALL fetch the full order payload from iFood (`/order/v1.0/orders/{orderId}`) and create an internal `Order` entity with `origin = 'ifood'`. If an order with that `external_order_id` already exists for the tenant, it SHALL NOT be duplicated.

#### Scenario: New delivery order ingestion
- **WHEN** a new order event is processed for order `abc-123`
- **THEN** an internal `Order` is created with `origin = 'ifood'`, items matched to tenant products or stored as custom items, total matching the iFood total, customer contact preserved, and an `OrderCreated` event broadcast

#### Scenario: Duplicate order event received
- **WHEN** an event is processed for an `external_order_id` that is already ingested
- **THEN** the system skips creation, updates status if applicable, and marks the event as processed

### Requirement: Order status synchronization callbacks
When an iFood order changes status in the operational board or via API, the system SHALL call the corresponding iFood lifecycle endpoint:
- Confirm: `POST /order/v1.0/orders/{id}/confirm`
- Dispatch (for DELIVERY orders only): `POST /order/v1.0/orders/{id}/dispatch`
- Ready to Pickup (for TAKEOUT orders only): `POST /order/v1.0/orders/{id}/readyToPickup`

#### Scenario: Confirming order within SLA
- **WHEN** an operator confirms an iFood order
- **THEN** the system sends a confirm request to iFood and logs the status response

#### Scenario: Dispatching takeout order rejected
- **WHEN** an operator marks a TAKEOUT order as dispatched
- **THEN** the system uses `readyToPickup` instead of `dispatch` to satisfy iFood lifecycle constraints
