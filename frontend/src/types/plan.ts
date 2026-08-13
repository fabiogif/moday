export interface Plan {
  id: number
  name: string
  url: string
  price: number
  description: string | null
  created_at: string
  updated_at: string
}

export interface DetailPlan {
  id: number
  plan_id: number
  name: string
  created_at: string
  updated_at: string
}

export interface PlanWithDetails extends Plan {
  details: DetailPlan[]
}
