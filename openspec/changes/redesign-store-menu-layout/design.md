# Design

## Context

See proposal.md for motivation and specs/public-store-menu/spec.md for behavior.

Current state (observed in code):
- `frontend/src/app/store/[slug]/page.tsx` (~2,900 lines) holds all state (store info, products, cart, wizard) and renders the menu step inline: header with logo/name + `OrderStepper`, `renderHighlightRow` ("Mais vendidos" by `sold_qty`, "Destaques" by discount), search input, `CategoryFilterChips` (filters by `selectedCategory`), a card grid, `ProductRecommendations`, a desktop aside summary and two mobile bottom elements (`showMobileSummaryButton` floating button when the store is closed, and the "Total + Continuar pedido" bar when open).
- Cart summary sheet (`mobileSummaryOpen`) already has the "continue" action and applies the opening-hours gate (`isStoreOpen`). The checkout steps, product dialog and cart logic stay untouched.
- Data already available: `sold_qty` per product, `settings.delivery_pickup` (`delivery_free_above_value`, `pickup_*`; the delivery minimum is deliberately not used — deferred to a future feature), `/api/store/{slug}/reviews/stats` (`endpoints.reviews.public.stats`), `/api/store/{slug}/promotions` (no entry in `endpoints` yet), `StoreHoursBanner` status via `onStatusChange`.
- Variation price is additive to the product price (`calculateSelectionTotal`).
- Coupon application is not implemented (`handleApplyCoupon` shows "em desenvolvimento"), so coupons can only be copied.
- Frontend components live next to the route (`app/store/[slug]/components/`), tests in `components/__tests__/` and `__tests__/page.test.tsx`.

## Goals / Non-Goals

**Goals:**
- Replace the menu-step markup with the delivery-app layout using only existing data and endpoints.
- Move new UI into small presentational components; `page.tsx` keeps state and passes props/callbacks.
- Keep checkout, product dialog, cart sheet and API untouched.

**Non-Goals:**
- Global state refactor of `page.tsx`, virtualization, new dependencies, dark-mode redesign beyond using existing tokens.

## Decisions

1. **Sections instead of filter.** Group `products` by category name in `page.tsx` (`categories` already computed, sorted) into `{ name, products }[]`; products without category go to an "Outros" section at the end. A product in two categories appears in both sections (same as today when filtering). `selectedCategory` state is removed; `productSearchQuery` stays and switches the view to a flat list.
   - Alternative: keep the filter and only restyle → rejected, the reference pattern depends on a continuous list.

2. **Scroll-spy with a rAF-throttled scroll listener.** `MenuCategoryTabs` receives the section ids; on scroll (at most once per animation frame) the active section is the last one whose top is at or above the sticky header line (`stickyOffset`); at the very top of the page it is the first section and at the very bottom the last one. Tab click calls `scrollIntoView({ block: 'start' })`; sections use `scroll-margin-top` equal to `MENU_STICKY_OFFSET`. While a click-driven smooth scroll is in progress, updates are ignored for ~800 ms to avoid the tab flickering through intermediate sections. The active tab is centered by scrolling only the tab strip (`scroller.scrollTo`), because `scrollIntoView` on a tab would interrupt the page's smooth scroll. `category-filter-chips.tsx` stays in the codebase because `app/demo/menu/page.tsx` still uses it; the store page no longer does.
   - Alternative tried first: `IntersectionObserver` with a band below the header → rejected after manual testing: the tail of the previous section still intersected the band, so the tab lagged behind the section title visible under the header. A handful of `getBoundingClientRect` reads per frame is cheap for menu-sized lists.

3. **Sticky menu header right above the list.** The search pill + category tabs block sits between the showcases and the category sections with `position: sticky; top: 0`, so it scrolls with the page until it reaches the top and then stays — no sentinel/observer needed. No ancestor of it may set `overflow` (the old `overflow-x-hidden` on the menu section was removed). The hero's search button focuses that input. The old top header (logo/name/cart/stepper) is not rendered on step 0; on steps ≥ 1 it stays exactly as today (stepper included). Its store-info sheet (contact, address, "Acompanhar pedido") was lifted out of the header so the hero's store name opens it on step 0.

4. **Single price helper.** `getDisplayPrice(product)` in `page.tsx` (or a small `lib` next to the route) returns `{ price, originalPrice, discountPercent, fromPrice }`, where `fromPrice` = `price + min(variation.price)` when variations exist. It replaces the three inline discount computations (grid, highlight row, offers list).

5. **Data fetching.** `page.tsx` adds two best-effort fetches on load: reviews stats (`endpoints.reviews.public.stats`) and promotions (new `endpoints.store.promotions(slug)` entry next to `store.info`/`store.isOpen`, keeping URLs centralized in `api-client.ts`). Failures render nothing (no toast). `ReviewsSection` keeps its own fetch; the duplicate stats call is accepted (one small cached GET) instead of lifting its state.

6. **Hero cover fallback.** Background = gradient of `hsl(var(--primary))` with the top `sold_qty` product image (if any) behind a dark overlay; no new field.

7. **Share.** `navigator.share({ title, url })` when available; otherwise `navigator.clipboard.writeText(url)` + `toast.success`. Coupons use the same clipboard path. `AbortError` from a dismissed share sheet is ignored.

8. **Cart bar replaces both mobile bottom elements on step 0.** `CartBar` renders when `cart.length > 0` and opens `mobileSummaryOpen`; the checkout start and closed-store message stay inside the existing cart sheet, preserving the opening-hours gate from the `public-store-checkout` spec. Desktop keeps the aside summary; the desktop bottom "Continuar pedido" bar stays. The informative message is computed from `cartTotal`, settings and `Σ (price − promotional_price) × qty`.

9. **Product placeholder.** Inline SVG component (lucide food icons on `bg-muted`, `aria-hidden`), no image asset.

10. **Quick add** reuses `handleAddProduct(product, e)` (already stops propagation and opens the dialog for customizable products).

11. **Large screens (`lg`+).** Hero content, menu and order summary share a `max-w-6xl` container. The hero info panel becomes a rounded card floating over a taller cover. The order summary aside is always visible on `lg`+ (empty state when the cart is empty), replacing the floating "Carrinho (0 itens)" pill; it stretches to the column height so its card stays sticky. Product rows switch to two columns from `xl` (1280px), when there is room beside the summary.

## Risks / Trade-offs

- [Mobile checkout needs one extra tap ("Ver carrinho" → continue)] → matches the reference; cart sheet already exists and shows the total.
- [Long menus render all sections at once] → images are lazy via `next/image` with `sizes`; acceptable for typical menus (< 300 items). ponytail: no virtualization until a store reports slowness.
- [Scroll-spy offsets break if header height changes] → header height is a single constant shared by `rootMargin` and `scroll-mt`.
- [Tests coupled to the category filter] → rewrite them as section/tab tests in the same task group; mock `IntersectionObserver` in the test setup if not already present.

## Migration Plan

Frontend-only deploy; rollback by reverting the commit. No data migration.
