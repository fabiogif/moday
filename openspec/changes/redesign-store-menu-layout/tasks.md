# Tasks

## 1. Price helper and product row

- [x] 1.1 Add `getDisplayPrice(product)` (price, original price, discount %, "a partir de" price = price + cheapest variation) and replace the inline discount computations in `page.tsx`; verify with unit tests covering string/number prices, no discount, and additive variation price (R$ 30 + R$ 5/R$ 10 → R$ 35)
- [x] 1.2 Create `components/product-image-placeholder.tsx` (inline SVG, `aria-hidden`) and verify it renders without network requests in a component test
- [x] 1.3 Create `components/menu-product-row.tsx` (text left, image/placeholder right, "+" over image, struck-through price with `sr-only` "de/por", green discount pill, "Esgotado" state, 44px touch targets) wired to `openProductDetail` and `handleAddProduct`; verify component tests for: row opens details, "+" on simple product calls add without opening details, "+" on product with variations opens details, zero stock shows "Esgotado" and no "+"

## 2. Category sections, tabs and search

- [x] 2.1 Replace the category filter with stacked category sections in `page.tsx` (grouped by category name, "Outros" for uncategorized, `scroll-mt` equal to the sticky header height), removing `selectedCategory`; verify in `page.test.tsx` that all categories render as section headings with their products
- [x] 2.2 Create `components/menu-category-tabs.tsx` (sticky tabs with `aria-current`, ☰ opening a sheet with all categories, click scrolls to the section, scroll-spy (rAF-throttled scroll listener, see design decision 2) with click-lock, active tab centered) and stop using `category-filter-chips.tsx` in the store page (component and its test kept: still used by `app/demo/menu/page.tsx`); verify component tests for click → `scrollIntoView` on the section and scroll position → `aria-current` moves only when the section title reaches the header
- [x] 2.3 Build the compact sticky header (back, search pill "Buscar em {loja}" with clear button, tabs) shown on step 0 after the hero sentinel, hide the old header and `OrderStepper` on step 0 only; verify search test (flat results, no sections, empty-state message, clearing restores sections) and that steps ≥ 1 still render the stepper (existing checkout tests keep passing)
- [x] 2.4 Rewrite the category-filter tests in `__tests__/page.test.tsx` as section/tab tests and run `npm test -- store` plus `npx tsc --noEmit` with no new errors

## 3. Hero, coupons and showcases

- [x] 3.1 Add `endpoints.store.promotions(slug)` to `api-client.ts` and best-effort fetches of reviews stats and promotions in `page.tsx` (failure → no UI, no toast); verify a page test where both requests fail and the menu still renders
- [x] 3.2 Create `components/store-hero.tsx` (primary-color gradient + best seller image fallback, square logo, name, rating line scrolling to reviews, 3-column info strip for hours/delivery fee/pickup, back/search/share buttons with `aria-label`; share via `navigator.share` or clipboard + toast, ignoring `AbortError`); verify component tests for rating shown/hidden, "Grátis acima de R$ 80,00", and clipboard fallback
- [x] 3.3 Create `components/coupon-strip.tsx` (ticket cards from coupon slides, click copies code + toast, hidden when empty); verify component tests for copy and empty state
- [x] 3.4 Create `MenuOffers` ("Ofertas": discount pill on image, "a partir de", "+" quick add, top card highlighted) and `MenuTopSellers`, both in `components/menu-showcases.tsx` sharing the image block ("Preferidos": rank numbers with `aria-label`), remove `renderHighlightRow`, show both only with empty search; verify tests for offer ordering by discount, hidden when empty, rank labels

## 4. Cart bar

- [x] 4.1 Create `components/cart-bar.tsx` (logo, total without delivery, item count, "Ver carrinho" opening the cart sheet, single informative message by priority: free delivery → savings; no delivery minimum message) replacing the mobile "Total + Continuar pedido" bar and the floating summary button on step 0; keep desktop aside and desktop bottom bar; verify component tests for both messages, their priority and that no minimum-order message appears, and a page test that "Ver carrinho" opens the cart sheet and the closed-store gate still disables checkout there

## 5. Follow-ups from review

- [x] 5.1 Add the "Meus pedidos" shortcut (link to `/store/{slug}/track`) to the hero; verify the hero component test asserts the link and its href
- [x] 5.2 Large screens: shared `max-w-6xl` container, floating hero card, order summary always visible and sticky on `lg`+, product rows in two columns on `xl`; verify in the browser at 1910px and keep store tests green

## 6. Integration

- [x] 6.1 Run the full frontend test suite, `npx tsc --noEmit` and lint with no new errors; then check the menu manually at 375px and desktop widths (tabs follow scroll, tab click lands below header, search, quick add, cart bar, share) in the running app
- [x] 6.2 Run `graphify update .` in `frontend/` and verify it completes
