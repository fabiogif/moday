# Proposal

## Why

The public menu blocks checkout when the store is closed, but only in the browser: `POST /store/{slug}/orders` accepts orders at any time. A stale tab, a slow checkout that crosses closing time, or a direct API call creates orders the restaurant never expected. Opening periods also carry a type (`delivery`, `pickup`, `both`) that nothing enforces, so a store that only delivers at night still gets pickup orders, and vice versa.

## What Changes

- The order submission API rejects public orders placed outside the opening hours that apply to the chosen shipping method (`delivery` → periods typed `delivery` or `both`; `pickup` → `pickup` or `both`), responding 422 with an error on `shipping_method`.
- Stores with no active opening hours keep accepting orders (same as the menu today). "Always open" stores keep accepting orders.
- `GET /store/{slug}/is-open` accepts an optional `delivery_type` query (`delivery`, `pickup`; default `both`) so the menu can ask about the chosen shipping method.
- When the customer confirms the shipping step, the menu checks `is-open` for that shipping method and blocks the step with a clear message if it is closed, instead of failing only at final submission.
- **BREAKING (behavior)**: orders previously accepted while closed are now rejected. Only the public menu is affected; PDV and dashboard orders are unchanged.

## Capabilities

### New Capabilities
- (none)

### Modified Capabilities
- `public-store-checkout`: the "Opening hours are informational" requirement becomes enforced hours (renamed), `is-open` gains a `delivery_type` filter, and order submission gains a closed-store rejection.

## Impact

- Backend: public order request validation, public `is-open` endpoint, store-hour service (no-hours fallback), feature tests.
- Frontend: store menu (`store/[slug]`) shipping-step validation.
- Out of scope (follow-up): overnight periods (e.g. 22:00–02:00) are only checked against the current weekday, so the tail after midnight counts as closed. This is existing behavior shared with `is-open` and is not changed here.
