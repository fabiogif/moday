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
import { AlertTriangle, Loader2 } from "lucide-react"
import { useSubscription } from "@/hooks/use-subscription"

interface CancelDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentPeriodEnd: string | null
  onSuccess: () => void
}

export function CancelDialog({
  open,
  onOpenChange,
  currentPeriodEnd,
  onSuccess,
}: CancelDialogProps) {
  const { cancelSubscription, loading, error, clearError } = useSubscription()

  async function handleConfirm() {
    clearError()
    const ok = await cancelSubscription()
    if (ok) {
      onSuccess()
      onOpenChange(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-destructive">
            <AlertTriangle className="h-5 w-5" />
            Cancelar assinatura
          </DialogTitle>
          <DialogDescription>
            Tem certeza que deseja cancelar sua assinatura?
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-3 py-2 text-sm text-muted-foreground">
          <p>
            Ao cancelar, <strong className="text-foreground">você manterá acesso</strong> a todos os recursos
            {currentPeriodEnd ? ` até ${currentPeriodEnd}` : " até o fim do ciclo atual"}.
          </p>
          <p>
            Após essa data, o acesso será encerrado e seus dados serão preservados por 90 dias.
          </p>
          <p>
            Você pode reativar sua assinatura a qualquer momento antes do encerramento do acesso.
          </p>

          {error && (
            <p className="text-destructive">{error}</p>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
            Manter assinatura
          </Button>
          <Button variant="destructive" onClick={handleConfirm} disabled={loading}>
            {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Confirmar cancelamento
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
