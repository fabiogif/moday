"use client"

import { useState, useMemo } from "react"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Utensils, Truck, Store, Coffee, Check } from "lucide-react"
import { cn } from "@/lib/utils"

export type OrderType = string

interface ServiceTypeOption {
  value: string
  label: string
  icon: React.ReactNode
}

interface OrderTypeSelectorProps {
  selectedType: OrderType
  onTypeChange: (type: OrderType) => void
  className?: string
  disabled?: boolean
  serviceTypes?: any[]
  loading?: boolean
}

// Mapeamento de ícones por slug/identify
const iconMap: Record<string, React.ReactNode> = {
  table: <Utensils className="h-6 w-6" />,
  mesa: <Utensils className="h-6 w-6" />,
  counter: <Coffee className="h-6 w-6" />,
  balcao: <Coffee className="h-6 w-6" />,
  delivery: <Truck className="h-6 w-6" />,
  pickup: <Store className="h-6 w-6" />,
  retirada: <Store className="h-6 w-6" />,
}

function getIcon(slug?: string | null, identify?: string | null): React.ReactNode {
  const key = (slug || identify || "").toLowerCase()
  return iconMap[key] || <Store className="h-4 w-4" />
}

export function OrderTypeSelector({
  selectedType,
  onTypeChange,
  className,
  disabled = false,
  serviceTypes = [],
  loading = false,
}: OrderTypeSelectorProps) {
  const normalizedTypes: ServiceTypeOption[] = useMemo(() => {
    if (!serviceTypes || serviceTypes.length === 0) return []

    return serviceTypes
      .filter((st) => st && st.is_active !== false)
      .sort((a, b) => (a.order_position || 0) - (b.order_position || 0))
      .map((st) => ({
        value: (st.identify || st.slug || "").toLowerCase(),
        label: st.name,
        icon: getIcon(st.slug, st.identify),
      }))
      .filter((option) => option.value)
  }, [serviceTypes])

  const defaultTypes: ServiceTypeOption[] = [
    { value: "table", label: "Mesa", icon: <Utensils className="h-6 w-6" /> },
    { value: "counter", label: "Balcão", icon: <Coffee className="h-6 w-6" /> },
    { value: "delivery", label: "Delivery", icon: <Truck className="h-6 w-6" /> },
    { value: "pickup", label: "Retirada", icon: <Store className="h-6 w-6" /> },
  ]

  const displayTypes = normalizedTypes.length > 0 ? normalizedTypes : defaultTypes
  const [isOpen, setIsOpen] = useState(false)

  // Encontrar o tipo selecionado para exibir no botão
  const selectedTypeData = displayTypes.find(
    (type) => type.value === selectedType?.toLowerCase()
  )

  const handleTypeSelect = (typeValue: string) => {
    onTypeChange(typeValue)
    setIsOpen(false)
  }

  if (loading) {
    return (
      <div className={cn("space-y-2", className)}>
        <p className="text-sm font-semibold mb-2">Tipo de Atendimento</p>
        <Button
          variant="outline"
          size="lg"
          disabled
          className="w-full h-16 justify-start gap-3"
        >
          <div className="h-6 w-6 rounded bg-muted animate-pulse" />
          <span className="text-sm font-medium">Carregando...</span>
        </Button>
      </div>
    )
  }

  return (
    <div className={cn("space-y-2", className)}>
      <p className="text-sm font-semibold mb-2">Tipo de Atendimento</p>
      <Button
        variant={selectedTypeData ? "default" : "outline"}
        size="lg"
        onClick={() => !disabled && setIsOpen(true)}
        disabled={disabled}
        className={cn(
          "w-full h-16 justify-start gap-3",
          selectedTypeData && "bg-primary text-primary-foreground"
        )}
      >
        {selectedTypeData ? (
          <>
            <div className="scale-110">{selectedTypeData.icon}</div>
            <span className="text-sm font-semibold">{selectedTypeData.label}</span>
          </>
        ) : (
          <>
            <Store className="h-6 w-6" />
            <span className="text-sm font-medium">Selecione o tipo de atendimento</span>
          </>
        )}
      </Button>

      <Dialog open={isOpen} onOpenChange={setIsOpen}>
        <DialogContent className="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Utensils className="h-5 w-5" />
              Selecionar Tipo de Atendimento
            </DialogTitle>
            <DialogDescription>
              Escolha o tipo de atendimento para este pedido
            </DialogDescription>
          </DialogHeader>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 py-4">
            {displayTypes.map((type) => {
              const isSelected = selectedType?.toLowerCase() === type.value
              return (
                <Button
                  key={type.value}
                  variant={isSelected ? "default" : "outline"}
                  size="lg"
                  onClick={() => handleTypeSelect(type.value)}
                  disabled={disabled}
                  className={cn(
                    "h-24 flex-col gap-2 py-3 px-4 relative",
                    isSelected && "bg-primary text-primary-foreground shadow-md ring-2 ring-primary ring-offset-2"
                  )}
                >
                  {isSelected && (
                    <div className="absolute top-2 right-2">
                      <Check className="h-5 w-5" />
                    </div>
                  )}
                  <div className="scale-125">{type.icon}</div>
                  <span className="text-sm font-semibold leading-tight">{type.label}</span>
                  {isSelected && (
                    <span className="text-xs opacity-90 mt-1">Selecionado</span>
                  )}
                </Button>
              )
            })}
          </div>
        </DialogContent>
      </Dialog>
    </div>
  )
}
