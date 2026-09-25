# Proposal

## Why

`GET /store/{slug}/auth/me` already rejects a customer session from another store (change `protect-client-lookup`), but `GET /store/{slug}/orders` — in the same route group — did not: a customer logged in to store A could open store B's "Meus pedidos" and see their store-A orders there. The design of `protect-client-lookup` recorded this as a follow-up.

## What Changes

- `GET /store/{slug}/orders` responds 401 "Cliente não autenticado" when there is no customer session or the customer belongs to a store other than `{slug}`, using the same check as `auth/me`.

## Capabilities

### New Capabilities
- (none)

### Modified Capabilities
- `public-store-checkout`: adds "Customer orders scoped to the store".

## Impact

- Backend: `ClientAuthController::getOrders` (takes `$slug`, calls `clientBelongsToStore`); new test in `ClientAuthenticationTest`.
- No frontend change: the orders page already treats 401 as "not logged in".
