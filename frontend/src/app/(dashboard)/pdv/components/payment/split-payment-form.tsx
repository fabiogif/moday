"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Separator } from "@/components/ui/separator"
import { PaymentAmountInput } from "./payment-amount-input"
import { PaymentSummary } from "./payment-summary"
import { type PaymentMethod } from "./payment-method-card"
import { Plus, Trash2 } from "lucide-react"
import { cn } from "@/lib/utils"

export interface SplitPaymentItem {
  method: PaymentMethod
  amount: number | null
}

interface SplitPaymentFormProps {
  methods: PaymentMethod[]
  orderTotal: number
  items: SplitPaymentItem[]
  onChange: (items: SplitPaymentItem[]) => void
  formatCurrency: (value: number) => string
  className?: string
}

export function SplitPaymentForm({
  methods,
  orderTotal,
  items,
  onChange,
  formatCurrency,
  className,
}: SplitPaymentFormProps) {
  const addPayment = () => {
    if (methods.length > 0) {
      onChange([
        ...items,
        {
          method: methods[0],
          amount: null,
        },
      ])
    }
  }

  const removePayment = (index: number) => {
    onChange(items.filter((_, i) => i !== index))
  }

  const updatePaymentMethod = (index: number, method: PaymentMethod) => {
    const newItems = [...items]
    newItems[index].method = method
    onChange(newItems)
  }

  const updatePaymentAmount = (index: number, amount: number | null) => {
    const newItems = [...items]
    newItems[index].amount = amount
    onChange(newItems)
  }

  const totalPaid = items.reduce((sum, item) => sum + (item.amount || 0), 0)
  const remaining = orderTotal - totalPaid

  return (
    <div className={cn("space-y-4", className)}>
      <div className="flex items-center justify-between">
        <Label className="text-base font-semibold">Pagamento Dividido</Label>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={addPayment}
          disabled={items.length >= methods.length}
        >
          <Plus className="h-4 w-4 mr-1" />
          Adicionar
        </Button>
      </div>

      <div className="space-y-4">
        {items.map((item, index) => (
          <div
            key={index}
            className="p-4 rounded-lg border space-y-3"
          >
            <div className="flex items-center justify-between">
              <Label className="text-sm font-medium">
                Pagamento {index + 1}
              </Label>
              {items.length > 1 && (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => removePayment(index)}
                  className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              )}
            </div>

            <div className="space-y-2">
              <div>
                <Label className="text-xs text-muted-foreground">
                  Método de pagamento
                </Label>
                <select
                  value={item.method.uuid}
                  onChange={(e) => {
                    const method = methods.find(m => m.uuid === e.target.value)
                    if (method) {
                      updatePaymentMethod(index, method)
                    }
                  }}
                  className="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  {methods.map((method) => (
                    <option key={method.uuid} value={method.uuid}>
                      {method.name}
                    </option>
                  ))}
                </select>
              </div>

              <PaymentAmountInput
                value={item.amount}
                onChange={(amount) => updatePaymentAmount(index, amount)}
                orderTotal={orderTotal}
                label="Valor"
                placeholder="0,00"
                showChange={false}
              />
            </div>
          </div>
        ))}
      </div>

      <Separator />

      <PaymentSummary
        items={items
          .filter(item => item.amount !== null && item.amount > 0)
          .map(item => ({
            method: item.method,
            amount: item.amount!,
          }))}
        orderTotal={orderTotal}
        formatCurrency={formatCurrency}
      />

      {remaining > 0 && (
        <div className="p-3 rounded-lg bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800">
          <p className="text-sm text-amber-900 dark:text-amber-100">
            <strong>Faltando:</strong> {formatCurrency(remaining)}
          </p>
        </div>
      )}
    </div>
  )
}

