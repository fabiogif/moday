"use client"

import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"
import {
  Smartphone,
  CreditCard,
  Banknote,
  Radio,
  Building2,
  CheckCircle2,
} from "lucide-react"

export interface PaymentMethod {
  uuid: string
  name: string
  description?: string | null
}

interface PaymentMethodCardProps {
  method: PaymentMethod
  isSelected: boolean
  isMultiple?: boolean
  onSelect: (method: PaymentMethod) => void
  className?: string
}

export function PaymentMethodCard({
  method,
  isSelected,
  isMultiple = false,
  onSelect,
  className,
}: PaymentMethodCardProps) {
  const getPaymentIcon = (name: string) => {
    const lowerName = name.toLowerCase()
    if (lowerName.includes('pix')) {
      return <Smartphone className="h-6 w-6" />
    }
    if (lowerName.includes('cartão') || lowerName.includes('card') || lowerName.includes('credito') || lowerName.includes('debito')) {
      return <CreditCard className="h-6 w-6" />
    }
    if (lowerName.includes('dinheiro') || lowerName.includes('money') || lowerName.includes('cash')) {
      return <Banknote className="h-6 w-6" />
    }
    if (lowerName.includes('contactless') || lowerName.includes('nfc') || lowerName.includes('aproximação')) {
      return <Radio className="h-6 w-6" />
    }
    if (lowerName.includes('transferência') || lowerName.includes('transfer') || lowerName.includes('ted') || lowerName.includes('doc')) {
      return <Building2 className="h-6 w-6" />
    }
    return <CreditCard className="h-6 w-6" />
  }

  return (
    <Button
      type="button"
      data-testid={`payment-button-${method.uuid}`}
      onClick={() => onSelect(method)}
      className={cn(
        "h-16 rounded-lg flex-col gap-1 relative",
        isSelected
          ? "bg-primary text-primary-foreground shadow-lg border-2 border-primary"
          : "bg-muted text-foreground hover:bg-primary/10 border-2 border-transparent",
        className
      )}
    >
      <div className={cn(
        "transition-colors",
        isSelected ? "text-primary-foreground" : "text-primary"
      )}>
        {getPaymentIcon(method.name)}
      </div>
      <span className="font-semibold text-xs">{method.name}</span>
      {isSelected && (
        <CheckCircle2 className="h-3.5 w-3.5 absolute top-1.5 right-1.5" />
      )}
    </Button>
  )
}

