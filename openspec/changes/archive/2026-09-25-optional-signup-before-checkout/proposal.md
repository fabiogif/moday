# Proposal

## Why

Customers on the public menu almost never create an account: the only entry point is the separate `/store/{slug}/register` page, and going there loses the cart (it lives only in memory on the menu page). Asking at the moment the customer taps "Continuar pedido" — and letting them register without leaving the menu — turns checkout into the natural place to become an identified customer, without making registration mandatory.

## What Changes

- Tapping "Continuar pedido" on the menu asks, once per visit, whether the customer wants to register: "Sim, quero me cadastrar" or "Continuar sem cadastro". Customers already logged in are not asked.
- "Continuar sem cadastro" follows the existing checkout flow unchanged.
- "Sim, quero me cadastrar" opens the existing customer registration form inside a dialog on the menu page. On success the customer is logged in (existing register endpoint and session storage), the "Seus dados" step is prefilled with the registered data and checkout continues. The cart is never lost: cancel, close, back and errors all keep it.
- Registration errors show the real reason: the register call surfaces the first field error of a 422 (e.g. "Email já cadastrado nesta loja") instead of the generic "Dados inválidos", and a network failure shows a connection message. After an error the customer can fix the data or continue without registering.
- The registration form is extracted from the `/register` page into a shared component used by both the page and the dialog; the page keeps its behavior.

## Capabilities

### New Capabilities
- (none)

### Modified Capabilities
- `public-store-checkout`: adds "Optional sign-up before checkout"; "Checkout prefill only for the logged-in customer" also allows prefill from the registration the customer just completed.

## Impact

- Frontend only: store menu (`store/[slug]/page.tsx`), new `store/[slug]/components/signup-prompt-dialog.tsx`, new shared `components/client-register-form.tsx`, `store/[slug]/register/page.tsx`, `contexts/client-auth-context.tsx` (`register` returns the client and surfaces field errors).
- No backend, API or database change. Uses the existing `POST /store/{slug}/auth/register`.
- Out of scope (follow-up): customers who already exist without a password (created by guest orders or in the admin panel) cannot register with the same e-mail — the register endpoint rejects it. They continue without registering and their order still links to their record. A "first access / forgot password" flow with e-mail verification is the planned fix; setting a password on an existing record without verifying the e-mail would allow account takeover.
