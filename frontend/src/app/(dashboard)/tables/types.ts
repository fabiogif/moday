export interface TableData {
  id: number
  identify: string
  uuid: string
  name: string
  description?: string
  capacity: number
  created_at: string
  created_at_formatted: string
  updated_at: string
  status?: string
  location?: string
  isActive?: boolean
}

export interface TableFormValues {
  identify: string
  name: string
  description?: string
  capacity: number
}