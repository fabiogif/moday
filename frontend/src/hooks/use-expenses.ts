import { useAuthenticatedApi, useMutation } from './use-authenticated-api'
import { endpoints } from '@/lib/api-client'

export interface Expense {
  id: number
  uuid: string
  description: string
  category?: {
    id: number
    uuid: string
    name: string
    type: string
    color: string
  }
  supplier?: {
    id: number
    uuid: string
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
  amount_formatted: string
  status: 'pendente' | 'pago' | 'cancelado'
  status_label: string
  recurrence: string
  recurrence_label: string
  is_overdue: boolean
  days_until_due: number
  notes?: string
  attachment_path?: string
  attachment_url?: string
  created_at: string
  updated_at: string
}

export interface ExpenseFormData {
  description: string
  financial_category_id?: number
  supplier_id?: number
  payment_method_id?: string
  issue_date: string
  due_date: string
  payment_date?: string
  amount: number
  status?: 'pendente' | 'pago' | 'cancelado'
  recurrence?: string
  notes?: string
}

export interface ExpenseStats {
  total_month: number
  pending: number
  paid_month: number
}

/**
 * Hook para listar despesas
 */
export function useExpenses(filters?: {
  status?: string
  category_id?: number
  supplier_id?: number
  start_date?: string
  end_date?: string
}, page: number = 1, perPage: number = 15) {
  const result = useAuthenticatedApi<{ expenses: Expense[] }>(endpoints.expenses.list, {
    immediate: true,
    queryParams: { ...(filters ?? {}), page, per_page: perPage },
  })

  const expenses = result.data && typeof result.data === 'object' && 'expenses' in result.data
    ? (result.data as any).expenses
    : (result.data as any)

  return {
    ...result,
    data: expenses as Expense[],
    pagination: result.pagination,
  }
}

/**
 * Hook para estatísticas de despesas
 */
export function useExpenseStats() {
  return useAuthenticatedApi<ExpenseStats>(endpoints.expenses.stats, {
    immediate: true,
  })
}

/**
 * Hook para detalhes de uma despesa
 */
export function useExpense(uuid: string | null) {
  return useAuthenticatedApi<Expense>(
    uuid ? endpoints.expenses.show(uuid) : '',
    { immediate: !!uuid }
  )
}

/**
 * Hook para operações de mutação
 */
export function useExpenseMutation() {
  return useMutation<Expense, ExpenseFormData>()
}

