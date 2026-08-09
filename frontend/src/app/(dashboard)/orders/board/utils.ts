import type { BoardOrder } from "./types"
import type { OrderDetails } from "../types"

export function normalizeBoardOrder(rawOrder: any): BoardOrder {
  const total =
    typeof rawOrder.total === "string"
      ? parseFloat(rawOrder.total)
      : rawOrder.total || 0

  return {
    identify: rawOrder.identify || String(rawOrder.id),
    total,
    client: rawOrder.client,
    client_full_name: rawOrder.client?.name || rawOrder.client_full_name,
    client_email: rawOrder.client?.email || rawOrder.client_email,
    client_phone: rawOrder.client?.phone || rawOrder.client_phone,
    table: rawOrder.table,
    status: rawOrder.status || "Em Preparo",
    date: rawOrder.date || rawOrder.created_at,
    created_at: rawOrder.created_at,
    products: Array.isArray(rawOrder.products)
      ? rawOrder.products.map((p: any, idx: number) => ({
          identify: p.identify,
          id: p.id ?? idx,
          name: p.name || "Produto",
          price: p.price || "0.00",
          quantity: p.quantity || p.pivot?.quantity || 1,
          total: p.total,
        }))
      : [],
    is_delivery: rawOrder.is_delivery || false,
    use_client_address: rawOrder.use_client_address,
    full_delivery_address: rawOrder.full_delivery_address,
    delivery_address: rawOrder.delivery_address,
    delivery_city: rawOrder.delivery_city,
    delivery_state: rawOrder.delivery_state,
    delivery_zip_code: rawOrder.delivery_zip_code,
    delivery_neighborhood: rawOrder.delivery_neighborhood,
    delivery_number: rawOrder.delivery_number,
    delivery_complement: rawOrder.delivery_complement,
    delivery_notes: rawOrder.delivery_notes,
    comment: rawOrder.comment,
  }
}

export function boardOrderToDetails(order: BoardOrder): OrderDetails {
  const total =
    typeof order.total === "string" ? parseFloat(order.total) : Number(order.total) || 0

  return {
    identify: order.identify,
    client: order.client
      ? {
          id: order.client.id,
          name: order.client.name,
          email: order.client.email || order.client_email || "",
          phone: order.client.phone || order.client_phone || "",
          address: order.client.address,
          city: order.client.city,
          state: order.client.state,
          zip_code: order.client.zip_code,
          neighborhood: order.client.neighborhood,
          number: order.client.number,
          complement: order.client.complement,
        }
      : order.client_full_name
        ? {
            id: 0,
            name: order.client_full_name,
            email: order.client_email || "",
            phone: order.client_phone || "",
          }
        : undefined,
    table: order.table
      ? {
          id: order.table.id,
          name: order.table.name,
          capacity: Number(order.table.capacity) || 0,
        }
      : undefined,
    status: order.status as OrderDetails["status"],
    total,
    products: (order.products || []).map((p, idx) => {
      const price = typeof p.price === "string" ? parseFloat(p.price) : Number(p.price) || 0
      const quantity = p.quantity || 1
      return {
        id: p.id ?? idx,
        name: p.name,
        quantity,
        price,
        total: p.total ?? price * quantity,
      }
    }),
    date: order.date,
    comment: order.comment,
    is_delivery: order.is_delivery,
    use_client_address: order.use_client_address,
    delivery_address: order.delivery_address,
    delivery_city: order.delivery_city,
    delivery_state: order.delivery_state,
    delivery_zip_code: order.delivery_zip_code,
    delivery_neighborhood: order.delivery_neighborhood,
    delivery_number: order.delivery_number,
    delivery_complement: order.delivery_complement,
    delivery_notes: order.delivery_notes,
    full_delivery_address: order.full_delivery_address,
    orderNumber: order.identify,
    orderDate: order.date,
  }
}
