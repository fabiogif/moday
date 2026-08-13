import type { AccountPayableStats } from '@/hooks/use-accounts-payable'
import type { AccountReceivableStats } from '@/hooks/use-accounts-receivable'
import type { ExpenseStats } from '@/hooks/use-expenses'

export function parseFinancialAmount(value: number | string | null | undefined): number {
  if (value === null || value === undefined) return 0
  if (typeof value === 'number') return value
  const raw = value.toString().trim()
  if (!raw) return 0
  const sanitized = raw.replace(/[^\d.,-]/g, '')
  if (!sanitized) return 0
  const lastComma = sanitized.lastIndexOf(',')
  const lastDot = sanitized.lastIndexOf('.')
  const decimalSeparator = lastComma > lastDot ? ',' : '.'
  let normalized = sanitized
  if (decimalSeparator === ',') {
    normalized = normalized.replace(/\./g, '').replace(/,/g, '.')
  } else {
    const firstDot = sanitized.indexOf('.')
    normalized =
      firstDot === lastDot
        ? sanitized.replace(/,/g, '')
        : sanitized.replace(/,/g, '').replace(/\.(?=.*\.)/g, '')
  }
  const parsed = Number(normalized)
  return Number.isNaN(parsed) ? 0 : parsed
}

export function formatFinancialCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

export type FinancialDashboardMetrics = {
  totalReceivable: number
  totalReceived: number
  totalExpenses: number
  totalPayablePending: number
  totalOverdue: number
  payableTotal: number
  balance: number
  receiveProgress: number
  overduePercent: number
}

export function computeFinancialDashboardMetrics(
  receivableStats?: AccountReceivableStats | null,
  payableStats?: AccountPayableStats | null,
  expenseStats?: ExpenseStats | null,
): FinancialDashboardMetrics {
  const totalReceived = parseFinancialAmount(receivableStats?.total_received)
  const totalReceivablePending = parseFinancialAmount(receivableStats?.total_pending)
  const totalReceivable = totalReceivablePending + totalReceived
  const totalExpenses = parseFinancialAmount(expenseStats?.total_month)
  const totalPayablePending = parseFinancialAmount(payableStats?.total_pending)
  const totalOverdue = parseFinancialAmount(payableStats?.total_overdue)
  const payableTotal = totalPayablePending + totalOverdue
  const balance = parseFloat(
    (totalReceivable - totalExpenses - totalPayablePending - totalOverdue).toFixed(2),
  )

  const receiveProgress =
    totalReceivable > 0
      ? Math.min(100, Math.round((totalReceived / totalReceivable) * 100))
      : 0

  const overduePercent =
    payableTotal > 0 ? Math.min(100, Math.round((totalOverdue / payableTotal) * 100)) : 0

  return {
    totalReceivable,
    totalReceived,
    totalExpenses,
    totalPayablePending,
    totalOverdue,
    payableTotal,
    balance,
    receiveProgress,
    overduePercent,
  }
}
