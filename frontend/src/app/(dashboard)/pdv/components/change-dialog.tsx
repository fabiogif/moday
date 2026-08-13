"use client"

import { useState, useEffect } from "react"
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
import { Banknote, X } from "lucide-react"

interface ChangeDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  orderTotal: number
  onConfirm: (needsChange: boolean, receivedAmount?: number) => void
}

export function ChangeDialog({
  open,
  onOpenChange,
  orderTotal,
  onConfirm,
}: ChangeDialogProps) {
  const [needsChange, setNeedsChange] = useState(false)
  const [receivedAmount, setReceivedAmount] = useState("")
  const [error, setError] = useState("")

  useEffect(() => {
    if (open) {
      setNeedsChange(false)
      setReceivedAmount("")
      setError("")
    }
  }, [open])

  const handleConfirm = () => {
    if (needsChange) {
      const amount = parseFloat(receivedAmount.replace(/[^\d,.-]/g, "").replace(",", "."))
      if (!amount || amount <= 0) {
        setError("Informe um valor válido")
        return
      }
      if (amount < orderTotal) {
        setError(`O valor recebido deve ser maior ou igual ao total (${orderTotal.toLocaleString("pt-BR", { style: "currency", currency: "BRL" })})`)
        return
      }
      onConfirm(true, amount)
    } else {
      onConfirm(false)
    }
    onOpenChange(false)
  }

  const handleCancel = () => {
    onOpenChange(false)
    setNeedsChange(false)
    setReceivedAmount("")
    setError("")
  }

  const formatCurrency = (value: string) => {
    // Remove tudo exceto números
    const numbers = value.replace(/\D/g, "")
    if (!numbers) return ""
    
    // Converte para número e formata
    const amount = parseFloat(numbers) / 100
    return amount.toLocaleString("pt-BR", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  }

  const handleAmountChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const formatted = formatCurrency(e.target.value)
    setReceivedAmount(formatted)
    setError("")
  }

  const changeAmount = needsChange && receivedAmount
    ? parseFloat(receivedAmount.replace(/\./g, "").replace(",", ".")) - orderTotal
    : 0

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <div className="flex items-center gap-2">
            <Banknote className="h-5 w-5 text-primary" />
            <DialogTitle>Necessita de troco?</DialogTitle>
          </div>
          <DialogDescription>
            O total do pedido é{" "}
            <span className="font-semibold text-foreground">
              {orderTotal.toLocaleString("pt-BR", {
                style: "currency",
                currency: "BRL",
              })}
            </span>
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          <div className="flex gap-3">
            <Button
              type="button"
              variant={needsChange ? "outline" : "default"}
              className="flex-1"
              onClick={() => {
                setNeedsChange(false)
                setReceivedAmount("")
                setError("")
                // Confirmar que não precisa de troco e fechar o modal
                onConfirm(false)
                onOpenChange(false)
              }}
            >
              Não
            </Button>
            <Button
              type="button"
              variant={needsChange ? "default" : "outline"}
              className="flex-1"
              onClick={() => setNeedsChange(true)}
            >
              Sim
            </Button>
          </div>

          {needsChange && (
            <div className="space-y-2 animate-in slide-in-from-top-2">
              <Label htmlFor="received-amount">Valor recebido</Label>
              <Input
                id="received-amount"
                type="text"
                placeholder="0,00"
                value={receivedAmount}
                onChange={handleAmountChange}
                className={error ? "border-red-500" : ""}
                autoFocus
              />
              {error && (
                <p className="text-sm text-red-500">{error}</p>
              )}
              {receivedAmount && !error && changeAmount > 0 && (
                <div className="rounded-lg bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 p-3">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-green-900 dark:text-green-100">
                      Troco:
                    </span>
                    <span className="text-lg font-bold text-green-700 dark:text-green-300">
                      {changeAmount.toLocaleString("pt-BR", {
                        style: "currency",
                        currency: "BRL",
                      })}
                    </span>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={handleCancel}>
            <X className="mr-2 h-4 w-4" />
            Cancelar
          </Button>
          <Button type="button" onClick={handleConfirm}>
            Confirmar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

