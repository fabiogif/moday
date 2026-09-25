# Spec Delta

## REMOVED Requirements

### Requirement: Client lookup for checkout autofill
**Reason**: It returned a customer's name, e-mail, phone, full CPF and address to any unauthenticated caller who knew a phone number or CPF.
**Migration**: The route is removed (404). Checkout prefill uses the logged-in customer's data from `GET /store/{slug}/auth/me`; guests type their data.

## ADDED Requirements

### Requirement: Checkout prefill only for the logged-in customer
The menu SHALL prefill the customer data step (name, e-mail, phone, CPF) and the delivery address only from `GET /store/{slug}/auth/me`, sent with the customer's session. The menu SHALL NOT request customer data based on what a visitor types. `GET /store/{slug}/auth/me` SHALL respond 401 when there is no valid customer session or when the authenticated customer belongs to a store other than `{slug}`. Fields the customer already typed SHALL NOT be overwritten by empty values from the session.

#### Scenario: Public lookup removed
- **WHEN** anyone calls `GET /store/{slug}/clients/lookup?phone=...`
- **THEN** the system responds 404 and returns no customer data

#### Scenario: Guest types a known phone
- **WHEN** a visitor without a customer session types the phone number of an existing customer in checkout
- **THEN** no request with that phone is made to look up customer data and no field is filled automatically

#### Scenario: Logged-in customer
- **WHEN** a customer logged in to this store opens checkout
- **THEN** name, e-mail, phone, CPF and the saved delivery address are prefilled from `auth/me`

#### Scenario: Session from another store
- **WHEN** a customer logged in to store A calls `GET /store/B/auth/me`
- **THEN** the system responds 401 and the checkout of store B is not prefilled

#### Scenario: No session
- **WHEN** checkout calls `auth/me` without a customer session
- **THEN** the system responds 401 and checkout continues with empty fields, without an error message
