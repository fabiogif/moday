export interface ServiceTypeData {
  id: number
  uuid: string
  identify: string
  name: string
  slug: string
  description?: string
  is_active: boolean
  requires_address: boolean
  requires_table: boolean
  available_in_menu: boolean
  order_position: number
  created_at: string
  created_at_formatted: string
  updated_at: string
}

export interface ServiceTypeFormValues {
  name: string
  slug?: string
  description?: string
  is_active?: boolean
  requires_address?: boolean
  requires_table?: boolean
  available_in_menu?: boolean
  order_position?: number
}

