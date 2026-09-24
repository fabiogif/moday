# Spec Delta

## Purpose

Defines what the system records and triggers when a public menu order is accepted, so the order reaches both the sales/billing side and the operational (kitchen/counter) board.

## ADDED Requirements

### Requirement: Atomic order recording
Accepting a public order SHALL, in a single database transaction: create or update the client (with the delivery address when provided), compute the order, apply the coupon, create the sales order with its items, and create the operational order with its products. If any of these steps fails, none of them SHALL persist.

#### Scenario: Operational order fails
- **WHEN** creating the operational order raises an error
- **THEN** the sales order, its items, the client changes and the coupon usage are rolled back and the request fails

### Requirement: Server-side pricing
Item prices SHALL be taken from the product record (promotional price when set, otherwise the regular price), never from the request. The order total SHALL be the sum of unit price times quantity.

#### Scenario: Client sends a price
- **WHEN** the request includes a price for a product
- **THEN** the stored item price is the product's current (promotional or regular) price

### Requirement: Coupon application
When a valid coupon code is provided, the system SHALL record it on the order and store `subtotal`, `discount_amount` and the final `total`. Without a coupon, `discount_amount` SHALL be zero and `total` equal to `subtotal`.

#### Scenario: Order with coupon
- **WHEN** an order totaling 100,00 uses a coupon worth 10,00
- **THEN** the order stores subtotal 100,00, discount 10,00 and total 90,00, and the response returns the coupon code

### Requirement: Paired sales order and operational order
Each accepted public order SHALL produce two records sharing the same order number (`identify`):
- a sales order with status `orcamento`;
- an operational order with `origin = store`, the tenant's initial order status (falling back to "Em Preparo" when none is marked initial), the payment method name and uuid, coupon data, delivery data when `is_delivery` is true, the delivery notes as comment, the customer's WhatsApp notification preference (default true), and one product line per distinct product with quantities summed.

#### Scenario: Same product twice
- **WHEN** the order lists the same product in two lines with quantities 1 and 2
- **THEN** the operational order has a single line for that product with quantity 3

#### Scenario: Tenant with custom initial status
- **WHEN** the tenant has an order status marked as initial
- **THEN** the operational order starts in that status

### Requirement: Post-commit side effects do not fail the order
After the transaction commits, the system SHALL send the order-completion email when the tenant's plan includes it, and SHALL broadcast a sale-order-created event. A failure in either SHALL be logged and SHALL NOT change the successful response.

#### Scenario: Email provider down
- **WHEN** the completion email fails to send
- **THEN** the order is still returned as created (201)

### Requirement: Automatic WhatsApp delivery disabled
The system SHALL NOT automatically send the order to the restaurant's or the customer's WhatsApp. Delivery to the restaurant is manual, through the `wa.me` link returned to the customer.

#### Scenario: Tenant with WhatsApp configured
- **WHEN** a tenant with a connected WhatsApp instance receives a public order
- **THEN** no WhatsApp message is queued and the response has `whatsapp_sent: false`
