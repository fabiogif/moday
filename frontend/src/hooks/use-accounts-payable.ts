import { useAuthenticatedApi, useMutation } from './use-authenticated-api'
import apiClient, { endpoints } from '@/lib/api-client'

export interface AccountPayable {
  id: number
  uuid: string
  tenant_id: number
  description: string
  category?: {
    id: number
    name: string
    type: string
    color: string
  }
  supplier?: {
    id: number
    name: string
    document: string
  }
  payment_method?: {
    uuid: string
    name: string
  }
  issue_date: string
  issue_date_formatted: string
  due_date: string
  due_date_formatted: string
  payment_date?: string
  payment_date_formatted?: string
  amount: number
  amount_paid: number
  discount: number
  interest: number
  fine: number
  total_amount: number
  total_amount_formatted: string
  status: 'pendente' | 'pago' | 'parcial' | 'vencido' | 'cancelado'
  status_label: string
  document_number?: string
  installment_number?: number
  total_installments?: number
  is_overdue: boolean
  days_until_due: number
  notes?: string
  attachment_path?: string
  created_at: string
  updated_at: string
}

export interface AccountPayableFormData {
  description: string
  financial_category_id?: number
  supplier_id?: number
  payment_method_id?: string
  issue_date: string
  due_date: string
  amount: number
  status: string
  document_number?: string
  installment_number?: number
  total_installments?: number
  notes?: string
}

export interface AccountPayableFilters {
  status?: string
  supplier_id?: number
  category_id?: number
  start_date?: string
  end_date?: string
  overdue?: boolean
}

export interface PaymentData {
  payment_date?: string
  amount_paid: number
  payment_method_id?: string
  notes?: string
}

export interface AccountPayableStats {
  total_pending: number
  total_paid: number
  total_overdue: number
}

export function useAccountsPayable(filters?: AccountPayableFilters, page: number = 1, perPage: number = 15) {
  const queryParams = {
    ...(filters ?? {}),
    page,
    per_page: perPage,
  }
  const endpoint = `${endpoints.accountsPayable.list}?${new URLSearchParams(
    Object.entries(queryParams)
      .filter(([_, v]) => v !== undefined && v !== '')
      .map(([k, v]) => [k, String(v)])
  ).toString()}`

  const result = useAuthenticatedApi<{ accounts_payable: AccountPayable[] }>(endpoint)

  const accountsPayable = result.data && typeof result.data === 'object' && 'accounts_payable' in result.data
    ? (result.data as any).accounts_payable
    : (result.data as any)

  return {
    ...result,
    data: accountsPayable as AccountPayable[],
    pagination: result.pagination,
  }
}

export function useAccountPayableStats() {
  return useAuthenticatedApi<AccountPayableStats>(
    endpoints.accountsPayable.stats
  )
}

export function useAccountPayableAlerts(days = 7) {
  return useAuthenticatedApi<AccountPayable[]>(
    `${endpoints.accountsPayable.alerts}?days=${days}`
  )
}

export function useAccountPayableMutation() {
  return useMutation<AccountPayable, AccountPayableFormData>()
}

