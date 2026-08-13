"use client"

import { useState, useEffect } from "react"
import { DataTable } from "./components/data-table"
import { PlanFormDialog } from "./components/plan-form-dialog"
import { SuccessAlert } from "./components/success-alert"
import { useAdminPlans, useAdminMutation } from "@/hooks/use-admin-api"
import { PageLoading } from "@/components/ui/loading-progress"
import { Button } from "@/components/ui/button"
import { Plus } from "lucide-react"
import adminApi, { type AdminPlanPayload } from "@/lib/admin-api-client"

export interface PlanDetail {
  id: number
  name: string
  description: string
  plan_id: number
}

export interface Plan {
  id: number
  name: string
  url: string
  price: string | number
  description: string | null
  is_active: boolean
  max_users: number | null
  max_products: number | null
  max_orders_per_month: number | null
  has_marketing: boolean
  has_order_completion_email: boolean
  has_reports: boolean
  details: PlanDetail[]
}

export type PlanFormValues = AdminPlanPayload

export default function PlansPage() {
  const { data: plansFromApi, loading, error, refetch, isAuthLoading, isAuthenticated } = useAdminPlans()
  const { mutate: runMutation, loading: mutating } = useAdminMutation<Plan>()

  const [localPlans, setLocalPlans] = useState<Plan[]>([])
  const [successAlert, setSuccessAlert] = useState({
    open: false,
    title: "",
    message: "",
  })
  const [editingPlan, setEditingPlan] = useState<Plan | null>(null)
  const [formDialogOpen, setFormDialogOpen] = useState(false)

  useEffect(() => {
    if (plansFromApi && Array.isArray(plansFromApi)) {
      setLocalPlans(plansFromApi)
    }
  }, [plansFromApi])

  const handleAddPlan = async (planData: PlanFormValues) => {
    try {
      const result = await runMutation(async () => {
        const response = await adminApi.createPlan(planData)
        return response.data as Plan
      })

      if (result) {
        setLocalPlans((prevPlans) => [...prevPlans, result])
        setSuccessAlert({
          open: true,
          title: "Sucesso!",
          message: "Plano criado com sucesso!",
        })
        setFormDialogOpen(false)
        refetch()
      }
    } catch {
      setSuccessAlert({
        open: true,
        title: "Erro!",
        message: "Erro ao criar plano. Tente novamente.",
      })
    }
  }

  const handleEditPlan = async (planData: PlanFormValues) => {
    if (!editingPlan) return

    try {
      await runMutation(async () => {
        const response = await adminApi.updatePlan(editingPlan.id, planData)
        return response.data as Plan
      })

      setLocalPlans((prevPlans) =>
        prevPlans.map((p) =>
          p.id === editingPlan.id
            ? { ...p, ...planData, id: editingPlan.id, details: editingPlan.details }
            : p
        )
      )

      setSuccessAlert({
        open: true,
        title: "Sucesso!",
        message: "Plano atualizado com sucesso!",
      })
      setFormDialogOpen(false)
      setEditingPlan(null)
      refetch()
    } catch {
      setSuccessAlert({
        open: true,
        title: "Erro!",
        message: "Erro ao editar plano. Tente novamente.",
      })
      refetch()
    }
  }

  const handleDeletePlan = async (id: number) => {
    try {
      await runMutation(async () => {
        await adminApi.deletePlan(id)
        return { id } as unknown as Plan
      })

      setLocalPlans((prevPlans) => prevPlans.filter((p) => p.id !== id))
      setSuccessAlert({
        open: true,
        title: "Sucesso!",
        message: "Plano excluído com sucesso!",
      })
      refetch()
    } catch {
      setSuccessAlert({
        open: true,
        title: "Erro!",
        message: "Erro ao excluir plano. Tente novamente.",
      })
      refetch()
    }
  }

  const handleStartEdit = (plan: Plan) => {
    setEditingPlan(plan)
    setFormDialogOpen(true)
  }

  const handleStartNew = () => {
    setEditingPlan(null)
    setFormDialogOpen(true)
  }

  if (isAuthLoading || !isAuthenticated) {
    return <PageLoading message="Verificando autenticação..." />
  }

  if (loading) {
    return <PageLoading isLoading={loading} message="Carregando planos..." />
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar planos: {error}</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="@container/main px-4 lg:px-6">
        <div className="flex justify-between items-center mb-6">
          <div>
            <h1 className="text-3xl font-bold">Planos de Assinatura</h1>
            <p className="text-muted-foreground">
              Gerencie os planos disponíveis no sistema
            </p>
          </div>
          <Button onClick={handleStartNew} className="cursor-pointer">
            <Plus className="w-4 h-4 mr-2" />
            Novo Plano
          </Button>
        </div>

        <DataTable
          plans={Array.isArray(localPlans) ? localPlans : []}
          onDeletePlan={handleDeletePlan}
          onEditPlan={handleStartEdit}
        />
      </div>

      <PlanFormDialog
        open={formDialogOpen}
        onOpenChange={setFormDialogOpen}
        onSubmit={editingPlan ? handleEditPlan : handleAddPlan}
        editPlan={editingPlan}
        loading={mutating}
      />

      <SuccessAlert
        open={successAlert.open}
        onOpenChange={(open: boolean) =>
          setSuccessAlert((prev) => ({ ...prev, open }))
        }
        title={successAlert.title}
        message={successAlert.message}
      />
    </div>
  )
}
