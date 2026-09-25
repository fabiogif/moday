# Tasks

## 1. Backend: remove the public lookup

- [x] 1.1 Remove the `GET /store/{slug}/clients/lookup` route, `PublicStoreController::lookupClient`, `PublicClientService::lookupClient` and `resolveClientAddress` (plus imports/repository methods left unused); verify with `grep -rn "lookupClient\|clients/lookup" backend/app backend/routes` returning nothing
- [x] 1.2 Replace the two lookup tests in `PublicClientCreationTest` (by CPF, by phone) with one test asserting `GET /api/store/{slug}/clients/lookup?phone=...` returns 404 and no customer fields; verify the file passes

## 2. Backend: store check on auth/me

- [x] 2.1 Make `ClientAuthController::me` take `string $slug`, resolve the tenant via the existing auth service and respond 401 "Cliente não autenticado" when the tenant is missing or differs from the client's; verify with a new test in `ClientAuthenticationTest`: client of store A with a valid token calls `/api/store/{B}/auth/me` → 401, and the existing `auth_me_*` tests still pass
- [x] 2.2 Run `ClientAuthenticationTest`, `PublicClientCreationTest`, `PublicStoreControllerTest` and verify they pass; run `php artisan audit:layers` and verify the violation count does not grow (baseline 77)

## 3. Frontend: prefill from the session

- [x] 3.1 In `store/[slug]/page.tsx`, remove `lookupExistingClient`, `scheduleClientLookup`, their refs and the `onChange`/`onBlur` calls on phone and CPF; verify with `grep -n "clients/lookup\|scheduleClientLookup" src/app/store` returning nothing
- [x] 3.2 On mount, call `auth/me` once (via `endpoints.store` if an entry exists, else add `authMe` next to `isOpen`) with `credentials: 'include'`; on 200 fill only empty client/delivery fields with the existing masks; on 401/error do nothing; verify with tests in `store/[slug]/__tests__/page.test.tsx`: `auth/me` 200 → name and phone prefilled at "Seus dados"; `auth/me` 401 → fields empty and no toast; typing a phone triggers no request containing `clients/lookup`
- [x] 3.3 Run the store page tests, type-check and ESLint on the touched files and verify no new findings
- [x] 3.4 Mount `ClientAuthProvider` once in a new `store/[slug]/layout.tsx` (drop the page-level wrapper in `orders/page.tsx`) so `/login` and `/register` stop crashing on `useClientAuth()`; verify with a test that renders the login and register pages inside the layout and finds their e-mail fields

## 4. Integration

- [x] 4.1 On a local environment, verify `clients/lookup` returns 404, a guest typing a known phone gets no autofill, and a customer logged in to the store gets prefill at checkout — done 2026-09-23 (local: MySQL in WSL + artisan serve + next dev): lookup 404 by phone and CPF with no data; guest typing a known phone made no API call; session prefilled name/e-mail/phone and address; same session on another store got 401. Login page itself crashes (pre-existing, missing ClientAuthProvider) — session was created via the login API.
- [x] 4.2 Run `graphify update .` in `backend/` and `frontend/`
