"use client"

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import { PaymentSummary } from "./payment-summary"
import { type PaymentMethod } from "./payment-method-card"
import { Loader2, Printer, CheckCircle2, X } from "lucide-react"
import { cn } from "@/lib/utils"

export interface PaymentConfirmationItem {
  method: PaymentMethod
  amount: number
}

interface PaymentConfirmationDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  orderTotal: number
  payments: PaymentConfirmationItem[]
  formatCurrency: (value: number) => string
  onConfirm: () => void | Promise<void>
  onPrint?: () => void
  isLoading?: boolean
  orderId?: string | number
  className?: string
}

export function PaymentConfirmationDialog({
  open,
  onOpenChange,
  orderTotal,
  payments,
  formatCurrency,
  onConfirm,
  onPrint,
  isLoading = false,
  orderId,
  className,
}: PaymentConfirmationDialogProps) {
  const totalPaid = payments.reduce((sum, payment) => sum + payment.amount, 0)
  const change = totalPaid > orderTotal ? totalPaid - orderTotal : 0
  const isFullyPaid = totalPaid >= orderTotal

  const handleConfirm = async () => {
    if (!isFullyPaid) {
      return
    }
    await onConfirm()
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className={cn("sm:max-w-[500px]", className)}>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <CheckCircle2 className="h-5 w-5 text-primary" />
            Confirmar Pagamento
          </DialogTitle>
          <DialogDescription>
            {isFullyPaid
              ? "Deseja realmente finalizar o pagamento?"
              : "O pagamento não está completo. Adicione mais métodos de pagamento."}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          {orderId && (
            <div className="text-sm text-muted-foreground">
              Pedido #{orderId}
            </div>
          )}

          <PaymentSummary
            items={payments}
            orderTotal={orderTotal}
            formatCurrency={formatCurrency}
          />

          {!isFullyPaid && (
            <div className="p-3 rounded-lg bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800">
              <p className="text-sm text-amber-900 dark:text-amber-100">
                <strong>Atenção:</strong> O valor pago é menor que o total do pedido.
                Adicione mais métodos de pagamento para completar.
              </p>
            </div>
          )}

          {change > 0 && (
            <div className="p-3 rounded-lg bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800">
              <p className="text-sm text-green-900 dark:text-green-100">
                <strong>Troco a ser devolvido:</strong> {formatCurrency(change)}
              </p>
            </div>
          )}
        </div>

        <Separator />

        <DialogFooter className="flex-col sm:flex-row gap-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={isLoading}
            className="w-full sm:w-auto"
          >
            <X className="mr-2 h-4 w-4" />
            Cancelar
          </Button>
          {onPrint && (
            <Button
              type="button"
              variant="outline"
              onClick={onPrint}
              disabled={isLoading}
              className="w-full sm:w-auto"
            >
              <Printer className="mr-2 h-4 w-4" />
              Imprimir
            </Button>
          )}
          <Button
            type="button"
            onClick={handleConfirm}
            disabled={isLoading || !isFullyPaid}
            className="w-full sm:w-auto"
          >
            {isLoading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Processando...
              </>
            ) : (
              <>
                <CheckCircle2 className="mr-2 h-4 w-4" />
                Confirmar Pagamento
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

