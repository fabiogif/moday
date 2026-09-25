## ADDED Requirements

### Requirement: Guest order does not overwrite the customer
When a public order is linked to an existing customer of the store (found by CPF, e-mail or phone), the system SHALL NOT change any non-empty field of that customer's record. It MAY fill fields that are empty on the record (name, phone, e-mail, CPF, address) with the data typed at checkout. This SHALL apply to customers with and without a password.

#### Scenario: Different name and phone for an existing CPF
- **WHEN** a guest orders with the CPF of an existing customer named "João" with phone 71999999999, typing the name "João Silva Santos" and the phone 71988888888
- **THEN** the order is created, no customer is duplicated, and the customer record keeps "João" and 71999999999

#### Scenario: Empty field on the record
- **WHEN** the existing customer has no e-mail and the guest types one that no other customer uses
- **THEN** the customer record gets that e-mail

#### Scenario: Customer with password
- **WHEN** a guest orders with the e-mail of a customer who has a password, typing a different name
- **THEN** the customer's name is unchanged

### Requirement: Order keeps the contact typed at checkout
Each public order SHALL store the customer name, phone and e-mail typed at checkout. Everything that shows or uses the contact of an order — order API fields `client_full_name`, `client_phone` and `client_email`, the panel order screens and receipt, WhatsApp messages and the destination of the order WhatsApp notification, the order-created notification and the order e-mail — SHALL use the order's stored contact, falling back to the linked customer's data when the order has none (orders created before this requirement or in the panel).

#### Scenario: Returning customer with a new phone
- **WHEN** an existing customer orders typing a new phone 71988888888
- **THEN** that order shows 71988888888 in the panel and receipt, and its WhatsApp confirmation is sent to 71988888888, while the customer record keeps the old phone

#### Scenario: Order without stored contact
- **WHEN** an order has no stored contact (created before this change or in the panel)
- **THEN** its contact fields show the linked customer's name, phone and e-mail
