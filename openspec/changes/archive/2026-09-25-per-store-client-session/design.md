# Design

## Context

`ClientAuthProvider` is mounted once in `store/[slug]/layout.tsx`. `login(email, password, slug)` and `register(data, slug)` already receive the slug; the provider itself did not know it.

## Decisions

1. **Key the stored session by slug** and read the current slug with `useParams()` in the provider. The restore effect depends on the slug, so navigating to another store resets the state and loads that store's session (if any).
2. **Discard legacy global keys** instead of migrating them: the stored client does not say which store it belongs to, so a migration could attach a session to the wrong store.
3. **`credentials: 'include'`** on register/login — the backend CORS already allows credentials; checkout prefill (`auth/me`) relies on the cookie.

## Risks / Trade-offs

- [Customers logged in before deploy lose their session once] → Accepted; logging in again is enough.
