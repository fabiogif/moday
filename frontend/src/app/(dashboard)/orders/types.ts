export type OrderStatus = 'Preparo' | 'Pronto' | 'Entregue' | 'Pendente' | 'Em Preparo' | 'Completo' | 'Cancelado' | 'Rejeitado' | 'Em Entrega'

export interface Order {
  id?: number
  identify: string // Campo do backend
  client?: {
    id: number
    name: string
    email: string
    phone: string
    address?: string
    city?: string
    state?: string
    zip_code?: string
    neighborhood?: string
    number?: string
    complement?: string
  }
  table?: {
    id: number
    name: string
    capacity: number
  }
  tenant?: {
    id: number
    name: string
  }
  status: OrderStatus
  total: number
  products: Array<{
    id: number
    name: string
    quantity?: number
    price: number
    total?: number
  }>
  evaluations?: any[]
  date: string // Data formatada dd/mm/yyyy
  comment?: string
  
  // Delivery fields
  is_delivery?: boolean
  use_client_address?: boolean
  delivery_address?: string
  delivery_city?: string
  delivery_state?: string
  delivery_zip_code?: string
  delivery_neighborhood?: string
  delivery_number?: string
  delivery_complement?: string
  delivery_notes?: string
  full_delivery_address?: string

  // Legacy fields for backward compatibility
  orderNumber?: string
  customerName?: string
  customerEmail?: string
  items?: number
  orderDate?: string
  deliveryDate?: string
}

export interface OrderFormValues {
  clientId: string
  products: {
    productId: string
    quantity: number
    price: number
  }[]
  status: OrderStatus
  isDelivery: boolean
  tableId?: string
  total: number
  useClientAddress?: boolean
  deliveryAddress?: string
  deliveryCity?: string
  deliveryState?: string
  deliveryZipCode?: string
  deliveryNeighborhood?: string
  deliveryNumber?: string
  deliveryComplement?: string
  deliveryNotes?: string
  comment?: string
}

export interface OrderDetails {
  identify: string
  client?: {
    id: number
    name: string
    email: string
    phone: string
    address?: string
    city?: string
    state?: string
    zip_code?: string
    neighborhood?: string
    number?: string
    complement?: string
  }
  table?: {
    id: number
    name: string
    capacity: number
  }
  status: OrderStatus
  total: number
  products: Array<{
    id: number
    name: string
    quantity?: number
    price: number
    total?: number
  }>
  date: string
  comment?: string
  is_delivery?: boolean
  use_client_address?: boolean
  delivery_address?: string
  delivery_city?: string
  delivery_state?: string
  delivery_zip_code?: string
  delivery_neighborhood?: string
  delivery_number?: string
  delivery_complement?: string
  delivery_notes?: string
  full_delivery_address?: string

  // Legacy compatibility fields
  id?: number
  orderNumber?: string
  items?: Array<{
    id: number
    name: string
    quantity: number
    price: number
    total: number
  }>
  orderDate?: string
  deliveryDate?: string
}

export interface OrderReceipt {
  id: number
  orderNumber: string
  client?: {
    name: string
    email: string
    phone: string
    address?: string
    city?: string
    state?: string
    zipCode?: string
    neighborhood?: string
    number?: string
    complement?: string
  }
  table?: {
    name: string
    capacity: number
  }
  status: OrderStatus
  total: number
  items: Array<{
    id: number
    name: string
    quantity: number
    price: number
    total: number
  }>
  orderDate: string
  deliveryDate?: string
  comment?: string
  isDelivery?: boolean
  deliveryAddress?: string
  deliveryCity?: string
  deliveryState?: string
  deliveryZipCode?: string
  deliveryNeighborhood?: string
  deliveryNumber?: string
  deliveryComplement?: string
  deliveryNotes?: string
  useClientAddress?: boolean
}
