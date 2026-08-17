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
import { Textarea } from "@/components/ui/textarea"
import { Undo2 } from "lucide-react"

interface RefundDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onConfirm: (amount: number, reason: string) => void
  orderTotal: number
  formatCurrency?: (value: number) => string
}

export function RefundDialog({
  open,
  onOpenChange,
  onConfirm,
  orderTotal,
  formatCurrency: formatCurrencyProp,
}: RefundDialogProps) {
  const [amount, setAmount] = useState<string>(String(orderTotal))
  const [reason, setReason] = useState("")

  const formatCurrencyFn = formatCurrencyProp || formatCurrency

  const handleConfirm = () => {
    const refundAmount = parseFloat(amount) || 0
    if (refundAmount > 0 && refundAmount <= orderTotal && reason.trim()) {
      onConfirm(refundAmount, reason.trim())
      setAmount(String(orderTotal))
      setReason("")
    }
  }

  const handleCancel = () => {
    setAmount(String(orderTotal))
    setReason("")
    onOpenChange(false)
  }

  const parsedAmount = parseFloat(amount) || 0
  const isValid = parsedAmount > 0 && parsedAmount <= orderTotal && reason.trim().length > 0

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Undo2 className="h-5 w-5" />
            Reembolsar Pedido
          </DialogTitle>
          <DialogDescription>
            Valor total do pedido: {formatCurrencyFn(orderTotal)}
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <Label htmlFor="refund-amount">Valor do Reembolso</Label>
            <Input
              id="refund-amount"
              type="number"
              step="0.01"
              min="0"
              max={orderTotal}
              value={amount}
              onChange={(e) => setAmount(e.target.value)}
              placeholder="0.00"
              className="text-lg"
            />
            <p className="text-xs text-muted-foreground">
              Valor máximo: {formatCurrencyFn(orderTotal)}
            </p>
          </div>
          <div className="space-y-2">
            <Label htmlFor="refund-reason">Motivo do Reembolso *</Label>
            <Textarea
              id="refund-reason"
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder="Descreva o motivo do reembolso..."
              className="min-h-[80px] resize-none"
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={handleCancel}>
            Cancelar
          </Button>
          <Button onClick={handleConfirm} disabled={!isValid} variant="destructive">
            Confirmar Reembolso
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

