# Spec Delta

## Purpose

Lets each restaurant manage the payment methods it accepts, including an optional PIX key, and controls which methods customers can pick in the public menu.

## ADDED Requirements

### Requirement: Tenant-scoped management with permissions
Authenticated users SHALL manage payment methods only within their own tenant, through list, list-active, show, create, update and delete operations, each gated by its permission (`payment-methods.index`, `.show`, `.store`, `.update`, `.destroy`).

#### Scenario: Missing permission
- **WHEN** a user without `payment-methods.store` tries to create a payment method
- **THEN** the request is rejected

#### Scenario: Active list
- **WHEN** a user requests the active payment methods
- **THEN** only methods of their tenant with `is_active = true` are returned, ordered by name

### Requirement: Payment method fields
A payment method SHALL require a `name` (max 255 characters) and SHALL accept an optional `description` (max 1000), an optional `pix_key` (max 255) and an `is_active` boolean. Each payment method SHALL receive a generated uuid on creation.

#### Scenario: PIX key too long
- **WHEN** a payment method is saved with a 256-character `pix_key`
- **THEN** the request fails with "A chave PIX não pode ter mais de 255 caracteres."

#### Scenario: Name missing
- **WHEN** a payment method is saved without a name
- **THEN** the request fails with "O nome da forma de pagamento é obrigatório."

### Requirement: PIX key only for PIX-named methods in the admin form
The admin form SHALL show the PIX key field only when the method's name contains "pix" (case-insensitive), and SHALL save `pix_key` as null (after trimming, empty becomes null) for any other name.

#### Scenario: Renaming away from PIX
- **WHEN** a method named "PIX" with a key is renamed to "Dinheiro" and saved in the admin form
- **THEN** its `pix_key` is cleared

#### Scenario: PIX method without key
- **WHEN** a method named "Pix" is saved with an empty key field
- **THEN** it is stored with `pix_key` null

### Requirement: Deletion blocked while in use
Deleting a payment method SHALL be refused with 409 while any active or non-archived order is linked to it, returning the linked orders. A payment method of another tenant SHALL be treated as not found (404).

#### Scenario: Method used by an open order
- **WHEN** a user deletes a payment method used by an active or non-archived order
- **THEN** the system responds 409 with "Forma de pagamento não pode ser excluída, existe um pedido ativo ou não arquivado vinculado." and the list of linked orders

#### Scenario: Method from another tenant
- **WHEN** a user requests, updates or deletes a payment method uuid that belongs to another tenant
- **THEN** the system responds 404 "Forma de pagamento não encontrada"

### Requirement: Only active methods are usable in the menu
Inactive payment methods SHALL NOT be listed in the public menu and SHALL NOT be accepted on public order submission.

#### Scenario: Deactivated method
- **WHEN** a method is deactivated and a customer submits an order using its uuid
- **THEN** the order is rejected with a validation error on `payment_method`
