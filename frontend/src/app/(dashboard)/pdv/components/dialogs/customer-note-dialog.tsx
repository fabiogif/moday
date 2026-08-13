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
import { MessageSquare } from "lucide-react"

interface CustomerNoteDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onSave: (note: string) => void
  initialNote?: string
}

export function CustomerNoteDialog({
  open,
  onOpenChange,
  onSave,
  initialNote = "",
}: CustomerNoteDialogProps) {
  const [note, setNote] = useState(initialNote)

  const handleSave = () => {
    onSave(note)
    setNote("")
  }

  const handleCancel = () => {
    setNote(initialNote)
    onOpenChange(false)
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <MessageSquare className="h-5 w-5" />
            Observação do Cliente
          </DialogTitle>
          <DialogDescription>
            Adicione uma observação que será visível para o cliente no pedido.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <Label htmlFor="customer-note">Observação</Label>
            <Textarea
              id="customer-note"
              value={note}
              onChange={(e) => setNote(e.target.value)}
              placeholder="Ex: Sem cebola, bem passado, etc."
              className="min-h-[100px] resize-none"
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={handleCancel}>
            Cancelar
          </Button>
          <Button onClick={handleSave}>Salvar</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

