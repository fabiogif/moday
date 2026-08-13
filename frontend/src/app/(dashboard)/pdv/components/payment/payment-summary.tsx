"use client"

import { Separator } from "@/components/ui/separator"
import { cn } from "@/lib/utils"
import { type PaymentMethod } from "./payment-method-card"

interface PaymentSummaryItem {
  method: PaymentMethod
  amount: number
}

interface PaymentSummaryProps {
  items: PaymentSummaryItem[]
  orderTotal: number
  formatCurrency: (value: number) => string
  className?: string
}

export function PaymentSummary({
  items,
  orderTotal,
  formatCurrency,
  className,
}: PaymentSummaryProps) {
  const totalPaid = items.reduce((sum, item) => sum + item.amount, 0)
  const remaining = orderTotal - totalPaid
  const change = totalPaid > orderTotal ? totalPaid - orderTotal : 0

  return (
    <div className={cn("space-y-2", className)}>
      <div className="space-y-1">
        <h4 className="text-xs font-semibold">Resumo do Pagamento</h4>
        <div className="space-y-1">
          {items.map((item, index) => (
            <div
              key={`${item.method.uuid}-${index}`}
              className="flex items-center justify-between text-xs"
            >
              <span className="text-muted-foreground">{item.method.name}:</span>
              <span className="font-medium">{formatCurrency(item.amount)}</span>
            </div>
          ))}
        </div>
      </div>

      <Separator />

      <div className="space-y-1">
        <div className="flex items-center justify-between text-xs">
          <span className="text-muted-foreground">Total do pedido:</span>
          <span className="font-semibold">{formatCurrency(orderTotal)}</span>
        </div>
        <div className="flex items-center justify-between text-xs">
          <span className="text-muted-foreground">Total pago:</span>
          <span className="font-semibold">{formatCurrency(totalPaid)}</span>
        </div>
        {remaining > 0 && (
          <div className="flex items-center justify-between text-xs text-amber-600 dark:text-amber-400">
            <span>Faltando:</span>
            <span className="font-semibold">{formatCurrency(remaining)}</span>
          </div>
        )}
        {change > 0 && (
          <div className="flex items-center justify-between text-xs text-green-600 dark:text-green-400">
            <span>Troco:</span>
            <span className="font-semibold">{formatCurrency(change)}</span>
          </div>
        )}
        {remaining === 0 && change === 0 && (
          <div className="flex items-center justify-between text-xs text-green-600 dark:text-green-400">
            <span>Status:</span>
            <span className="font-semibold">Pago</span>
          </div>
        )}
      </div>
    </div>
  )
}

