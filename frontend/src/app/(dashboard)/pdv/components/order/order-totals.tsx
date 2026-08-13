"use client"

import { useMemo } from "react"
import { cn } from "@/lib/utils"

interface OrderTotalsProps {
  subtotal: number
  taxes?: number
  discounts?: number
  formatCurrency: (value: number) => string
  className?: string
}

export function OrderTotals({
  subtotal,
  taxes = 0,
  discounts = 0,
  formatCurrency,
  className,
}: OrderTotalsProps) {
  const finalTotal = useMemo(() => {
    return subtotal + taxes - discounts
  }, [subtotal, taxes, discounts])

  return (
    <div className={cn("rounded-md border bg-muted/30 px-2.5 py-2 space-y-1", className)}>
      <div className="flex items-center justify-between text-xs">
        <span className="text-muted-foreground">Subtotal</span>
        <span className="tabular-nums font-medium text-foreground">
          {formatCurrency(subtotal)}
        </span>
      </div>

      {discounts > 0 && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-muted-foreground">Descontos</span>
          <span className="tabular-nums font-medium text-emerald-600 dark:text-emerald-400">
            −{formatCurrency(discounts)}
          </span>
        </div>
      )}

      {taxes > 0 && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-muted-foreground">Impostos</span>
          <span className="tabular-nums font-medium text-foreground">
            {formatCurrency(taxes)}
          </span>
        </div>
      )}

      <div className="mt-0.5 flex items-center justify-between border-t pt-1.5">
        <span className="text-xs font-semibold text-foreground">Total</span>
        <span className="text-base font-bold text-primary tabular-nums sm:text-lg">
          {formatCurrency(finalTotal)}
        </span>
      </div>
    </div>
  )
}
