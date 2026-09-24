# Proposal

## Why

OpenSpec has no specs yet, so every future change to the public menu (cardápio), order intake or payment methods would be written against an undocumented baseline. This change records the behavior the code has **today** so later changes can express themselves as deltas.

## What Changes

- No code changes. This is a documentation-only baseline: the specs describe observed behavior, not desired behavior.
- Adds three capabilities describing the current public order flow.
- Known gaps found while reading the code are listed below as follow-ups, deliberately **not** written as requirements:
  - **Store hours not enforced at order time**: `POST /store/{slug}/orders` accepts orders regardless of opening hours; `GET /store/{slug}/is-open` is informational only (the menu UI gates on it).
  - **Optionals silently dropped**: the price calculation sums optional prices sent by the client, but the public order request does not validate `products.*.optionals`, so they are stripped and never charged or recorded.
  - **Unauthenticated client lookup**: `GET /store/{slug}/clients/lookup` returns a client's data and address by CPF or phone, protected only by rate limiting (20/min).
  - **PIX detection by name**: a payment method is treated as PIX in the admin form when its name matches `/pix/i`; the backend accepts `pix_key` on any method, and the menu shows the key whenever the selected method has one.
  - **Dead code**: `PublicOrderService::buildShippingData()` has no callers.
  - **WhatsApp auto-send disabled**: the code path exists but is commented out; the response always carries `whatsapp_sent = false` plus a manual `wa.me` link.

## Capabilities

### New Capabilities
- `public-store-checkout`: the public, unauthenticated menu API by tenant slug — store info, products, payment methods, opening hours, coupon preview, client lookup, order tracking, order submission and its response.
- `order-intake`: what happens when a public order is accepted — client upsert, server-side pricing, coupon application, the paired sales order + operational (Kanban) order, and post-commit side effects.
- `payment-methods`: tenant-scoped management of payment methods, including active flag and optional PIX key, and how they are exposed to the public menu.

### Modified Capabilities
- (none — no specs exist yet)

## Impact

- Code: none.
- Documentation: `openspec/specs/public-store-checkout`, `openspec/specs/order-intake`, `openspec/specs/payment-methods` after archive.
- Follow-up changes can target the gaps above (e.g. enforce store hours, support optionals, protect client lookup).
