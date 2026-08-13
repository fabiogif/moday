import { useAuthenticatedApi, useMutation } from './use-authenticated-api'
import { endpoints } from '@/lib/api-client'

export interface LoyaltyProgram {
  id: number
  uuid: string
  name: string
  description?: string
  is_active: boolean
  points_per_currency: number
  min_purchase_amount?: number
  max_points_per_purchase?: number
  points_expiry_days?: number
  rewards?: LoyaltyReward[]
}

export interface LoyaltyReward {
  id: number
  uuid: string
  name: string
  description?: string
  type: 'discount_percentage' | 'discount_fixed' | 'free_product' | 'free_shipping' | 'custom'
  type_label: string
  points_required: number
  discount_value?: number
  stock_quantity?: number
  is_active: boolean
  is_available: boolean
  has_stock: boolean
}

export interface ClientBalance {
  balance: number
  available_points: number
  has_program: boolean
  program_name?: string
}

export interface LoyaltyTransaction {
  id: number
  uuid: string
  type: 'earn' | 'redeem' | 'expire' | 'adjust'
  type_label: string
  points: number
  balance_after: number
  description?: string
  created_at: string
}

export function useLoyaltyProgram() {
  return useAuthenticatedApi<LoyaltyProgram>(endpoints.loyalty.program, {
    immediate: true,
  })
}

export function useLoyaltyRewards(availableOnly = false) {
  return useAuthenticatedApi<LoyaltyReward[]>(endpoints.loyalty.rewards, {
    immediate: true,
    queryParams: availableOnly ? { available: 'true' } : undefined,
  })
}

export function useClientBalance(clientId: number | null) {
  return useAuthenticatedApi<ClientBalance>(
    clientId ? endpoints.loyalty.clientBalance(clientId) : '',
    { immediate: !!clientId }
  )
}

export function useClientTransactions(clientId: number | null) {
  return useAuthenticatedApi<LoyaltyTransaction[]>(
    clientId ? endpoints.loyalty.clientTransactions(clientId) : '',
    { immediate: !!clientId }
  )
}

export function useLoyaltyMutation() {
  return useMutation<any, any>()
}

