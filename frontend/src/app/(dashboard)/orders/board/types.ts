export interface BoardProduct {
  identify?: string
  id?: number
  name: string
  price: string | number
  quantity?: number
  total?: number
}

export interface BoardClient {
  id: number
  name: string
  email?: string
  phone?: string
  address?: string
  city?: string
  state?: string
  zip_code?: string
  neighborhood?: string
  number?: string
  complement?: string
}

export interface BoardTable {
  id: number
  identify?: string
  name: string
  capacity?: string | number
}

export type BoardOrderStatus = "Em Preparo" | "Pronto" | "Entregue" | "Cancelado"

export interface BoardOrder {
  identify: string
  total: string | number
  client?: BoardClient
  client_full_name?: string
  client_email?: string
  client_phone?: string
  table?: BoardTable
  status: string
  date: string
  created_at: string
  products: BoardProduct[]
  is_delivery?: boolean
  use_client_address?: boolean
  full_delivery_address?: string
  delivery_address?: string
  delivery_city?: string
  delivery_state?: string
  delivery_zip_code?: string
  delivery_neighborhood?: string
  delivery_number?: string
  delivery_complement?: string
  delivery_notes?: string
  comment?: string
}

export interface BoardColumnDef {
  id: BoardOrderStatus
  title: string
  color: string
}
