# Tasks

## 1. Data and write path

- [x] 1.1 Migration `add_customer_contact_to_orders_table`: nullable `customer_name`, `customer_phone`, `customer_email` after `client_id` (`down()` drops them); add to `Order::$fillable`; verify migrate/rollback on a throwaway database
- [x] 1.2 `Order::contactName()`, `contactPhone()`, `contactEmail()`: snapshot or the customer's value
- [x] 1.3 `PublicOrderService`: store the typed name, phone (digits only, as the customer record) and e-mail on the order
- [x] 1.4 `PublicClientService::updateExistingClient`: only fill empty fields (name, phone, e-mail when not used by another customer, CPF when not used by another customer, address); keep `is_active = true`
- [x] 1.5 Update `PublicClientCreationTest`: the three tests that assert the overwrite now assert the record is kept and the order has the typed contact; add "empty field is filled" and "customer with password is not changed"

## 2. Backend readers

- [x] 2.1 `OrderResource`: `client_full_name` / `client_phone` / `client_email` from the accessors (nested `client` unchanged)
- [x] 2.2 `WhatsAppService` (message body), `SendWhatsAppNotification` (destination phone and greeting), `OrderCreated` (`customer_name`), `OrderEmailService` (recipient and name), `OrderService` listings/reports that print the client name — all via the accessors
- [x] 2.3 Tests: resource returns the snapshot and falls back to the customer; WhatsApp job targets the snapshot phone; order e-mail goes to the snapshot e-mail
- [x] 2.4 Full backend suite, `audit:layers` without new violations, `graphify update .` in `backend/`

## 3. Frontend

- [x] 3.1 Helper `orderContact(order)` in `(dashboard)/orders` returning name/phone/e-mail from `client_full_name`/`client_phone`/`client_email` with fallback to `order.client`
- [x] 3.2 Replace the reads of `order.client.name|phone|email` in the board, list, details, receipt (`order-receipt.ts`, `receipt-dialog.tsx`) and success page; verify with a grep that none remain outside the helper
- [x] 3.3 Tests for the helper and for the board card / receipt showing the snapshot; full frontend suite, TypeScript, ESLint on touched files; `graphify update .` in `frontend/`

## 4. Verification (before any push)

- [x] 4.1 Locally (Docker serving `backend/`): guest order `PV-4KHLUA` with an existing customer's CPF, new name/phone/e-mail → 201; customer record unchanged and not duplicated; order stores the typed contact; `OrderResource` and the restaurant WhatsApp message show it. Panel: board card shows the typed name on desktop and 390×844 while older orders show the customer's name; order details (`/orders?view=PV-4KHLUA`) show typed name, phone and e-mail on desktop. On 390×844 "Todos os Pedidos" timed out locally (api-client 30s abort; the local container serves requests one at a time with `php -S`) — mobile details not checked in the browser; the details dialog uses the same tested helper

## Notes (implementation)

- `ClientCpfFlowTest` also asserted the overwrite (three tests, all with customers that have a password); they now assert the record is kept, like `PublicClientCreationTest`.
- The restaurant WhatsApp message is built from the `SaleOrder`, which has no contact columns; it now receives the mirror `Order` (`SaleOrder::mirrorOrder()`, same identify/tenant — moved from `PublicOrderService::findDashboardOrder`) and uses its contact.
- The PDV also read `order.client.name/phone` (order search and loading an order into the PDV); it uses the helper too.
- Pre-existing, not fixed: a component requests `/orders/undefined/api/order?per_page=1…` (404) — an API base URL built from an undefined value.
