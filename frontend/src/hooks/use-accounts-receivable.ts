import { useAuthenticatedApi, useMutation } from './use-authenticated-api'
import apiClient, { endpoints } from '@/lib/api-client'

export interface AccountReceivable {
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
  client?: {
    id: number
    name: string
    email: string
    phone: string
  }
  order_id?: number
  order?: {
    id: number
    identify: string
  }
  payment_method?: {
    uuid: string
    name: string
  }
  issue_date: string
  issue_date_formatted: string
  due_date: string
  due_date_formatted: string
  receipt_date?: string
  receipt_date_formatted?: string
  amount: number
  amount_received: number
  discount: number
  interest: number
  fine: number
  total_amount: number
  total_amount_formatted: string
  status: 'pendente' | 'recebido' | 'parcial' | 'vencido' | 'cancelado'
  status_label: string
  document_number?: string
  installment_number?: number
  total_installments?: number
  is_overdue: boolean
  days_until_due: number
  notes?: string
  created_at: string
  updated_at: string
}

export interface AccountReceivableFormData {
  description: string
  financial_category_id?: number
  client_id?: number
  order_id?: number
  payment_method_id?: string
  issue_date: string
  due_date: string
  amount: number
  status: string
  document_number?: string
  installment_number?: number
  total_installments?: number
  discount?: number
  interest?: number
  fine?: number
  notes?: string
}

export interface AccountReceivableFilters {
  status?: string
  client_id?: number
  category_id?: number
  order_id?: number
  start_date?: string
  end_date?: string
  overdue?: boolean
}

export interface ReceiptData {
  receipt_date?: string
  amount_received: number
  payment_method_id?: string
  notes?: string
}

export interface AccountReceivableStats {
  total_pending: number
  total_received: number
  total_overdue: number
}

export function useAccountsReceivable(filters?: AccountReceivableFilters, page: number = 1, perPage: number = 15) {
  const queryParams = {
    ...(filters ?? {}),
    page,
    per_page: perPage,
  }
  const endpoint = `${endpoints.accountsReceivable.list}?${new URLSearchParams(
    Object.entries(queryParams)
      .filter(([_, v]) => v !== undefined && v !== '')
      .map(([k, v]) => [k, String(v)])
  ).toString()}`

  const result = useAuthenticatedApi<{ accounts_receivable: AccountReceivable[] }>(endpoint)

  const accountsReceivable = result.data && typeof result.data === 'object' && 'accounts_receivable' in result.data
    ? (result.data as any).accounts_receivable
    : (result.data as any)

  return {
    ...result,
    data: accountsReceivable as AccountReceivable[],
    pagination: result.pagination,
  }
}

export function useAccountReceivableStats() {
  return useAuthenticatedApi<AccountReceivableStats>(
    endpoints.accountsReceivable.stats
  )
}

export function useAccountsReceivableByOrder(orderId: number) {
  return useAuthenticatedApi<AccountReceivable[]>(
    endpoints.accountsReceivable.fromOrder(orderId)
  )
}

export function useAccountReceivableMutation() {
  return useMutation<AccountReceivable, AccountReceivableFormData>()
}

