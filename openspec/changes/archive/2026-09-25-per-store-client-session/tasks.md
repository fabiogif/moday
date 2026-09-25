# Tasks

- [x] 1.1 `ClientAuthProvider`: session keys per slug (`useParams`), restore on slug change, discard legacy keys, logout clears only the current store
- [x] 1.2 Register and login send `credentials: 'include'`
- [x] 1.3 Tests in `src/__tests__/contexts/client-auth-context.test.tsx`: register saves only under the store and sends credentials; store A session not valid on store B; legacy session discarded; logout keeps the other store's session. Register form test updated for the keyed session
- [x] 1.4 Full frontend suite (871 passed), TypeScript 0 errors
