'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { useAdminAuth } from '@/contexts/admin-auth-context'
import adminApi from '@/lib/admin-api-client'
import { StatCard } from '@/components/admin/stat-card'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { PageLoading } from '@/components/ui/loading-progress'
import {
  Building2,
  DollarSign,
  Activity,
  MessageSquare,
  TrendingUp,
  AlertTriangle,
  Clock,
  CheckCircle2,
  XCircle,
} from 'lucide-react'

interface DashboardStats {
  tenants: {
    total: number
    active: number
    trial: number
    expired: number
    suspended: number
    blocked: number
  }
  financial: {
    mrr: number
    pending_invoices: number
    overdue_invoices: number
    total_revenue_month: number
  }
  usage: {
    logins_today: number
    orders_today: number
    revenue_today: number
    messages_today: number
  }
  growth: {
    new_tenants_month: number
    new_tenants_week: number
    churn_rate: number
  }
}

interface Alert {
  type: 'warning' | 'error' | 'info'
  message: string
  action: string
}

export default function AdminDashboardPage() {
  const router = useRouter()
  const { isAuthenticated, isLoading: authLoading } = useAdminAuth()
  const [stats, setStats] = useState<DashboardStats | null>(null)
  const [alerts, setAlerts] = useState<Alert[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push('/admin/login')
    }
  }, [authLoading, isAuthenticated, router])

  useEffect(() => {
    // Aguardar que a autenticação esteja completa e o token esteja disponível
    if (!authLoading && isAuthenticated) {
      // Garantir que o adminApi tenha o token antes de carregar dados
      const token = localStorage.getItem('admin-token')
      if (token) {
        adminApi.setToken(token)
      }
      loadDashboardData()
    }
  }, [authLoading, isAuthenticated])

  const loadDashboardData = async () => {
    try {
      setIsLoading(true)

      // Garantir que o token está configurado antes de fazer requisições
      const token = localStorage.getItem('admin-token')
      if (token) {
        adminApi.setToken(token)
      }

      // Carrega estatísticas e alertas em paralelo
      const [statsResponse, alertsResponse] = await Promise.all([
        adminApi.getDashboardStats(),
        adminApi.getAlerts(),
      ])

      setStats(statsResponse.data)
      setAlerts(alertsResponse.data)
    } catch (error: any) {
      console.error('Erro ao carregar dados do dashboard:', error)
      // Manter stats como null para mostrar mensagem de erro
      setStats(null)
      setAlerts([])
    } finally {
      setIsLoading(false)
    }
  }

  if (authLoading || !isAuthenticated) {
    return null
  }

  if (isLoading) {
    return <PageLoading />
  }

  if (!stats) {
    return (
      <div className="flex-1 space-y-6 px-6 pt-0">
        <Alert variant="destructive">
          <AlertTriangle className="h-4 w-4" />
          <AlertTitle>Erro</AlertTitle>
          <AlertDescription>
            Não foi possível carregar os dados do painel de controle. Tente atualizar a página.
          </AlertDescription>
        </Alert>
      </div>
    )
  }

  const getAlertVariant = (type: string) => {
    switch (type) {
      case 'error':
        return 'destructive'
      case 'warning':
        return 'default'
      default:
        return 'default'
    }
  }

  const getAlertIcon = (type: string) => {
    switch (type) {
      case 'error':
        return XCircle
      case 'warning':
        return AlertTriangle
      default:
        return Clock
    }
  }

  return (
    <div className="flex-1 space-y-6 px-6 pt-0">
      {/* Header */}
      <div className="flex md:flex-row flex-col md:items-center justify-between gap-4 md:gap-6">
        <div className="flex flex-col gap-2">
          <h1 className="text-2xl font-bold tracking-tight">Painel de Controle</h1>
          <p className="text-muted-foreground">
            Visão geral do sistema e principais métricas
          </p>
        </div>
      </div>

      {/* Alertas */}
      {alerts.length > 0 && (
        <div className="space-y-3">
          {alerts.map((alert, index) => {
            const AlertIcon = getAlertIcon(alert.type)
            return (
              <Alert key={index} variant={getAlertVariant(alert.type)}>
                <AlertIcon className="h-4 w-4" />
                <AlertTitle>{alert.message}</AlertTitle>
              </Alert>
            )
          })}
        </div>
      )}

      {/* Stats Cards - Empresas */}
      <div>
        <h2 className="text-xl font-semibold mb-4">Empresas</h2>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <StatCard
            title="Total de Empresas"
            value={stats.tenants.total}
            icon={Building2}
            description={`${stats.tenants.active} ativas`}
          />
          <StatCard
            title="Ativas"
            value={stats.tenants.active}
            icon={CheckCircle2}
            className="border-green-200 bg-green-50/50 dark:border-green-900 dark:bg-green-950/50"
          />
          <StatCard
            title="Trial"
            value={stats.tenants.trial}
            icon={Clock}
            description="Período de teste"
            className="border-blue-200 bg-blue-50/50 dark:border-blue-900 dark:bg-blue-950/50"
          />
          <StatCard
            title="Expiradas"
            value={stats.tenants.expired}
            icon={XCircle}
            className="border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50"
          />
        </div>
      </div>

      {/* Stats Cards - Financeiro */}
      <div>
        <h2 className="text-xl font-semibold mb-4">Financeiro</h2>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <StatCard
            title="MRR"
            value={`R$ ${(stats.financial.mrr || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
            icon={DollarSign}
            description="Receita recorrente mensal"
          />
          <StatCard
            title="Receita do Mês"
            value={`R$ ${(stats.financial.total_revenue_month || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
            icon={TrendingUp}
          />
          <StatCard
            title="Faturas Pendentes"
            value={stats.financial.pending_invoices}
            icon={Clock}
          />
          <StatCard
            title="Faturas Vencidas"
            value={stats.financial.overdue_invoices}
            icon={AlertTriangle}
            className={stats.financial.overdue_invoices > 0 ? "border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50" : ""}
          />
        </div>
      </div>

      {/* Stats Cards - Uso Hoje */}
      <div>
        <h2 className="text-xl font-semibold mb-4">Uso Hoje</h2>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <StatCard
            title="Logins"
            value={stats.usage.logins_today}
            icon={Activity}
          />
          <StatCard
            title="Pedidos"
            value={stats.usage.orders_today}
            icon={Building2}
          />
          <StatCard
            title="Receita"
            value={`R$ ${(stats.usage.revenue_today || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
            icon={DollarSign}
          />
          <StatCard
            title="Mensagens"
            value={stats.usage.messages_today}
            icon={MessageSquare}
          />
        </div>
      </div>

      {/* Crescimento */}
      <Card>
        <CardHeader>
          <CardTitle>Crescimento</CardTitle>
          <CardDescription>
            Métricas de crescimento e conversão
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 md:grid-cols-3">
            <div>
              <p className="text-sm text-muted-foreground">Novos este mês</p>
              <p className="text-2xl font-bold mt-1">{stats.growth.new_tenants_month}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Novos esta semana</p>
              <p className="text-2xl font-bold mt-1">{stats.growth.new_tenants_week}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Taxa de Churn</p>
              <p className="text-2xl font-bold mt-1">{stats.growth.churn_rate.toFixed(2)}%</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
