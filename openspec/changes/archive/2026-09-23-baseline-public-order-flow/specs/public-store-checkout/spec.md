# Spec Delta

## Purpose

Public, unauthenticated menu (cardápio) API that lets a customer browse a restaurant by its slug, choose products and a payment method, and submit an order.

## ADDED Requirements

### Requirement: Store resolved by slug
Every public store endpoint SHALL resolve the tenant from the `{slug}` path segment and SHALL respond 404 when no active tenant matches the slug.

#### Scenario: Unknown slug
- **WHEN** a client calls any `/store/{slug}/...` endpoint with a slug that matches no active tenant
- **THEN** the system responds 404 with a "Loja não encontrada" message

#### Scenario: Known slug
- **WHEN** a client calls `GET /store/{slug}/info` with a valid slug
- **THEN** the system responds 200 with the store information without requiring authentication

### Requirement: Public catalog and payment methods
The system SHALL expose the store's products and its active payment methods publicly. Payment methods SHALL be ordered by name and SHALL include `uuid`, `name`, `type`, `description`, `pix_key` and `is_active`.

#### Scenario: Inactive payment method hidden
- **WHEN** a tenant has one active and one inactive payment method
- **THEN** `GET /store/{slug}/payment-methods` returns only the active one

#### Scenario: PIX key exposed
- **WHEN** an active payment method has a non-empty `pix_key`
- **THEN** the public payment method list includes that `pix_key`

### Requirement: Opening hours are informational
`GET /store/{slug}/is-open` SHALL return `is_open`, `is_always_open`, `current_time`, `current_day` and the active opening periods grouped by day (up to two periods per day, each with `start`, `end`, `delivery_type`). The menu SHALL disable starting checkout while the store is closed, treating it as open while the status is loading, when the status request fails, or when no hours are configured. The order submission API SHALL NOT reject orders based on opening hours.

#### Scenario: Menu while closed
- **WHEN** the customer opens the menu of a store that is closed
- **THEN** the button to start checkout is disabled

#### Scenario: No hours configured
- **WHEN** the store has no active opening hours
- **THEN** the menu allows checkout

#### Scenario: Store closed
- **WHEN** the store is outside its opening hours
- **THEN** `is-open` returns `is_open: false`

#### Scenario: Order while closed
- **WHEN** a valid order is submitted while the store is closed
- **THEN** the order is accepted like any other order

### Requirement: Order submission validation
`POST /store/{slug}/orders` SHALL validate the request and respond 422 with field errors when:
- `client.name` or `client.phone` is missing (`client.email` and `client.cpf` are optional);
- `delivery.is_delivery` is missing, or it is true and any of address, number, neighborhood, city, state or zip code is missing;
- `products` is empty, a product is not an active product of this tenant, or the requested quantity exceeds the product's stock;
- `payment_method` is not an active payment method of this tenant;
- `shipping_method` is not `delivery` or `pickup`;
- `coupon_code` is provided and is not valid for this store, the computed total and the client (email, else phone).

Fields not listed in the validation rules (including per-product optionals) SHALL be ignored.

#### Scenario: Insufficient stock
- **WHEN** an order requests 5 units of a product with 3 in stock
- **THEN** the system responds 422 with an error on `products.N.quantity` naming the available quantity

#### Scenario: Payment method from another store
- **WHEN** `payment_method` is the uuid of a payment method belonging to a different tenant
- **THEN** the system responds 422 with "A forma de pagamento selecionada é inválida."

#### Scenario: Pickup without address
- **WHEN** `delivery.is_delivery` is false and no address is sent
- **THEN** address fields are not required

### Requirement: Order submission rate limit
Order submission SHALL be limited to 10 requests per minute; order tracking to 10 per minute; client lookup to 20 per minute.

#### Scenario: Burst of orders
- **WHEN** the same client sends an 11th order within one minute
- **THEN** the system responds 429

### Requirement: Order submission response
On success the system SHALL respond 201 with `order_id` (the order number), `subtotal`, `discount_amount` and `total` formatted in pt-BR (`1.234,56`), `coupon_code` (or null), `whatsapp_message`, `whatsapp_link` (a `wa.me` link to the store's WhatsApp, falling back to its phone) and `whatsapp_sent`, which is currently always `false`.

#### Scenario: Successful order
- **WHEN** a valid order is submitted
- **THEN** the response is 201 with `whatsapp_sent: false` and a `whatsapp_link` the customer can open to send the order manually

#### Scenario: Duplicate client data
- **WHEN** order creation hits a unique-constraint violation on client email or CPF
- **THEN** the system responds 422 with a message explaining which data is already registered

### Requirement: PIX key shown to the customer
When the selected payment method has a PIX key, the menu SHALL display the key with a copy-to-clipboard action during payment selection and on the order success screen.

#### Scenario: Copy PIX key
- **WHEN** the customer selects a payment method that has a `pix_key` and taps copy
- **THEN** the key is copied to the clipboard and a "Chave PIX copiada!" confirmation is shown

#### Scenario: Method without key
- **WHEN** the selected payment method has no `pix_key`
- **THEN** no PIX key box is displayed

### Requirement: Client lookup for checkout autofill
`GET /store/{slug}/clients/lookup` SHALL accept `cpf` or `phone`, respond 422 when neither is given, and otherwise respond 200 with `exists`, `client` and `address` (null values when not found). It SHALL NOT require authentication.

#### Scenario: Unknown client
- **WHEN** the lookup phone matches no client of the store
- **THEN** the response is 200 with `exists: false`

### Requirement: Public order tracking by phone
`GET /store/{slug}/orders/track` SHALL require a `phone` with 10 or 11 digits (after removing non-digits) and SHALL return the client's in-progress order, or 404 when none exists.

#### Scenario: Invalid phone
- **WHEN** `phone` has 9 digits
- **THEN** the system responds 422 with "Telefone inválido. Informe DDD + número."
