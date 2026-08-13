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
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import { Loader2 } from "lucide-react"

interface PlanMigrationModalProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  planToMigrate: { id: number; name: string } | null
  onConfirm: (planId: number, notes?: string) => void
  loading: boolean
}

export function PlanMigrationModal({
  open,
  onOpenChange,
  planToMigrate,
  onConfirm,
  loading,
}: PlanMigrationModalProps) {
  const [notes, setNotes] = useState("")

  const handleConfirm = () => {
    if (planToMigrate) {
      onConfirm(planToMigrate.id, notes || undefined)
      setNotes("")
    }
  }

  const handleCancel = () => {
    setNotes("")
    onOpenChange(false)
  }

  if (!planToMigrate) {
    return null
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Confirmar Migração de Plano</DialogTitle>
          <DialogDescription>
            Você está prestes a migrar para o plano <strong>{planToMigrate.name}</strong>.
            Esta ação será registrada no histórico de migrações.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          <div>
            <Label htmlFor="notes">Observações (opcional)</Label>
            <Textarea
              id="notes"
              placeholder="Adicione uma observação sobre esta migração..."
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={3}
              className="mt-2"
            />
          </div>
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            onClick={handleCancel}
            disabled={loading}
          >
            Cancelar
          </Button>
          <Button
            onClick={handleConfirm}
            disabled={loading}
          >
            {loading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Migrando...
              </>
            ) : (
              "Confirmar Migração"
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

