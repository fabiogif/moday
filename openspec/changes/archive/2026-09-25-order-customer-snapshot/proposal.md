# Proposal

## Why

A guest order on the public menu links to an existing customer found by CPF, e-mail or phone (`PublicClientService::createOrUpdateClient`) and **overwrites** that customer's name, phone and address with whatever was typed. Anyone who types someone else's e-mail, CPF or phone changes that person's record — including customers with a password (accounts created by the optional sign-up). Simply stopping the overwrite is not enough: orders store their delivery address but **not** the customer's name, phone or e-mail; the panel, the kitchen board, receipts, WhatsApp and e-mail notifications read them from the customer record. A customer who changed phone and orders by CPF would show the old number on that order, and the WhatsApp confirmation would go to the old number.

## What Changes

- Orders keep a snapshot of the contact typed at checkout: `customer_name`, `customer_phone`, `customer_email` (nullable columns on `orders`).
- A guest order never overwrites an existing customer: it only fills fields that are empty on the record (name, phone, e-mail, CPF, address). The typed delivery address keeps going to the order as today.
- Everything that shows or uses the contact of an order reads the snapshot first and falls back to the customer record (orders created before the change, or created in the panel/PDV without a snapshot): API `OrderResource`, WhatsApp messages and the destination number of order notifications, the order-created event, the order e-mail, and the panel screens (board, list, details, receipt, success page).

## Capabilities

### New Capabilities
- (none)

### Modified Capabilities
- `public-store-checkout`: adds "Guest order does not overwrite the customer" and "Order keeps the contact typed at checkout".

## Impact

- Database: migration adding three nullable columns to `orders` (no backfill; null means "use the customer record").
- Backend: `PublicClientService::updateExistingClient` (fill-only), `PublicOrderService` (writes the snapshot), `Order` model (`$fillable` + accessors), `OrderResource` (`client_full_name`/`client_phone`/`client_email` from the snapshot), `WhatsAppService`, `SendWhatsAppNotification`, `OrderCreated`, `OrderEmailService`, `OrderService` (reports/listings that print the client name). Tests in `PublicClientCreationTest` (three existing tests assert the overwrite and change on purpose) and order notification tests.
- Frontend: ~30 reads of `order.client.name/phone/email` in `(dashboard)/orders/**` move to one helper that prefers `client_full_name`/`client_phone`/`client_email`.
- Behavior change for restaurants: a returning customer who types a new name/phone no longer updates the customer record; the new data shows on that order only. The record can still be edited in the panel.
- Out of scope: snapshot for orders created in the panel/PDV (they keep reading the customer record); customer self-service profile edit.
