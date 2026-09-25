# Proposal

## Why

The customer session in the menu (`ClientAuthProvider`) was stored under global `localStorage` keys. A customer account belongs to one store, but after logging in to store A the browser counted as "logged in" on every store: store B's menu skipped the optional sign-up question and the orders page tried store A's token there. Registration and login also did not send `credentials: 'include'`, so the httpOnly session cookie used by checkout prefill was not stored when the API is on another origin (local development).

## What Changes

- The customer session is stored per store (`client-auth-user:{slug}` / `client-auth-token:{slug}`) and only restored on the menu of that store. The old global keys are discarded (those customers log in again once).
- Logout clears only the current store's session.
- Registration and login send `credentials: 'include'`.

## Capabilities

### New Capabilities
- (none)

### Modified Capabilities
- `public-store-checkout`: adds "Customer session is per store".

## Impact

- Frontend only: `contexts/client-auth-context.tsx` (reads the slug with `useParams`); tests `src/__tests__/contexts/client-auth-context.test.tsx` and the register form test.
- Customers currently logged in are logged out once after deploy.
