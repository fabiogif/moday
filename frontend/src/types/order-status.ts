export interface OrderStatus {
  uuid: string
  name: string
  slug: string
  description?: string
  color: string
  icon: string
  order_position: number
  is_initial: boolean
  is_final: boolean
  is_active: boolean
  orders_count?: number
  can_delete?: boolean
  can_reorder?: boolean
}

export interface OrderStatusForm {
  name: string
  description?: string
  color: string
  icon: string
  is_initial?: boolean
  is_final?: boolean
  is_active?: boolean
}

