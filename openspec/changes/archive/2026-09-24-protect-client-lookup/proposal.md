# Proposal

## Why

`GET /store/{slug}/clients/lookup` returns a customer's name, e-mail, phone, full CPF and full address to anyone who types their phone number or CPF in the public menu, with only a 20 requests/minute limit as protection. That is a personal-data exposure (LGPD) and makes enumeration of a restaurant's customer base trivial. The menu already has customer login, and `auth/me` already returns the same data to the authenticated customer, so checkout prefill does not need a public lookup.

## What Changes

- **BREAKING**: remove the public `GET /store/{slug}/clients/lookup` endpoint (the route responds 404).
- Checkout prefill comes only from the logged-in customer: when the menu has a valid customer session for this store, the "Seus dados" step and the delivery address are filled from `GET /store/{slug}/auth/me`. Guests type their data; nothing is looked up while typing.
- `GET /store/{slug}/auth/me` responds 401 when the authenticated customer belongs to a different store than `{slug}`, so a session from one restaurant never prefills another restaurant's checkout.
- Order submission is unchanged (guests still create/update their client record when ordering).

## Capabilities

### New Capabilities
- (none)

### Modified Capabilities
- `public-store-checkout`: removes "Client lookup for checkout autofill"; adds prefill for logged-in customers and the store check on `auth/me`.

## Impact

- Backend: public store routes, `PublicStoreController::lookupClient` and `PublicClientService::lookupClient` (removed), `ClientAuthController::me` (store check), feature tests for lookup replaced.
- Frontend: store menu (`store/[slug]/page.tsx`) — remove typing-triggered lookup, add prefill from `auth/me`.
- Customers who used to get autofill as guests now type their data or log in.
- Out of scope (follow-up): a guest order with an existing customer's phone/CPF still updates that customer's stored name/e-mail/address (`createOrUpdateClient`). Not a read leak, but a data-integrity issue worth its own change.
