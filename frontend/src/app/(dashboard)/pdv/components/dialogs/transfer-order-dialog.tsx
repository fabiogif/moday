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
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { ArrowRightLeft } from "lucide-react"
import { Badge } from "@/components/ui/badge"

interface Table {
  uuid?: string
  identify?: string
  name: string
  isOccupied?: boolean
}

interface TransferOrderDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onConfirm: (targetTable: string) => void
  currentTable: string | null
  tables: Table[]
}

export function TransferOrderDialog({
  open,
  onOpenChange,
  onConfirm,
  currentTable,
  tables,
}: TransferOrderDialogProps) {
  const [selectedTable, setSelectedTable] = useState<string>("")

  const handleConfirm = () => {
    if (selectedTable && selectedTable !== currentTable) {
      onConfirm(selectedTable)
      setSelectedTable("")
    }
  }

  const handleCancel = () => {
    setSelectedTable("")
    onOpenChange(false)
  }

  const availableTables = tables.filter((table) => {
    const key = table.uuid || table.identify || table.name
    return key !== currentTable
  })

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <ArrowRightLeft className="h-5 w-5" />
            Transferir Pedido
          </DialogTitle>
          <DialogDescription>
            {currentTable
              ? `Transferir pedido da mesa ${currentTable} para outra mesa.`
              : "Selecione a mesa de destino para transferir o pedido."}
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <Label>Mesa de Destino</Label>
            <Select value={selectedTable} onValueChange={setSelectedTable}>
              <SelectTrigger>
                <SelectValue placeholder="Selecione uma mesa..." />
              </SelectTrigger>
              <SelectContent className="z-[100]">
                {availableTables.length > 0 ? (
                  availableTables.map((table) => {
                    const key = table.uuid || table.identify || table.name
                    return (
                      <SelectItem key={key} value={key}>
                        <div className="flex items-center justify-between w-full">
                          <span>Mesa {table.name}</span>
                          {table.isOccupied && (
                            <Badge variant="destructive" className="ml-2 text-xs">
                              Ocupada
                            </Badge>
                          )}
                        </div>
                      </SelectItem>
                    )
                  })
                ) : (
                  <SelectItem value="no-tables" disabled>
                    Nenhuma mesa disponível
                  </SelectItem>
                )}
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={handleCancel}>
            Cancelar
          </Button>
          <Button
            onClick={handleConfirm}
            disabled={!selectedTable || selectedTable === currentTable}
          >
            Transferir
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

