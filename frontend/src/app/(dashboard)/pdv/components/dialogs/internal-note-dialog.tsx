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
import { FileText } from "lucide-react"

interface InternalNoteDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onSave: (note: string) => void
  initialNote?: string
}

export function InternalNoteDialog({
  open,
  onOpenChange,
  onSave,
  initialNote = "",
}: InternalNoteDialogProps) {
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
            <FileText className="h-5 w-5" />
            Observação Interna
          </DialogTitle>
          <DialogDescription>
            Adicione uma observação interna que será visível apenas para a equipe.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <Label htmlFor="internal-note">Observação Interna</Label>
            <Textarea
              id="internal-note"
              value={note}
              onChange={(e) => setNote(e.target.value)}
              placeholder="Ex: Cliente VIP, urgente, etc."
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

