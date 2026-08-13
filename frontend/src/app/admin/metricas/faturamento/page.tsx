'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { useAdminAuth } from '@/contexts/admin-auth-context'
import adminApi from '@/lib/admin-api-client'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { PageLoading } from '@/components/ui/loading-progress'
import { StatCard } from '@/components/admin/stat-card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { DollarSign, TrendingUp, CreditCard, Calendar } from 'lucide-react'

interface RevenueData {
  mrr: number
  revenue_by_month: Array<{
    month: string
    total: number
    count: number
  }>
  by_plan: Array<{
    subscription_plan: string
    count: number
    total_mrr: number
  }>
  top_tenants: Array<{
    id: number
    name: string
    subscription_plan: string
    mrr: number
  }>
}

export default function FaturamentoPage() {
  const router = useRouter()
  const { isAuthenticated, isLoading: authLoading } = useAdminAuth()
  const [data, setData] = useState<RevenueData | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push('/admin/login')
    }
  }, [authLoading, isAuthenticated, router])

  useEffect(() => {
    if (isAuthenticated) {
      loadData()
    }
  }, [isAuthenticated])

  const loadData = async () => {
    try {
      setIsLoading(true)
      const response = await adminApi.getRevenueMetrics()
      setData(response.data)
    } catch (error) {

    } finally {
      setIsLoading(false)
    }
  }

  if (authLoading || !isAuthenticated) {
    return null
  }

  if (isLoading) {
    return <PageLoading message="Carregando métricas de faturamento..." />
  }

  if (!data) {
    return (
      <div className="flex-1 space-y-6 px-6 pt-0">
        <p className="text-muted-foreground">Erro ao carregar dados</p>
      </div>
    )
  }

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value)
  }

  const totalRevenue = data.revenue_by_month.reduce((sum, item) => sum + parseFloat(item.total.toString()), 0)

  return (
    <div className="flex-1 space-y-6 px-6 pt-0">
      {/* Header */}
      <div className="flex md:flex-row flex-col md:items-center justify-between gap-4 md:gap-6">
        <div className="flex flex-col gap-2">
          <h1 className="text-2xl font-bold tracking-tight">Métricas de Faturamento</h1>
          <p className="text-muted-foreground">
            Acompanhe a receita e distribuição por planos
          </p>
        </div>
      </div>

      {/* Stat Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <StatCard
          title="MRR Total"
          value={formatCurrency(data.mrr)}
          description="Monthly Recurring Revenue"
          icon={DollarSign}
        />
        <StatCard
          title="Receita Acumulada"
          value={formatCurrency(totalRevenue)}
          description="Últimos 6 meses"
          icon={TrendingUp}
        />
        <StatCard
          title="Faturas Geradas"
          value={data.revenue_by_month.reduce((sum, item) => sum + item.count, 0)}
          description="Total de faturas"
          icon={CreditCard}
        />
      </div>

      {/* Distribuição por Plano */}
      <Card>
        <CardHeader>
          <CardTitle>Distribuição por Plano</CardTitle>
          <CardDescription>
            Quantidade de empresas e receita por tipo de plano
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Plano</TableHead>
                  <TableHead className="text-right">Empresas</TableHead>
                  <TableHead className="text-right">MRR Total</TableHead>
                  <TableHead className="text-right">MRR Médio</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.by_plan.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                      Nenhum dado disponível
                    </TableCell>
                  </TableRow>
                ) : (
                  data.by_plan.map((plan) => (
                    <TableRow key={plan.subscription_plan}>
                      <TableCell>
                        <span className="font-medium capitalize">
                          {plan.subscription_plan || 'Sem Plano'}
                        </span>
                      </TableCell>
                      <TableCell className="text-right">
                        <Badge variant="secondary">{plan.count}</Badge>
                      </TableCell>
                      <TableCell className="text-right font-medium">
                        {formatCurrency(parseFloat(plan.total_mrr.toString()))}
                      </TableCell>
                      <TableCell className="text-right">
                        {formatCurrency(parseFloat(plan.total_mrr.toString()) / plan.count)}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Receita por Mês */}
      <Card>
        <CardHeader>
          <CardTitle>Receita por Mês</CardTitle>
          <CardDescription>
            Evolução da receita nos últimos 6 meses
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>
                    <Calendar className="h-4 w-4 inline mr-2" />
                    Mês
                  </TableHead>
                  <TableHead className="text-right">Faturas</TableHead>
                  <TableHead className="text-right">Total</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.revenue_by_month.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={3} className="text-center py-8 text-muted-foreground">
                      Nenhuma receita registrada
                    </TableCell>
                  </TableRow>
                ) : (
                  data.revenue_by_month.map((month) => (
                    <TableRow key={month.month}>
                      <TableCell className="font-medium">
                        {new Date(month.month + '-01').toLocaleDateString('pt-BR', {
                          month: 'long',
                          year: 'numeric',
                        })}
                      </TableCell>
                      <TableCell className="text-right">
                        <Badge variant="outline">{month.count}</Badge>
                      </TableCell>
                      <TableCell className="text-right font-bold text-green-600">
                        {formatCurrency(parseFloat(month.total.toString()))}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Top 10 Empresas */}
      <Card>
        <CardHeader>
          <CardTitle>Top 10 Empresas por Faturamento</CardTitle>
          <CardDescription>
            Empresas com maior MRR
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Posição</TableHead>
                  <TableHead>Empresa</TableHead>
                  <TableHead>Plano</TableHead>
                  <TableHead className="text-right">MRR</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.top_tenants.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                      Nenhuma empresa com plano ativo
                    </TableCell>
                  </TableRow>
                ) : (
                  data.top_tenants.map((tenant, index) => (
                    <TableRow key={tenant.id}>
                      <TableCell>
                        <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold">
                          {index + 1}
                        </div>
                      </TableCell>
                      <TableCell className="font-medium">{tenant.name}</TableCell>
                      <TableCell>
                        <Badge variant="secondary" className="capitalize">
                          {tenant.subscription_plan}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right font-bold">
                        {formatCurrency(tenant.mrr)}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

