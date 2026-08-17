'use client'

import { useEffect, useMemo, useState } from 'react'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import adminApi from '@/lib/admin-api-client'
import type { Plan } from '@/app/admin/plans/page'

interface ChangeTenantPlanDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  tenant: {
    id: number
    name: string
    plan_id: number | null
    subscription_plan: string | null
  }
  onSuccess?: () => void
}

function formatPlanPrice(price: string | number) {
  const value = typeof price === 'string' ? parseFloat(price) : price
  if (!value || Number.isNaN(value)) return 'Grátis'
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(value)
}

export function ChangeTenantPlanDialog({
  open,
  onOpenChange,
  tenant,
  onSuccess,
}: ChangeTenantPlanDialogProps) {
  const [plans, setPlans] = useState<Plan[]>([])
  const [isLoadingPlans, setIsLoadingPlans] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [selectedPlanId, setSelectedPlanId] = useState<string>('')
  const [reason, setReason] = useState('')
  const [notifyTenant, setNotifyTenant] = useState(false)

  const activePlans = useMemo(
    () => plans.filter((plan) => plan.is_active),
    [plans]
  )

  const selectedPlan = activePlans.find((plan) => String(plan.id) === selectedPlanId)

  useEffect(() => {
    if (!open) return

    setSelectedPlanId(tenant.plan_id ? String(tenant.plan_id) : '')
    setReason('')
    setNotifyTenant(false)

    const loadPlans = async () => {
      try {
        setIsLoadingPlans(true)
        const response = await adminApi.getPlans({ per_page: 100 })
        const data = Array.isArray(response.data) ? response.data : []
        setPlans(data as Plan[])
      } catch {
        toast.error('Erro ao carregar planos disponíveis')
      } finally {
        setIsLoadingPlans(false)
      }
    }

    void loadPlans()
  }, [open, tenant.plan_id])

  const handleSubmit = async () => {
    if (!selectedPlanId) {
      toast.error('Selecione um plano')
      return
    }

    if (tenant.plan_id && Number(selectedPlanId) === tenant.plan_id) {
      toast.error('A empresa já está neste plano')
      return
    }

    try {
      setIsSubmitting(true)
      await adminApi.migrateTenantPlan({
        tenant_id: tenant.id,
        plan_id: Number(selectedPlanId),
        reason: reason.trim() || undefined,
        notify_tenant: notifyTenant,
      })
      toast.success('Plano alterado com sucesso!')
      onOpenChange(false)
      onSuccess?.()
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Erro ao alterar plano'
      toast.error(message)
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Alterar plano da empresa</DialogTitle>
          <DialogDescription>
            Migre <strong>{tenant.name}</strong> para outro plano. A alteração atualiza
            limites, MRR e registra histórico de migração.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-2">
          <div className="rounded-lg border bg-muted/40 p-3 text-sm">
            <p className="text-muted-foreground">Plano atual</p>
            <p className="font-medium capitalize">
              {tenant.subscription_plan || 'Sem plano definido'}
            </p>
          </div>

          <div className="space-y-2">
            <Label htmlFor="new-plan">Novo plano</Label>
            <Select
              value={selectedPlanId}
              onValueChange={setSelectedPlanId}
              disabled={isLoadingPlans || isSubmitting}
            >
              <SelectTrigger id="new-plan">
                <SelectValue
                  placeholder={isLoadingPlans ? 'Carregando planos...' : 'Selecione o plano'}
                />
              </SelectTrigger>
              <SelectContent>
                {activePlans.map((plan) => (
                  <SelectItem key={plan.id} value={String(plan.id)}>
                    {plan.name} — {formatPlanPrice(plan.price)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {selectedPlan ? (
              <p className="text-xs text-muted-foreground">
                {selectedPlan.description || 'Plano ativo disponível para migração.'}
              </p>
            ) : null}
          </div>

          <div className="space-y-2">
            <Label htmlFor="plan-reason">Motivo (opcional)</Label>
            <Textarea
              id="plan-reason"
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder="Ex.: upgrade solicitado pelo cliente, ajuste comercial..."
              rows={3}
              disabled={isSubmitting}
            />
          </div>

          <div className="flex items-start gap-2">
            <Checkbox
              id="notify-tenant"
              checked={notifyTenant}
              onCheckedChange={(checked) => setNotifyTenant(checked === true)}
              disabled={isSubmitting}
            />
            <div className="space-y-1">
              <Label htmlFor="notify-tenant" className="font-normal cursor-pointer">
                Notificar empresa por e-mail
              </Label>
              <p className="text-xs text-muted-foreground">
                Envia confirmação da alteração de plano para o tenant.
              </p>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={isSubmitting}
          >
            Cancelar
          </Button>
          <Button onClick={handleSubmit} disabled={isSubmitting || isLoadingPlans || !selectedPlanId}>
            {isSubmitting ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Salvando...
              </>
            ) : (
              'Confirmar alteração'
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
