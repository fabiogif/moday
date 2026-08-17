"use client"

import { use, useState, useEffect } from "react"
import { useRouter } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { PageLoading } from "@/components/ui/loading-progress"
import { ArrowLeft, Edit, DollarSign, Users, Package, ShoppingCart, CheckCircle, XCircle, Layers } from "lucide-react"
import Link from "next/link"
import { useAdminAuth } from "@/contexts/admin-auth-context"
import adminApi from "@/lib/admin-api-client"
import { Plan } from "../page"
import { PLAN_MODULE_GROUPS } from "@/lib/plan-modules"

interface PageProps {
  params: Promise<{ id: string }>
}

export default function PlanDetailPage({ params }: PageProps) {
  const { id } = use(params)
  const router = useRouter()
  const { isAuthenticated, isLoading: authLoading } = useAdminAuth()
  const [plan, setPlan] = useState<Plan | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (authLoading) return
    if (!isAuthenticated) {
      router.push('/admin/login')
      return
    }

    const fetchPlan = async () => {
      try {
        setLoading(true)
        adminApi.reloadToken()
        const response = await adminApi.getPlan(id)

        if (response.success && response.data) {
          setPlan(response.data as Plan)
        } else {
          setError("Plano não encontrado")
        }
      } catch (err: unknown) {
        setError(err instanceof Error ? err.message : "Erro ao carregar plano")
      } finally {
        setLoading(false)
      }
    }

    fetchPlan()
  }, [id, authLoading, isAuthenticated, router])

  if (loading) {
    return <PageLoading isLoading={loading} message="Carregando plano..." />
  }

  if (error || !plan) {
    return (
      <div className="flex flex-col items-center justify-center h-96 gap-4">
        <div className="text-destructive text-lg">{error || "Plano não encontrado"}</div>
        <Button onClick={() => router.push("/admin/plans")} className="cursor-pointer">
          <ArrowLeft className="w-4 h-4 mr-2" />
          Voltar para Planos
        </Button>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-6 py-2 px-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => router.push("/admin/plans")}
            className="cursor-pointer"
          >
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div>
            <div className="flex items-center gap-3">
              <h1 className="text-3xl font-bold">{plan.name}</h1>
              {plan.is_active ? (
                <Badge variant="default" className="gap-1">
                  <CheckCircle className="w-3 h-3" />
                  Ativo
                </Badge>
              ) : (
                <Badge variant="secondary" className="gap-1">
                  <XCircle className="w-3 h-3" />
                  Inativo
                </Badge>
              )}
            </div>
            {plan.description && (
              <p className="text-muted-foreground mt-1">{plan.description}</p>
            )}
          </div>
        </div>
        <Button onClick={() => router.push("/admin/plans")} className="cursor-pointer">
          <Edit className="w-4 h-4 mr-2" />
          Editar
        </Button>
      </div>

      {/* Preço e Informações Básicas */}
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Preço Mensal</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {Number(plan.price) === 0 ? "Grátis" : `R$ ${Number(plan.price).toFixed(2)}`}
            </div>
            {Number(plan.price) > 0 && (
              <p className="text-xs text-muted-foreground mt-1">por mês</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Usuários</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {plan.max_users === null || plan.max_users >= 999999 ? "Ilimitado" : plan.max_users}
            </div>
            <p className="text-xs text-muted-foreground mt-1">máximo permitido</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Produtos</CardTitle>
            <Package className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {plan.max_products === null || plan.max_products >= 999999 ? "Ilimitado" : plan.max_products}
            </div>
            <p className="text-xs text-muted-foreground mt-1">máximo permitido</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pedidos/mês</CardTitle>
            <ShoppingCart className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {plan.max_orders_per_month === null || plan.max_orders_per_month >= 999999 
                ? "Ilimitado" 
                : plan.max_orders_per_month}
            </div>
            <p className="text-xs text-muted-foreground mt-1">por mês</p>
          </CardContent>
        </Card>
      </div>

      {/* Features */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Layers className="w-5 h-5" />
            Features Incluídas
          </CardTitle>
          <CardDescription>
            Funcionalidades disponíveis neste plano
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {PLAN_MODULE_GROUPS.map((module) => (
            <div key={module.id} className="space-y-3">
              <h4 className="font-medium text-sm">{module.label}</h4>
              <div className="grid gap-3 md:grid-cols-2">
                {module.options.map((option) => (
                  <div key={option.key} className="flex items-center justify-between p-4 rounded-lg border">
                    <span className="font-medium text-sm">{option.label}</span>
                    {plan[option.key] ? (
                      <Badge variant="default" className="gap-1">
                        <CheckCircle className="w-3 h-3" />
                        Incluído
                      </Badge>
                    ) : (
                      <Badge variant="secondary" className="gap-1">
                        <XCircle className="w-3 h-3" />
                        Não incluído
                      </Badge>
                    )}
                  </div>
                ))}
              </div>
            </div>
          ))}
        </CardContent>
      </Card>

      {/* Detalhes do Plano */}
      {plan.details && plan.details.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Detalhes do Plano</CardTitle>
            <CardDescription>
              Lista completa de funcionalidades exibidas na landing page
            </CardDescription>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {plan.details.map((detail, index) => (
                <li key={detail.id || `detail-${index}`} className="flex items-start gap-3 p-3 rounded-lg bg-muted/30">
                  <CheckCircle className="w-4 h-4 text-primary mt-0.5 shrink-0" />
                  <span className="text-sm">{detail.name}</span>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>
      )}
    </div>
  )
}

