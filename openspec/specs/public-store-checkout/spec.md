# public-store-checkout Specification

## Purpose
Public, unauthenticated menu (cardápio) API that lets a customer browse a restaurant by its slug, choose products and a payment method, and submit an order.

## Requirements

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

### Requirement: Opening hours gate public orders
`GET /store/{slug}/is-open` SHALL return `is_open`, `is_always_open`, `current_time`, `current_day` and the active opening periods grouped by day (up to two periods per day, each with `start`, `end`, `delivery_type`). It SHALL accept an optional `delivery_type` query parameter (`delivery` or `pickup`); when given, `is_open` SHALL consider only periods of that type or `both`, and when absent any period counts. Opening hours SHALL be evaluated in the server's timezone.

The menu SHALL disable starting checkout while the store is closed, treating it as open while the status is loading, when the status request fails, or when no hours are configured. When the customer confirms the shipping step, the menu SHALL check `is-open` for the chosen shipping method and SHALL NOT advance if it is closed, showing a message that names the shipping method.

The order submission API SHALL reject a public order with 422 and an error on `shipping_method` when the store has at least one active opening period, is not marked always open, and no active period of today matching the order's `shipping_method` (or typed `both`) contains the current time.

#### Scenario: Menu while closed
- **WHEN** the customer opens the menu of a store that is closed
- **THEN** the button to start checkout is disabled

#### Scenario: No hours configured
- **WHEN** the store has no active opening hours
- **THEN** the menu allows checkout and the order submission API accepts the order

#### Scenario: Store closed
- **WHEN** the store is outside its opening hours
- **THEN** `is-open` returns `is_open: false`

#### Scenario: Order while closed
- **WHEN** a valid order is submitted while the store is outside all of today's periods
- **THEN** the system responds 422 with an error on `shipping_method` saying the store is closed, and no order is created

#### Scenario: Always open
- **WHEN** the store is marked always open and an order is submitted at any time
- **THEN** the order is accepted

#### Scenario: Delivery closed, pickup open
- **WHEN** the only period covering the current time is typed `pickup` and a `delivery` order is submitted
- **THEN** the system responds 422 on `shipping_method` saying delivery is not available right now, and a `pickup` order at the same time is accepted

#### Scenario: Period typed both
- **WHEN** the period covering the current time is typed `both`
- **THEN** both `delivery` and `pickup` orders are accepted

#### Scenario: Second period of the day
- **WHEN** the day has periods 11:00–14:00 and 18:00–23:00 and an order is submitted at 19:00
- **THEN** the order is accepted

#### Scenario: is-open filtered by shipping method
- **WHEN** the only current period is typed `pickup` and the menu calls `is-open?delivery_type=delivery`
- **THEN** the response has `is_open: false`, while `is-open?delivery_type=pickup` and `is-open` without the parameter return `is_open: true`

#### Scenario: Shipping step blocked in the menu
- **WHEN** the customer chooses delivery and confirms the shipping step while delivery is closed
- **THEN** the menu stays on the shipping step and shows a message that delivery is not available right now

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

### Requirement: Public order tracking by phone
`GET /store/{slug}/orders/track` SHALL require a `phone` with 10 or 11 digits (after removing non-digits) and SHALL return the client's in-progress order, or 404 when none exists.

#### Scenario: Invalid phone
- **WHEN** `phone` has 9 digits
- **THEN** the system responds 422 with "Telefone inválido. Informe DDD + número."

### Requirement: Checkout prefill only for the logged-in customer
The menu SHALL prefill the customer data step (name, e-mail, phone, CPF) and the delivery address only from `GET /store/{slug}/auth/me`, sent with the customer's session, or from the data returned by a registration the customer just completed in the menu. The menu SHALL NOT request customer data based on what a visitor types. `GET /store/{slug}/auth/me` SHALL respond 401 when there is no valid customer session or when the authenticated customer belongs to a store other than `{slug}`. Fields the customer already typed SHALL NOT be overwritten by empty values from the session.

#### Scenario: Public lookup removed
- **WHEN** anyone calls `GET /store/{slug}/clients/lookup?phone=...`
- **THEN** the system responds 404 and returns no customer data

#### Scenario: Guest types a known phone
- **WHEN** a visitor without a customer session types the phone number of an existing customer in checkout
- **THEN** no request with that phone is made to look up customer data and no field is filled automatically

#### Scenario: Logged-in customer
- **WHEN** a customer logged in to this store opens checkout
- **THEN** name, e-mail, phone, CPF and the saved delivery address are prefilled from `auth/me`

#### Scenario: Just registered in the menu
- **WHEN** a customer completes registration in the menu's sign-up dialog
- **THEN** the customer data step is prefilled with the name, e-mail, phone and CPF returned by the registration

#### Scenario: Session from another store
- **WHEN** a customer logged in to store A calls `GET /store/B/auth/me`
- **THEN** the system responds 401 and the checkout of store B is not prefilled

#### Scenario: No session
- **WHEN** checkout calls `auth/me` without a customer session
- **THEN** the system responds 401 and checkout continues with empty fields, without an error message

### Requirement: Optional sign-up before checkout
When a customer without a customer session taps "Continuar pedido" on the menu, the menu SHALL ask whether they want to register, offering "Sim, quero me cadastrar" and "Continuar sem cadastro", and SHALL make clear that registration is optional and the cart is kept. The question SHALL be asked at most once per visit to the menu and SHALL NOT be asked to a customer already logged in. The question SHALL only appear after the existing checks for starting checkout pass (store open, cart not empty).

"Continuar sem cadastro" SHALL continue to the customer data step exactly as checkout did before this requirement. "Sim, quero me cadastrar" SHALL show the customer registration form (name, e-mail, phone, optional CPF, password and confirmation) without leaving the menu. On successful registration the customer SHALL be logged in and checkout SHALL continue to the customer data step prefilled with the registered data.

The cart SHALL be kept when the customer registers, cancels, closes the dialog, goes back or gets a registration error. On a registration error the menu SHALL show the reason, keep the typed data, stay on the form and let the customer retry or continue without registering. When the e-mail is already registered in the store, the message SHALL say so and no new customer record SHALL be created.

#### Scenario: Continue without registering
- **WHEN** a guest with items in the cart taps "Continuar pedido" and chooses "Continuar sem cadastro"
- **THEN** checkout moves to the customer data step with empty fields, the cart unchanged and no registration request made

#### Scenario: Register and continue
- **WHEN** a guest chooses "Sim, quero me cadastrar", fills the form and the registration succeeds
- **THEN** the customer is logged in, checkout moves to the customer data step with name, e-mail and phone prefilled and the cart unchanged

#### Scenario: Registration error
- **WHEN** the registration request fails
- **THEN** the form shows the error, keeps the typed data, does not move to the customer data step and keeps the cart

#### Scenario: E-mail already registered
- **WHEN** the customer registers with an e-mail that already exists in this store
- **THEN** the form shows "Email já cadastrado nesta loja" and offers "Continuar sem cadastro", which continues checkout with the cart unchanged

#### Scenario: Connection failure
- **WHEN** the registration request cannot reach the server
- **THEN** the form shows a message asking the customer to check their connection and try again

#### Scenario: Cancel registration
- **WHEN** the customer closes the dialog
- **THEN** the menu stays on the menu step with the cart unchanged

#### Scenario: Logged-in customer
- **WHEN** a customer already logged in taps "Continuar pedido"
- **THEN** checkout moves to the customer data step without asking about registration

#### Scenario: Asked once per visit
- **WHEN** the customer answered the question, went back to the menu and taps "Continuar pedido" again
- **THEN** checkout moves to the customer data step without asking again

### Requirement: Customer session is per store
The menu SHALL keep a customer's login only for the store where the customer logged in or registered. On another store's menu the customer SHALL be treated as not logged in (the optional sign-up question is shown and no session data is used). Logging out SHALL end the session only for the current store. A session stored before this requirement, without a store, SHALL be discarded.

#### Scenario: Other store
- **WHEN** a customer registered on store A opens store B's menu in the same browser
- **THEN** store B treats the customer as not logged in

#### Scenario: Same store
- **WHEN** the customer returns to store A's menu
- **THEN** the customer is still logged in there

#### Scenario: Logout
- **WHEN** a customer logged in to stores A and B logs out on store A
- **THEN** the session on store B is kept

#### Scenario: Legacy session
- **WHEN** the browser has a customer session saved before sessions were per store
- **THEN** that session is discarded and the customer is treated as not logged in

### Requirement: Customer orders scoped to the store
`GET /store/{slug}/orders` SHALL return the authenticated customer's orders only when the customer belongs to the store `{slug}`. It SHALL respond 401 without order data when there is no valid customer session or when the customer belongs to another store.

#### Scenario: Session from another store
- **WHEN** a customer logged in to store A calls `GET /store/B/orders`
- **THEN** the system responds 401 and returns no orders

#### Scenario: Own store
- **WHEN** a customer logged in to store A calls `GET /store/A/orders`
- **THEN** the system responds 200 with that customer's orders
