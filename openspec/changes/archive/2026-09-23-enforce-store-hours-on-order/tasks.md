# Tasks

## 1. Backend: order acceptance rule

- [x] 1.1 Add `hasActiveHours(int $tenantId): bool` to the store-hour repository (and its interface, if any) and `acceptsOrdersNow(int $tenantId, string $shippingMethod): bool` to `StoreHourService`; verify with a unit/feature test covering no hours → true, always open → true, inside a `both` period → true, delivery inside a `pickup`-only period → false, second period of the day → true (use `Carbon::setTestNow`)
- [x] 1.2 Add `validateStoreHours()` to `PublicStoreOrderRequest::withValidator()` adding the per-method error on `shipping_method`; verify with feature tests in `PublicStoreControllerTest` (or `PublicClientCreationTest`, whichever already posts real orders): closed store → 422 on `shipping_method` and no `sale_orders`/`orders` rows created; no hours configured → 201; delivery rejected while pickup accepted in a `pickup`-only period
- [x] 1.3 Run the existing public order test suites and verify they still pass (stores without hours must keep accepting orders)

## 2. Backend: is-open filter

- [x] 2.1 Make public `GET /store/{slug}/is-open` read `delivery_type` (`delivery`/`pickup`, otherwise `both`) and pass it to `isStoreOpen()`; verify with a feature test: `pickup`-only current period → `delivery_type=delivery` returns `is_open: false`, `pickup` and no parameter return `true`

## 3. Frontend: shipping step check

- [x] 3.1 In `store/[slug]/page.tsx`, make `goNext()` on the shipping step call `is-open?delivery_type=<shippingMethod>` (via the existing `isOpen` endpoint helper) and stay on the step with the per-method message when closed (fail open on network error or when no hours are configured; disable Continue while checking); verify with a test in `store/[slug]/__tests__/page.test.tsx` mocking `is-open` closed → step does not advance and the message is shown, open → advances
- [x] 3.2 Run `npm run lint`, type-check and the store page tests and verify they pass — done: tests 21/21; type-check and ESLint report no new findings (the store page already had 16 TS errors and 28 lint findings at HEAD). ESLint was unrunnable because the `ajv`/`minimatch` security overrides forced major versions onto ESLint; fixed by scoping them in `frontend/package.json`.

## 4. Integration

- [x] 4.1 Manually (or via browser) place a menu order inside hours, and with hours set to exclude now, and verify: accepted, then blocked on the shipping step, and a direct `POST` returns 422 on `shipping_method` — done 2026-09-23 on a local env (backend via `php artisan serve` + throwaway MySQL in WSL): API `is-open` filter and 422/201 verified with curl; in the browser, pickup advanced inside a pickup period and was blocked with the toast after switching the period to delivery-only. Delivery was not exercised in the browser (test DB had no states/cities for the address form); covered by the API check.
- [x] 4.2 Run `graphify update .` in `backend/` and `frontend/` per project instructions
