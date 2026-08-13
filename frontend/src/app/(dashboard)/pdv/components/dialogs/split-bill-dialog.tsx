"use client"

import { useState } from "react"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Split } from "lucide-react"
import { cn } from "@/lib/utils"

interface SplitBillDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onConfirm: (items: any[], amounts: number[]) => void
  items: Array<{ id: string; name: string; price: number }>
  formatCurrency?: (value: number) => string
}

export function SplitBillDialog({
  open,
  onOpenChange,
  onConfirm,
  items,
  formatCurrency: formatCurrencyProp,
}: SplitBillDialogProps) {
  const [splitCount, setSplitCount] = useState(2)
  const [amounts, setAmounts] = useState<number[]>([])

  const formatCurrencyFn = formatCurrencyProp || formatCurrency

  const total = items.reduce((sum, item) => sum + item.price, 0)
  const splitAmount = total / splitCount

  const handleSplitCountChange = (count: number) => {
    if (count >= 2 && count <= 10) {
      setSplitCount(count)
      setAmounts(Array(count).fill(splitAmount))
    }
  }

  const handleAmountChange = (index: number, value: string) => {
    const newAmounts = [...amounts]
    newAmounts[index] = parseFloat(value) || 0
    setAmounts(newAmounts)
  }

  const handleConfirm = () => {
    const totalAmount = amounts.reduce((sum, amt) => sum + amt, 0)
    if (Math.abs(totalAmount - total) < 0.01) {
      onConfirm(items, amounts)
      setSplitCount(2)
      setAmounts([])
    }
  }

  const handleCancel = () => {
    setSplitCount(2)
    setAmounts([])
    onOpenChange(false)
  }

  const totalAmount = amounts.reduce((sum, amt) => sum + amt, 0)
  const difference = total - totalAmount
  const isValid = Math.abs(difference) < 0.01

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Split className="h-5 w-5" />
            Dividir Conta
          </DialogTitle>
          <DialogDescription>
            Valor total: {formatCurrencyFn(total)}
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <Label>Número de Divisões</Label>
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="icon"
                onClick={() => handleSplitCountChange(splitCount - 1)}
                disabled={splitCount <= 2}
              >
                -
              </Button>
              <Input
                type="number"
                min="2"
                max="10"
                value={splitCount}
                onChange={(e) => handleSplitCountChange(parseInt(e.target.value) || 2)}
                className="w-20 text-center"
              />
              <Button
                variant="outline"
                size="icon"
                onClick={() => handleSplitCountChange(splitCount + 1)}
                disabled={splitCount >= 10}
              >
                +
              </Button>
            </div>
          </div>

          <div className="space-y-2">
            <Label>Valores por Divisão</Label>
            <div className="space-y-2 max-h-[300px] overflow-y-auto">
              {Array.from({ length: splitCount }).map((_, index) => (
                <div key={index} className="flex items-center gap-2">
                  <Label className="w-20">Divisão {index + 1}</Label>
                  <Input
                    type="number"
                    step="0.01"
                    value={amounts[index]?.toFixed(2) || splitAmount.toFixed(2)}
                    onChange={(e) => handleAmountChange(index, e.target.value)}
                    className="flex-1"
                  />
                  <span className="text-sm text-muted-foreground w-24 text-right">
                    {formatCurrencyFn(amounts[index] || splitAmount)}
                  </span>
                </div>
              ))}
            </div>
          </div>

          <div className="pt-2 border-t">
            <div className="flex justify-between items-center">
              <span className="font-semibold">Total:</span>
              <span className={cn("font-bold", isValid ? "text-green-600" : "text-red-600")}>
                {formatCurrencyFn(totalAmount)}
              </span>
            </div>
            {!isValid && (
              <p className="text-xs text-red-600 mt-1">
                Diferença: {formatCurrencyFn(Math.abs(difference))}
              </p>
            )}
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={handleCancel}>
            Cancelar
          </Button>
          <Button onClick={handleConfirm} disabled={!isValid}>
            Confirmar Divisão
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
  }).format(value)
}

