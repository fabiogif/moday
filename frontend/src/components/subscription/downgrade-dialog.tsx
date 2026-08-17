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
import { Badge } from "@/components/ui/badge"
import { ArrowDown, Loader2 } from "lucide-react"
import { useSubscription } from "@/hooks/use-subscription"
import type { PlanWithDetails } from "@/types/plan"

interface DowngradeDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentPlan: PlanWithDetails | null
  targetPlan: PlanWithDetails
  nextBillingDate: string | null
  onSuccess: () => void
}

export function DowngradeDialog({
  open,
  onOpenChange,
  currentPlan,
  targetPlan,
  nextBillingDate,
  onSuccess,
}: DowngradeDialogProps) {
  const { scheduleDowngrade, loading, error, clearError } = useSubscription()

  async function handleConfirm() {
    clearError()
    const ok = await scheduleDowngrade(targetPlan.id)
    if (ok) {
      onSuccess()
      onOpenChange(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <ArrowDown className="h-5 w-5 text-amber-600" />
            Agendar downgrade de plano
          </DialogTitle>
          <DialogDescription>
            O downgrade será aplicado no início do próximo ciclo de cobrança.
            Você continuará com todos os recursos do plano atual até lá.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-2">
          {currentPlan && (
            <div className="flex items-center justify-between rounded-md border p-3">
              <div>
                <p className="text-sm text-muted-foreground">Plano atual</p>
                <p className="font-medium">{currentPlan.name}</p>
              </div>
              <Badge variant="outline">
                R$ {Number(currentPlan.price).toFixed(2)}/mês
              </Badge>
            </div>
          )}

          <div className="flex items-center justify-between rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950">
            <div>
              <p className="text-sm text-muted-foreground">Novo plano (a partir de {nextBillingDate ?? "próximo ciclo"})</p>
              <p className="font-medium text-amber-700 dark:text-amber-400">{targetPlan.name}</p>
            </div>
            <Badge className="bg-amber-600 text-white">
              R$ {Number(targetPlan.price).toFixed(2)}/mês
            </Badge>
          </div>

          {error && (
            <p className="text-sm text-destructive">{error}</p>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
            Cancelar
          </Button>
          <Button
            variant="outline"
            className="border-amber-600 text-amber-700 hover:bg-amber-50"
            onClick={handleConfirm}
            disabled={loading}
          >
            {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Confirmar downgrade
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
