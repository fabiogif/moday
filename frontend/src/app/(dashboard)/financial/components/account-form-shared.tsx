'use client'

import type { ReactNode } from 'react'
import { AlertCircle, FileText, Wallet, ClipboardCheck, type LucideIcon } from 'lucide-react'
import { cn } from '@/lib/utils'

export const ACCOUNT_FORM_STEPS: { label: string; icon: LucideIcon }[] = [
  { label: 'Informações', icon: FileText },
  { label: 'Financeiro', icon: Wallet },
  { label: 'Revisão', icon: ClipboardCheck },
]

export function FieldError({ message }: { message?: string }) {
  if (!message) return null
  return (
    <p className="mt-1 flex items-center gap-1 text-sm text-red-600">
      <AlertCircle className="h-3 w-3 shrink-0" />
      {message}
    </p>
  )
}

export function FormField({
  children,
  className,
}: {
  children: ReactNode
  className?: string
}) {
  return <div className={cn('min-w-0 space-y-2', className)}>{children}</div>
}

export function formatAccountCurrency(value: number | string | undefined | null): string {
  const amount = typeof value === 'string' ? parseFloat(value) : Number(value ?? 0)
  if (Number.isNaN(amount)) return 'R$ 0,00'
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(amount)
}

export function formatAccountDate(value?: string | null): string {
  if (!value) return '—'
  const [year, month, day] = value.split('-')
  if (!year || !month || !day) return value
  return `${day}/${month}/${year}`
}

export const PAYABLE_STATUS_LABELS: Record<string, string> = {
  pendente: 'Pendente',
  pago: 'Pago',
  parcial: 'Parcial',
  vencido: 'Vencido',
  cancelado: 'Cancelado',
}

export const RECEIVABLE_STATUS_LABELS: Record<string, string> = {
  pendente: 'Pendente',
  recebido: 'Recebido',
  parcial: 'Parcial',
  vencido: 'Vencido',
  cancelado: 'Cancelado',
}

export function ReviewRow({ label, value }: { label: string; value: ReactNode }) {
  return (
    <div className="flex min-w-0 flex-col gap-0.5 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
      <dt className="shrink-0 text-sm text-muted-foreground">{label}</dt>
      <dd className="min-w-0 break-words text-sm font-medium sm:text-right">{value || '—'}</dd>
    </div>
  )
}

export const EXPENSE_STATUS_LABELS: Record<string, string> = {
  pendente: 'Pendente',
  pago: 'Pago',
  cancelado: 'Cancelado',
}

/** Classes base para DialogContent de wizards financeiros (evita sm:max-w-lg do Dialog). */
export const WIZARD_DIALOG_CONTENT_CLASS =
  'flex max-h-[90vh] w-[calc(100%-2rem)] max-w-3xl flex-col gap-6 overflow-x-hidden overflow-y-auto p-6 sm:max-w-3xl'

/**
 * Avança o passo no próximo tick para o clique de Continuar não acionar
 * o botão Criar (type=submit) recém-renderizado no mesmo evento.
 */
export function scheduleWizardStep(update: () => void) {
  window.setTimeout(update, 0)
}
