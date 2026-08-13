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
import { Users } from "lucide-react"

interface GuestsDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onConfirm: (count: number) => void
  currentGuests: number
}

export function GuestsDialog({
  open,
  onOpenChange,
  onConfirm,
  currentGuests,
}: GuestsDialogProps) {
  const [guests, setGuests] = useState<string>(String(currentGuests))

  const handleConfirm = () => {
    const count = parseInt(guests) || 1
    if (count > 0 && count <= 50) {
      onConfirm(count)
      setGuests(String(currentGuests))
    }
  }

  const handleCancel = () => {
    setGuests(String(currentGuests))
    onOpenChange(false)
  }

  const count = parseInt(guests) || 1
  const isValid = count > 0 && count <= 50

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[400px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Users className="h-5 w-5" />
            Quantidade de Clientes
          </DialogTitle>
          <DialogDescription>
            Defina a quantidade de clientes para este atendimento.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <Label htmlFor="guests-count">Número de Clientes</Label>
            <Input
              id="guests-count"
              type="number"
              min="1"
              max="50"
              value={guests}
              onChange={(e) => setGuests(e.target.value)}
              placeholder="1"
              className="text-lg"
            />
            <p className="text-xs text-muted-foreground">
              Valor atual: {currentGuests} cliente{currentGuests !== 1 ? "s" : ""}
            </p>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={handleCancel}>
            Cancelar
          </Button>
          <Button onClick={handleConfirm} disabled={!isValid}>
            Confirmar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

