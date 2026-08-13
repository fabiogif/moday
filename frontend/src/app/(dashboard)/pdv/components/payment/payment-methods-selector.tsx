"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"
import { PaymentMethodCard, type PaymentMethod } from "./payment-method-card"
import { Plus } from "lucide-react"

interface PaymentMethodsSelectorProps {
  methods: PaymentMethod[]
  selectedMethods: PaymentMethod[]
  onSelect: (method: PaymentMethod) => void
  onRemove?: (method: PaymentMethod) => void
  allowMultiple?: boolean
  maxMethods?: number
  className?: string
}

export function PaymentMethodsSelector({
  methods,
  selectedMethods,
  onSelect,
  onRemove,
  allowMultiple = false,
  maxMethods,
  className,
}: PaymentMethodsSelectorProps) {
  const [showAll, setShowAll] = useState(methods.length <= 4)

  const displayMethods = showAll ? methods : methods.slice(0, 4)
  const canAddMore = allowMultiple && (!maxMethods || selectedMethods.length < maxMethods)

  const isMethodSelected = (method: PaymentMethod) => {
    return selectedMethods.some(m => m.uuid === method.uuid)
  }

  const handleMethodClick = (method: PaymentMethod) => {
    if (allowMultiple) {
      if (isMethodSelected(method)) {
        onRemove?.(method)
      } else {
        if (!maxMethods || selectedMethods.length < maxMethods) {
          onSelect(method)
        }
      }
    } else {
      onSelect(method)
    }
  }

  return (
    <div className={cn("space-y-2", className)}>
      <div className="flex items-center justify-between">
        <p className="text-xs font-medium">Forma de pagamento</p>
        {methods.length > 4 && (
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setShowAll(!showAll)}
            className="h-7 text-[10px] px-2"
          >
            {showAll ? "Ocultar" : "Mostrar todas"}
          </Button>
        )}
      </div>
      <div className={cn(
        "grid gap-2",
        methods.length <= 4 ? "grid-cols-2" : "grid-cols-2 sm:grid-cols-3"
      )}>
        {displayMethods.map((method) => (
          <PaymentMethodCard
            key={method.uuid}
            method={method}
            isSelected={isMethodSelected(method)}
            isMultiple={allowMultiple}
            onSelect={handleMethodClick}
          />
        ))}
      </div>
      {allowMultiple && selectedMethods.length > 0 && (
        <div className="pt-2 border-t space-y-2">
          <p className="text-xs font-medium text-muted-foreground">
            Métodos selecionados ({selectedMethods.length}):
          </p>
          <div className="flex flex-wrap gap-2">
            {selectedMethods.map((method) => (
              <div
                key={method.uuid}
                className="flex items-center gap-1 px-2 py-1 rounded-md bg-primary/10 text-primary text-xs"
              >
                <span>{method.name}</span>
                {onRemove && (
                  <button
                    type="button"
                    onClick={() => onRemove(method)}
                    className="ml-1 hover:text-destructive"
                  >
                    ×
                  </button>
                )}
              </div>
            ))}
            {canAddMore && (
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => {
                  // Abrir seletor adicional se necessário
                }}
                className="h-7 text-xs"
              >
                <Plus className="h-3 w-3 mr-1" />
                Adicionar
              </Button>
            )}
          </div>
        </div>
      )}
    </div>
  )
}

