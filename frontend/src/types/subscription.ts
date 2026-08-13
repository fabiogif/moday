export type SubscriptionStatus =
  | "trial"
  | "active"
  | "pending"
  | "under_review"
  | "delinquent"
  | "suspended"
  | "cancelled"
  | "expired"

export interface SubscriptionStatus2 {
  is_trial: boolean
  is_active: boolean
  is_expired: boolean
  days_remaining: number
  expires_at: string | null
  is_expiring_soon: boolean
  needs_payment: boolean
  account_status: SubscriptionStatus
  is_delinquent: boolean
  is_suspended: boolean
  is_cancelled: boolean
  is_in_dunning: boolean
  dunning_day: number
  current_period_end: string | null
  next_billing_date: string | null
  cancellation_pending: boolean
  scheduled_downgrade: {
    plan_id: number
    effective_date: string
  } | null
}

export interface SubscriptionInvoice {
  id: number
  invoice_number: string
  plan_name: string
  amount: number
  status: "pending" | "paid" | "failed" | "refunded" | "cancelled"
  payment_method: string | null
  billing_cycle_start: string
  billing_cycle_end: string | null
  due_date: string
  paid_at: string | null
  created_at: string
}

export interface UpgradeRequest {
  plan_id: number
}

export interface DowngradeRequest {
  plan_id: number
}

export interface ReactivateRequest {
  card_token: string
  payer_email: string
  plan_id?: number
}

export interface UpdateCardRequest {
  card_token: string
}
