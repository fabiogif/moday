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
import { Badge } from "@/components/ui/badge"
import { ArrowUp, Loader2 } from "lucide-react"
import { useSubscription } from "@/hooks/use-subscription"
import type { PlanWithDetails } from "@/types/plan"

interface UpgradeDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentPlan: PlanWithDetails | null
  targetPlan: PlanWithDetails
  onSuccess: () => void
}

export function UpgradeDialog({
  open,
  onOpenChange,
  currentPlan,
  targetPlan,
  onSuccess,
}: UpgradeDialogProps) {
  const { upgrade, loading, error, clearError } = useSubscription()

  async function handleConfirm() {
    clearError()
    const ok = await upgrade(targetPlan.id)
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
            <ArrowUp className="h-5 w-5 text-green-600" />
            Confirmar upgrade de plano
          </DialogTitle>
          <DialogDescription>
            O upgrade é imediato e o valor é ajustado proporcionalmente no próximo ciclo.
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

          <div className="flex items-center justify-between rounded-md border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950">
            <div>
              <p className="text-sm text-muted-foreground">Novo plano</p>
              <p className="font-medium text-green-700 dark:text-green-400">{targetPlan.name}</p>
            </div>
            <Badge className="bg-green-600 text-white">
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
          <Button onClick={handleConfirm} disabled={loading}>
            {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Confirmar upgrade
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
