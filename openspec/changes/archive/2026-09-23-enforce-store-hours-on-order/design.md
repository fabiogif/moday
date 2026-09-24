# Design

## Context

- `StoreHourService::isStoreOpen(tenantId, deliveryType = 'both')` already implements the period check: always-open short-circuit, today's active periods, both periods per day, overnight windows within the same day, and the `delivery_type` filter (a period typed `both` matches any request; a request for `both` matches any period). It returns `false` when the store has no active hours.
- Callers: public `GET /store/{slug}/is-open` (always passes `both`) and admin `GET /store-hours/check-is-open` (already reads `?delivery_type=`).
- Public orders are validated in `PublicStoreOrderRequest::withValidator()`, which already adds cross-field errors (stock, payment method, coupon) after the base rules.
- The menu (`store/[slug]/page.tsx`) validates wizard steps synchronously in `validateWizardStep()`; `goNext()` calls it. The store-hours banner already applies the "no hours configured → open" fallback on the client.

## Goals / Non-Goals

**Goals:**
- One server-side rule for "may this public order be placed now", shared by validation and reused by nothing else that would change meaning.
- The menu fails early on the shipping step, not only at final submit.

**Non-Goals:**
- Changing `isStoreOpen()` semantics for existing callers (admin screen and public `is_open` stay as they are for stores without hours).
- Fixing overnight periods that spill into the next weekday.
- Enforcing hours on PDV, dashboard or offline-sync orders.

## Decisions

1. **New `StoreHourService::acceptsOrdersNow(int $tenantId, string $shippingMethod): bool`** = `!hasActiveHours($tenantId) || isStoreOpen($tenantId, $shippingMethod)`.
   - Why not put the no-hours fallback inside `isStoreOpen()`: it would flip `is_open` to `true` for stores without hours on the admin screen and on public `is-open`, a behavior change outside this scope. The menu already applies the fallback itself.
   - `hasActiveHours` is a single `exists()` query on active store hours of the tenant (repository method next to `isAlwaysOpen`).
   - `shipping_method` values (`delivery`, `pickup`) are exactly the `delivery_type` values, so no mapping is needed.

2. **Check lives in `PublicStoreOrderRequest::withValidator()`** as `validateStoreHours()`, adding the error on `shipping_method`, after the base rules pass. Why: it is where every other cross-field rejection of public orders already lives, it runs before the transaction so no records or coupon usage are created, and it produces the standard 422 field-error shape the menu already renders as a toast. The tenant lookup is done the same way the sibling validators do it.
   - Messages: `delivery` → "Entrega indisponível no momento. A loja não está atendendo entregas neste horário."; `pickup` → "Retirada indisponível no momento. A loja não está atendendo retiradas neste horário." When neither is open, the same per-method message is enough; the client does not need a separate "closed" message.

3. **Public `is-open` reads `delivery_type`**, accepting only `delivery`/`pickup`; anything else (or absent) is treated as `both`. Mirrors the admin endpoint.

4. **Menu: async check in `goNext()` for the shipping step (step 2)** after the existing synchronous validation passes: call `is-open?delivery_type=<shippingMethod>`; if `is_open` is false and the response lists opening hours (i.e. not the no-hours case) and is not always open, show the per-method message and stay on the step. On network error, advance (server is the backstop), matching the banner's fail-open behavior. Disable the Continue button while the request is in flight to avoid double advance.

## Risks / Trade-offs

- [Closing time between shipping step and submit] → the server check rejects at submit with the same message; the menu already toasts field errors from 422 responses.
- [Clock/timezone] → evaluation uses the app timezone (`America/Sao_Paulo`); tenants in other timezones are already inconsistent today on `is-open`. Unchanged.
- [Overnight tail] → a 22:00–02:00 period rejects orders after midnight. Documented as follow-up; restaurants can add a 00:00–02:00 period on the next day as a workaround.
- [Behavior change for live tenants] → tenants with hours configured but who relied on late orders slipping through will now reject them. That is the intended fix; release note for tenants.

## Migration Plan

No data migration. Deploy backend first (server rejects closed orders; old menu still works and shows the 422 as a toast), then frontend. Rollback: revert the validator call.
