"use client"

import { useState, useEffect } from "react"
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Textarea } from "@/components/ui/textarea"
import { Plan, PlanFormValues, PlanDetail } from "../page"
import { PlanDetailsManager } from "./plan-details-manager"
import { PLAN_MODULE_GROUPS, type PlanModuleOptionKey } from "@/lib/plan-modules"

interface PlanFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onSubmit: (data: PlanFormValues) => void
  editPlan?: Plan | null
  loading?: boolean
}

const DEFAULT_FORM: PlanFormValues = {
  name: "",
  url: "",
  price: 0,
  description: "",
  is_active: true,
  max_users: null,
  max_products: null,
  max_orders_per_month: null,
  has_marketing: false,
  has_order_completion_email: false,
  has_reports: false,
}

export function PlanFormDialog({
  open,
  onOpenChange,
  onSubmit,
  editPlan,
  loading = false
}: PlanFormDialogProps) {
  const [formData, setFormData] = useState<PlanFormValues>(DEFAULT_FORM)
  const [details, setDetails] = useState<PlanDetail[]>([])

  useEffect(() => {
    if (editPlan) {
      setFormData({
        name: editPlan.name,
        url: editPlan.url,
        price: Number(editPlan.price),
        description: editPlan.description || "",
        is_active: editPlan.is_active,
        max_users: editPlan.max_users,
        max_products: editPlan.max_products,
        max_orders_per_month: editPlan.max_orders_per_month,
        has_marketing: editPlan.has_marketing || false,
        has_order_completion_email: editPlan.has_order_completion_email || false,
        has_reports: editPlan.has_reports || false,
      })
      setDetails(editPlan.details || [])
    } else {
      setFormData(DEFAULT_FORM)
      setDetails([])
    }
  }, [editPlan, open])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const dataToSubmit = {
      ...formData,
      details: details.map(d => ({ name: d.name }))
    }
    onSubmit(dataToSubmit as PlanFormValues)
  }

  const generateUrl = (name: string) => {
    return name
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9\s-]/g, "")
      .replace(/\s+/g, "-")
      .replace(/-+/g, "-")
      .trim()
  }

  const handleNameChange = (name: string) => {
    setFormData({
      ...formData,
      name,
      url: generateUrl(name)
    })
  }

  const handleModuleOptionChange = (key: PlanModuleOptionKey, checked: boolean) => {
    setFormData({ ...formData, [key]: checked })
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {editPlan ? "Editar Plano" : "Novo Plano"}
          </DialogTitle>
          <DialogDescription>
            {editPlan
              ? "Atualize as informações do plano"
              : "Preencha os dados para criar um novo plano"}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="space-y-4">
            <h3 className="font-semibold text-sm">Informações Básicas</h3>
            
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="name">
                  Nome do Plano <span className="text-destructive">*</span>
                </Label>
                <Input
                  id="name"
                  value={formData.name}
                  onChange={(e) => handleNameChange(e.target.value)}
                  placeholder="Ex: Básico, Premium, Enterprise"
                  required
                />
              </div>

              <div>
                <Label htmlFor="url">
                  URL (slug) <span className="text-destructive">*</span>
                </Label>
                <Input
                  id="url"
                  value={formData.url}
                  onChange={(e) => setFormData({ ...formData, url: e.target.value })}
                  placeholder="Ex: basico, premium"
                  required
                />
              </div>
            </div>

            <div>
              <Label htmlFor="description">Descrição</Label>
              <Textarea
                id="description"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Breve descrição do plano"
                rows={2}
              />
            </div>

            <div>
              <Label htmlFor="price">
                Preço (R$) <span className="text-destructive">*</span>
              </Label>
              <Input
                id="price"
                type="number"
                step="0.01"
                min="0"
                value={formData.price}
                onChange={(e) => setFormData({ ...formData, price: Number(e.target.value) })}
                placeholder="0.00"
                required
              />
            </div>
          </div>

          <div className="space-y-4 border-t pt-4">
            <h3 className="font-semibold text-sm">Limites do Plano</h3>
            <p className="text-xs text-muted-foreground">
              Deixe em branco ou defina valores altos (999999) para ilimitado
            </p>

            <div className="grid grid-cols-3 gap-4">
              <div>
                <Label htmlFor="max_users">Máximo de Usuários</Label>
                <Input
                  id="max_users"
                  type="number"
                  min="0"
                  value={formData.max_users === null ? "" : formData.max_users}
                  onChange={(e) => setFormData({ 
                    ...formData, 
                    max_users: e.target.value === "" ? null : Number(e.target.value)
                  })}
                  placeholder="Ex: 5 ou 999999"
                />
              </div>

              <div>
                <Label htmlFor="max_products">Máximo de Produtos</Label>
                <Input
                  id="max_products"
                  type="number"
                  min="0"
                  value={formData.max_products === null ? "" : formData.max_products}
                  onChange={(e) => setFormData({ 
                    ...formData, 
                    max_products: e.target.value === "" ? null : Number(e.target.value)
                  })}
                  placeholder="Ex: 100 ou 999999"
                />
              </div>

              <div>
                <Label htmlFor="max_orders_per_month">Pedidos/mês</Label>
                <Input
                  id="max_orders_per_month"
                  type="number"
                  min="0"
                  value={formData.max_orders_per_month === null ? "" : formData.max_orders_per_month}
                  onChange={(e) => setFormData({ 
                    ...formData, 
                    max_orders_per_month: e.target.value === "" ? null : Number(e.target.value)
                  })}
                  placeholder="Ex: 100 ou vazio"
                />
              </div>
            </div>
          </div>

          <div className="space-y-4 border-t pt-4">
            <div>
              <h3 className="font-semibold text-sm">Módulos e Opções</h3>
              <p className="text-xs text-muted-foreground mt-1">
                Ative as funcionalidades incluídas em cada plano
              </p>
            </div>

            <div className="space-y-4">
              {PLAN_MODULE_GROUPS.map((module) => (
                <div key={module.id} className="rounded-lg border p-4 space-y-3">
                  <div>
                    <h4 className="font-medium text-sm">{module.label}</h4>
                    {module.description && (
                      <p className="text-xs text-muted-foreground">{module.description}</p>
                    )}
                  </div>

                  <div className="space-y-3">
                    {module.options.map((option) => (
                      <div key={option.key} className="flex items-center justify-between gap-4">
                        <div className="space-y-0.5">
                          <Label htmlFor={option.key}>{option.label}</Label>
                          <p className="text-xs text-muted-foreground">{option.description}</p>
                        </div>
                        <Switch
                          id={option.key}
                          checked={formData[option.key]}
                          onCheckedChange={(checked) => handleModuleOptionChange(option.key, checked)}
                        />
                      </div>
                    ))}
                  </div>
                </div>
              ))}

              <div className="flex items-center justify-between rounded-lg border p-4">
                <div className="space-y-0.5">
                  <Label htmlFor="is_active">Plano Ativo</Label>
                  <p className="text-xs text-muted-foreground">
                    Planos inativos não aparecem na landing page
                  </p>
                </div>
                <Switch
                  id="is_active"
                  checked={formData.is_active}
                  onCheckedChange={(checked) => setFormData({ ...formData, is_active: checked })}
                />
              </div>
            </div>
          </div>

          <div className="space-y-4 border-t pt-4">
            <PlanDetailsManager details={details} onChange={setDetails} />
          </div>

          <div className="flex justify-end gap-3 border-t pt-4">
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={loading}
              className="cursor-pointer"
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              disabled={loading}
              className="cursor-pointer"
            >
              {loading ? "Salvando..." : editPlan ? "Atualizar Plano" : "Criar Plano"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  )
}
