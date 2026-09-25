# Tasks

- [x] 1.1 `ClientAuthController::getOrders(Request $request, string $slug)` responds 401 when `clientBelongsToStore` fails
- [x] 1.2 Test in `ClientAuthenticationTest`: customer of store A calling `/api/store/{B}/orders` → 401 without `data.orders`; existing orders test still passes
- [x] 1.3 Full backend suite green (1524 passed)
