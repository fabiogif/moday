# Proposal

## Why

The public menu screen (`/store/[slug]`, step "Cardápio") is a category-filtered grid of cards, while customers are used to the delivery-app menu pattern (iFood / 99Food): a store hero, one scrollable list with category tabs that follow the scroll, text-left/image-right product rows and a single cart bar. Aligning the menu layout with that pattern reduces friction to browse and surfaces information the store already has (rating, free-delivery threshold, featured coupons, best sellers) but the menu does not show.

## What Changes

Scope is **only the menu screen layout** (checkout step 0). The product detail dialog, the cart sheet, the checkout steps and the backend are unchanged.

- **Store hero**: cover area (primary-color gradient with the best-selling product image — no cover upload), square logo, store name, line with rating, 3-column info strip (open/closed status, delivery fee rule, pickup time/discount), back, search and share buttons (native share with copy-link fallback).
- **Featured coupons strip**: ticket-style cards fed by the existing `GET /store/{slug}/promotions` (coupon slides only); tapping copies the coupon code.
- **Showcases**: "Ofertas" (discounted products with discount pill on the image, "a partir de" price when the product has variations, quick-add "+") and "Preferidos" (best sellers ranked 1..N by `sold_qty`), replacing "Mais vendidos" / "Destaques".
- **Navigation**: compact sticky header shown after the hero (back, "Buscar em {loja}", ☰ + category tabs). **BREAKING (UI)**: categories no longer filter the list — all categories render as stacked sections; tabs scroll to their section and follow the scroll; ☰ opens a sheet listing categories. A non-empty search replaces the sections with a flat result list.
- **Product row**: name, 2-line description, price, old price struck through and green discount pill on the left; image (or placeholder illustration) on the right with the quick-add "+" over it; sold-out state.
- **Cart bar**: one bottom bar on the menu (store logo, total without delivery, item count, "Ver carrinho" opening the existing cart sheet), replacing the current "Total + Continuar pedido" bar and floating button, with one informative strip: amount missing to free delivery or savings from discounts. The strip is informative only.
- The order stepper is hidden on the menu step.

Non-goals (possible future changes): product detail sheet redesign, cart sheet ("sacola") redesign, item editing, per-item observation, checkout restyle, pickup suggestion, delivery minimum order (display and enforcement — deferred to a future feature), coupon application, cover image upload, favorites, distance/map, loyalty stamps, club pricing.

## Capabilities

### New Capabilities
- `public-store-menu`: customer-facing presentation and navigation of the public menu screen (hero, coupons strip, showcases, category sections and tabs, search, product rows, cart bar).

### Modified Capabilities
<!-- None: public-store-checkout requirements (API, opening-hours gate, order submission) are unchanged. -->

## Impact

- Frontend only: `frontend/src/app/store/[slug]/page.tsx` (menu step markup), new components under `frontend/src/app/store/[slug]/components/`, removal of `category-filter-chips.tsx` and its test, updates to `__tests__/page.test.tsx` (category-filter tests become section/tab tests).
- Consumes existing public endpoints only (`/info`, `/products`, `/promotions`, `/reviews/stats`, `/is-open`). No backend change, no new dependency.
