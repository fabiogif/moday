# Design

## Context

- Public lookup: route `GET /store/{slug}/clients/lookup` → `PublicStoreController::lookupClient` → `PublicClientService::lookupClient` (CPF first, then phone; address from the client record or the last delivery order). Only caller: `lookupExistingClient`/`scheduleClientLookup` in `store/[slug]/page.tsx`, fired on phone/CPF `onChange` and `onBlur`.
- Customer session: login/register set an httpOnly `client_auth_token` cookie (SameSite=strict, path `/`) and return a token. `GET /store/{slug}/auth/me` runs under `inject.token.cookie:client_auth_token` + `auth:client` and returns the client's own data, but never compares the client's `tenant_id` with `{slug}`.
- The menu page does not mount `ClientAuthProvider` (only `store/[slug]/orders` does); the context keeps the token in `localStorage`.

## Goals / Non-Goals

**Goals:**
- No endpoint returns customer data to an unauthenticated caller.
- Logged-in customers keep prefill, scoped to the store they logged in to.

**Non-Goals:**
- Changing how guest orders create/update client records (see proposal follow-up).
- Adding the customer login entry point to the menu UI (login/register pages already exist).
- Address fallback from the last delivery order for logged-in customers: order creation already saves the delivery address on the client record, so `auth/me` has it.

## Decisions

1. **Delete, don't disable, the lookup.** Remove the route, `PublicStoreController::lookupClient` and `PublicClientService::lookupClient` together with its private helper `resolveClientAddress`, which has no other caller. A disabled-but-present endpoint invites re-enabling; the 404 is the contract.

2. **Store check in `auth/me` via the auth service.** `ClientAuthController::me(Request $request, string $slug)` resolves the tenant with the existing `findActiveTenantBySlug($slug)` and responds 401 "Cliente não autenticado" when the tenant is missing or `$client->tenant_id !== $tenant->id`. Same message as the no-session case, so the response does not reveal that the customer exists elsewhere. `getOrders` shares the route group and has the same gap; it is left as-is here (it only returns the caller's own orders) and noted as follow-up.

3. **Menu prefill from the cookie session, not the context.** On mount, the menu calls `auth/me` once with `credentials: 'include'` (the cookie is httpOnly, so the page cannot read it; same-origin in production via Nginx). On 200 it fills only empty fields of `clientData` and `deliveryData`, reusing the existing mask helpers (`maskPhone`, `maskCPF`, `maskZipCode`). On 401 or network error it does nothing and shows nothing. Why not wrap the page in `ClientAuthProvider`: the provider reads `localStorage` only and never validates the session or the store, so it would prefill stale or cross-store data.

4. **Mount `ClientAuthProvider` in a `store/[slug]/layout.tsx`.** Found during manual testing: `/login` and `/register` call `useClientAuth()` without any provider above them and crash on render (pre-existing). With the public lookup gone, login is the only path to prefill, so the fix belongs here. A single layout provider replaces the page-level wrapper in `orders/page.tsx`; the menu page itself still reads the session from `auth/me`, not from the context.

## Risks / Trade-offs

- [Returning guests lose autofill] → Accepted trade-off of option A; the login/register pages remain available.
- [Old frontend bundle calls the removed route] → it already ignores non-OK responses silently; deploy order does not matter.
- [Cookie not sent in local dev across ports] → prefill silently absent in dev when frontend and API are on different origins; production is same-origin.

## Migration Plan

Deploy backend and frontend together (either order is safe). Rollback: revert the commit; no data changes.
