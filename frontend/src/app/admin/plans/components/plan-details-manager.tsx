"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Plus, X } from "lucide-react"
import { PlanDetail } from "../page"

interface PlanDetailsManagerProps {
  details: PlanDetail[]
  onChange: (details: PlanDetail[]) => void
}

export function PlanDetailsManager({ details, onChange }: PlanDetailsManagerProps) {
  const [newDetailName, setNewDetailName] = useState("")

  const addDetail = () => {
    if (!newDetailName.trim()) return

    const newDetail: PlanDetail = {
      id: Date.now(), // ID temporário para frontend
      name: newDetailName.trim(),
      description: newDetailName.trim(),
      plan_id: 0, // Será preenchido ao salvar
    }

    onChange([...details, newDetail])
    setNewDetailName("")
  }

  const removeDetail = (index: number) => {
    const updated = details.filter((_, i) => i !== index)
    onChange(updated)
  }

  const updateDetail = (index: number, name: string) => {
    const updated = details.map((detail, i) => 
      i === index ? { ...detail, name, description: name } : detail
    )
    onChange(updated)
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <Label>Detalhes do Plano</Label>
          <p className="text-xs text-muted-foreground">
            Features que aparecerão na landing page
          </p>
        </div>
        <Badge variant="outline" className="h-6">
          {details.length}
        </Badge>
      </div>

      {/* Lista de detalhes existentes */}
      {details.length > 0 && (
        <div className="space-y-2">
          {details.map((detail, index) => (
            <Card key={detail.id || `detail-${index}`} className="p-3 bg-muted/30">
              <div className="flex items-center gap-2">
                <Input
                  value={detail.name}
                  onChange={(e) => updateDetail(index, e.target.value)}
                  placeholder="Nome da feature"
                  className="flex-1"
                />
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  onClick={() => removeDetail(index)}
                  className="cursor-pointer text-destructive hover:text-destructive shrink-0"
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>
            </Card>
          ))}
        </div>
      )}

      {details.length === 0 && (
        <div className="text-center py-8 border-2 border-dashed rounded-lg bg-muted/10">
          <p className="text-sm text-muted-foreground">
            Nenhum detalhe adicionado
          </p>
          <p className="text-xs text-muted-foreground mt-1">
            Adicione features como "Até 100 produtos", "Suporte por email", etc.
          </p>
        </div>
      )}

      {/* Adicionar novo detalhe */}
      <div className="flex gap-2">
        <Input
          value={newDetailName}
          onChange={(e) => setNewDetailName(e.target.value)}
          onKeyPress={(e) => {
            if (e.key === "Enter") {
              e.preventDefault()
              addDetail()
            }
          }}
          placeholder="Ex: Até 100 produtos cadastrados"
          className="flex-1"
        />
        <Button
          type="button"
          onClick={addDetail}
          disabled={!newDetailName.trim()}
          className="cursor-pointer"
          size="icon"
        >
          <Plus className="h-4 w-4" />
        </Button>
      </div>

      <p className="text-xs text-muted-foreground">
        Pressione Enter ou clique + para adicionar
      </p>
    </div>
  )
}

