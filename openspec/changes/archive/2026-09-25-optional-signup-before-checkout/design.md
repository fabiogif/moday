# Design

## Context

- Checkout lives in a single page (`store/[slug]/page.tsx`) as wizard steps 0–4; step 0 is the menu. Every "Continuar pedido" button (cart summary, desktop bar, mobile sheet) calls `handleStartCheckout`.
- The cart is `useState` only: navigating away from the menu page drops it.
- Customer auth: `ClientAuthProvider` (mounted in `store/[slug]/layout.tsx`) exposes `register()`, which calls `POST /store/{slug}/auth/register` and stores token and client in `localStorage`; the backend also sets the httpOnly `client_auth_token` cookie. The register fetch does not send `credentials: 'include'`, so the cookie may not be stored when the API is on another origin.
- The register endpoint rejects an e-mail that already exists in the store with 422 `errors.email = ["Email já cadastrado nesta loja"]` and message "Dados inválidos"; the context threw only the message.
- Guest orders link to the client record by CPF, then e-mail, then phone (`PublicClientService::createOrUpdateClient`), so an order placed right after registering reuses the new record.

## Goals / Non-Goals

**Goals:**
- Optional registration at "Continuar pedido" without losing the cart or changing the guest path.
- Reuse the existing registration form, validation, endpoint and session handling.

**Non-Goals:**
- Letting customers without a password (guest-created records) claim their account — follow-up change with e-mail verification.
- Login inside the dialog (the login page is a separate route; it can reuse `login()` later).
- Migrating the registration form to RHF + Zod — it is extracted as-is to keep behavior identical.
- Scoping the `localStorage` session per store.

## Decisions

1. **Dialog on the menu page, not a route.** The page stays mounted, so the in-memory cart, delivery data and step survive registration, cancel and errors. Rejected: persisting the cart and navigating to `/register` — introduces a new persistence pattern and touches the cart.
2. **Extract `ClientRegisterForm` from the register page.** One set of fields, masks, validations and API call, used by `/register` (redirects on success) and the dialog (continues checkout on success). Extra actions are passed as children (login link on the page; "Voltar"/"Continuar sem cadastro" in the dialog). Rejected: copying the form into the dialog.
3. **Prefill from the register response, not from `auth/me`.** `register()` returns the created client; the page merges it into empty fields through the same `applyClientPrefill` used by the `auth/me` prefill. Avoids depending on the cookie being stored cross-origin.
4. **When to ask.** Skip when `isAuthenticated`; ask at most once per page visit (`signupPromptAnswered`). The prompt is shown after the existing step-0 validation (store open, cart not empty), and the mobile summary sheet is closed before it opens.
5. **Dialog cannot close mid-submit.** The form reports its submitting state; while true, closing and the secondary buttons are disabled, so a late success never advances checkout behind a closed dialog.
6. **Errors.** The context throws the first field error of a 422 (falls back to `message`); the form maps a `TypeError` from `fetch` to a connection message. Any error keeps the typed data and offers "Continuar sem cadastro".

## Risks / Trade-offs

- [Returning guests see "Email já cadastrado"] → Accepted until the first-access follow-up; the dialog offers "Continuar sem cadastro" and the order still links by e-mail.
- [Logged in to another store in the same browser skips the prompt] → Pre-existing: the context session is not per store. Checkout prefill still goes through `auth/me`, which validates the store.
- [Register page refactor] → Covered by the existing auth-pages test and the new form test.

## Migration Plan

Frontend-only deploy. Rollback: revert the commit; no data or API change.
