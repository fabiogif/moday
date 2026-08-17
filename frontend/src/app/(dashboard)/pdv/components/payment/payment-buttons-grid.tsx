"use client"

import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"
import { CreditCard, Banknote, Smartphone, Building2 } from "lucide-react"
import type { PaymentMethod } from "./payment-method-card"

interface PaymentButtonsGridProps {
  methods: PaymentMethod[]
  selectedMethods: PaymentMethod[]
  onSelect: (method: PaymentMethod) => void
  onRemove?: (method: PaymentMethod) => void
  className?: string
}

// Mapeamento de ícones por nome do método
const getPaymentIcon = (methodName: string) => {
  const name = methodName.toLowerCase()
  if (name.includes('dinheiro') || name.includes('money') || name.includes('cash')) {
    return Banknote
  }
  if (name.includes('pix')) {
    return Smartphone
  }
  if (name.includes('débito') || name.includes('debito') || name.includes('debit')) {
    return CreditCard
  }
  if (name.includes('crédito') || name.includes('credito') || name.includes('credit')) {
    return CreditCard
  }
  if (name.includes('vr') || name.includes('alelo')) {
    return Building2
  }
  if (name.includes('stone')) {
    return CreditCard
  }
  return CreditCard
}

export function PaymentButtonsGrid({
  methods,
  selectedMethods,
  onSelect,
  onRemove,
  className,
}: PaymentButtonsGridProps) {
  const isMethodSelected = (method: PaymentMethod) => {
    return selectedMethods.some(m => m.uuid === method.uuid)
  }

  return (
    <div className={cn("space-y-2", className)}>
      <h4 className="font-semibold text-sm">PAGAMENTO:</h4>
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
        {methods.map((method) => {
          const Icon = getPaymentIcon(method.name)
          const isSelected = isMethodSelected(method)
          
          return (
            <Button
              key={method.uuid}
              variant="outline"
              className={cn(
                "p-3 border-2 rounded-lg hover:border-primary hover:bg-primary/5 transition-all flex flex-col items-center gap-2 h-auto",
                isSelected && "border-primary bg-primary/10"
              )}
              onClick={() => {
                if (isSelected && onRemove) {
                  onRemove(method)
                } else {
                  onSelect(method)
                }
              }}
            >
              <Icon className="w-6 h-6" />
              <span className="text-sm font-medium">{method.name}</span>
            </Button>
          )
        })}
      </div>
      
      {/* Lista de pagamentos adicionados */}
      {selectedMethods.length > 0 && (
        <div className="space-y-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
          <h5 className="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
            Pagamentos adicionados:
          </h5>
          {selectedMethods.map((method) => (
            <div
              key={method.uuid}
              className="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700"
            >
              <div className="flex-1 min-w-0">
                <span className="font-medium text-sm">{method.name}</span>
                {/* Valor será exibido quando disponível via props ou contexto */}
              </div>
              {onRemove && (
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-6 w-6 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20 flex-shrink-0"
                  onClick={() => onRemove(method)}
                  title="Remover pagamento"
                >
                  <span className="text-lg">×</span>
                </Button>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

