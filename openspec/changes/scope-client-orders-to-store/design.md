# Design

## Context

`auth/me` and `orders` share `inject.token.cookie:client_auth_token` + `auth:client`. `ClientAuthService::clientBelongsToStore($client, $slug)` compares the client's `tenant_id` with the active tenant of `{slug}`.

## Decisions

1. **Reuse `clientBelongsToStore` in `getOrders`**, with the same 401 message as the no-session case, so the response does not reveal that the customer exists in another store.
2. **No middleware.** Two endpoints do not justify a new middleware; if more store-scoped customer endpoints appear, the check can move there.

## Risks / Trade-offs

- [A customer who used store A's session on store B's orders page is now logged out there] → Intended: the account belongs to store A.
