/**
 * Contato do pedido para exibição. A API devolve em `client_full_name` / `client_phone` /
 * `client_email` o contato digitado no checkout (quando existe) ou o do cliente vinculado —
 * por isso esses campos vêm antes do `client` aninhado, que é sempre o cadastro.
 */
export interface OrderContact {
  name: string
  email: string
  phone: string
}

type ContactPerson = { name?: string | null; email?: string | null; phone?: string | null } | null

export interface OrderContactSource {
  client?: ContactPerson
  client_full_name?: string | null
  client_email?: string | null
  client_phone?: string | null
  // Campos legados que algumas telas ainda recebem
  client_name?: string | null
  customerName?: string | null
  customerEmail?: string | null
  customerPhone?: string | null
  customer?: ContactPerson
}

export function orderContact(order: OrderContactSource): OrderContact {
  return {
    name: order.client_full_name || order.client?.name || order.customerName || order.customer?.name || order.client_name || '',
    email: order.client_email || order.client?.email || order.customerEmail || order.customer?.email || '',
    phone: order.client_phone || order.client?.phone || order.customerPhone || order.customer?.phone || '',
  }
}
