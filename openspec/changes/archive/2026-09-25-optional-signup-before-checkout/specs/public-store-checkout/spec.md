## ADDED Requirements

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

## MODIFIED Requirements

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
