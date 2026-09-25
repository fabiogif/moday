# Design

## Context

- `PublicClientService::createOrUpdateClient` finds the customer by CPF → e-mail → phone and calls `updateExistingClient`, which sets `name`, `phone`, `is_active`, address fields and (when free) CPF/e-mail from the request.
- `PublicOrderService` creates the `Order` with `client_id` and, for delivery, `delivery_*` fields copied from the request. No contact fields.
- Readers of the contact: `OrderResource` (`client_full_name`, `client_phone`, `client_email`, nested `client`), `WhatsAppService` (message body), `SendWhatsAppNotification` (destination `client->whatsapp ?? client->phone`), `OrderCreated` (`customer_name` for the panel notification), `OrderEmailService` (recipient `client->email`), `OrderService` (listings/reports), and the panel frontend, which mostly reads the nested `order.client.*`.

## Goals / Non-Goals

**Goals:** nobody can change another customer's data by ordering with their identifiers; each order shows and notifies the contact typed for it.

**Non-Goals:** snapshot for panel/PDV orders; changing how the customer is matched (CPF → e-mail → phone stays); editing customer data from the menu.

## Decisions

1. **Snapshot columns on `orders`, not a new table.** Three nullable strings next to the existing `delivery_*` snapshot. Null means "no snapshot" and readers fall back to the customer, so old orders and panel orders need no backfill.
2. **Fill-only update for every existing customer** (with or without password). Consistent and simple; the restaurant still edits records in the panel. The snapshot covers "customer changed phone".
3. **Model accessors as the single fallback rule**: `Order::contactName()`, `contactPhone()`, `contactEmail()` return the snapshot or the customer's value. Backend readers call them instead of `client->name/phone/email`.
4. **API keeps its shape.** `OrderResource` fills `client_full_name`/`client_phone`/`client_email` from the accessors; the nested `client` stays the real customer record. The frontend switches its ~30 reads to a small helper (`orderContact(order)`) over those flat fields, so screens never mix record and snapshot.
5. **WhatsApp destination uses the snapshot phone.** The confirmation goes to the number typed for that order (normalized the same way as today).

## Risks / Trade-offs

- [Restaurants used guest orders to "refresh" customer data] → Documented behavior change; data is still visible on the order and editable in the panel.
- [Missing one reader leaves a screen showing the record instead of the snapshot] → Tasks list every reader found by search; a test per backend reader; frontend helper plus a grep check for leftover `order.client.name|phone|email`.
- [Migration on a large `orders` table] → Adding nullable columns without default is metadata-only in MySQL 8 (instant); verify on staging data size before deploy.

## Migration Plan

Deploy backend (migration + readers with fallback) and frontend together; either order is safe because readers fall back to the customer when the snapshot is null. Rollback: revert; the columns can stay (ignored) or be dropped by the migration's `down()`.
