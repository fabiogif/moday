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
import { TrendingUp, TrendingDown, UserPlus, Users, AlertTriangle } from 'lucide-react'

interface GrowthData {
  new_tenants_by_month: Array<{
    month: string
    count: number
  }>
  conversions_by_month: Array<{
    month: string
    count: number
  }>
  churn_by_month: Array<{
    month: string
    count: number
  }>
  conversion_rate: number
  current_stats: {
    total_trials: number
    total_active: number
    total_expired: number
  }
}

export default function CrescimentoPage() {
  const router = useRouter()
  const { isAuthenticated, isLoading: authLoading } = useAdminAuth()
  const [data, setData] = useState<GrowthData | null>(null)
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
      const response = await adminApi.getGrowthMetrics()
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
    return <PageLoading message="Carregando métricas de crescimento..." />
  }

  if (!data) {
    return (
      <div className="flex-1 space-y-6 px-6 pt-0">
        <p className="text-muted-foreground">Erro ao carregar dados</p>
      </div>
    )
  }

  const totalNewTenants = data.new_tenants_by_month.reduce((sum, month) => sum + month.count, 0)
  const totalConversions = data.conversions_by_month.reduce((sum, month) => sum + month.count, 0)
  const totalChurn = data.churn_by_month.reduce((sum, month) => sum + month.count, 0)

  return (
    <div className="flex-1 space-y-6 px-6 pt-0">
      {/* Header */}
      <div className="flex md:flex-row flex-col md:items-center justify-between gap-4 md:gap-6">
        <div className="flex flex-col gap-2">
          <h1 className="text-2xl font-bold tracking-tight">Métricas de Crescimento</h1>
          <p className="text-muted-foreground">
            Acompanhe o crescimento e conversão de empresas
          </p>
        </div>
      </div>

      {/* Stat Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Novos Cadastros"
          value={totalNewTenants}
          description="Últimos 12 meses"
          icon={UserPlus}
          className="border-blue-200 bg-blue-50/50 dark:border-blue-900 dark:bg-blue-950/50"
        />
        <StatCard
          title="Taxa de Conversão"
          value={`${data.conversion_rate.toFixed(1)}%`}
          description="Trial → Pago"
          icon={TrendingUp}
          className="border-green-200 bg-green-50/50 dark:border-green-900 dark:bg-green-950/50"
        />
        <StatCard
          title="Conversões"
          value={totalConversions}
          description="Últimos 12 meses"
          icon={Users}
        />
        <StatCard
          title="Churn Total"
          value={totalChurn}
          description="Empresas perdidas"
          icon={AlertTriangle}
          className="border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50"
        />
      </div>

      {/* Status Atual */}
      <Card>
        <CardHeader>
          <CardTitle>Status Atual</CardTitle>
          <CardDescription>
            Distribuição atual de empresas
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 md:grid-cols-3">
            <div className="text-center p-4 border rounded-lg bg-blue-50/50 dark:bg-blue-950/50">
              <p className="text-sm text-muted-foreground mb-1">Em Trial</p>
              <p className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                {data.current_stats.total_trials}
              </p>
            </div>
            <div className="text-center p-4 border rounded-lg bg-green-50/50 dark:bg-green-950/50">
              <p className="text-sm text-muted-foreground mb-1">Ativas (Pagas)</p>
              <p className="text-3xl font-bold text-green-600 dark:text-green-400">
                {data.current_stats.total_active}
              </p>
            </div>
            <div className="text-center p-4 border rounded-lg bg-red-50/50 dark:bg-red-950/50">
              <p className="text-sm text-muted-foreground mb-1">Expiradas</p>
              <p className="text-3xl font-bold text-red-600 dark:text-red-400">
                {data.current_stats.total_expired}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Novos Cadastros por Mês */}
      <Card>
        <CardHeader>
          <CardTitle>Novos Cadastros por Mês</CardTitle>
          <CardDescription>
            Evolução de novos cadastros nos últimos 12 meses
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Mês</TableHead>
                  <TableHead className="text-right">Novos Cadastros</TableHead>
                  <TableHead className="text-right">Tendência</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.new_tenants_by_month.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={3} className="text-center py-8 text-muted-foreground">
                      Nenhum dado disponível
                    </TableCell>
                  </TableRow>
                ) : (
                  data.new_tenants_by_month.map((month, index) => {
                    const prevMonth = index > 0 ? data.new_tenants_by_month[index - 1] : null
                    const trend = prevMonth 
                      ? ((month.count - prevMonth.count) / prevMonth.count) * 100 
                      : 0

                    return (
                      <TableRow key={month.month}>
                        <TableCell className="font-medium">
                          {new Date(month.month + '-01').toLocaleDateString('pt-BR', {
                            month: 'long',
                            year: 'numeric',
                          })}
                        </TableCell>
                        <TableCell className="text-right">
                          <Badge variant="secondary">{month.count}</Badge>
                        </TableCell>
                        <TableCell className="text-right">
                          {index > 0 && (
                            <div className={`flex items-center justify-end gap-1 ${trend > 0 ? 'text-green-600' : trend < 0 ? 'text-red-600' : 'text-muted-foreground'}`}>
                              {trend > 0 ? (
                                <TrendingUp className="h-4 w-4" />
                              ) : trend < 0 ? (
                                <TrendingDown className="h-4 w-4" />
                              ) : null}
                              <span className="text-sm font-medium">
                                {trend > 0 ? '+' : ''}{trend.toFixed(1)}%
                              </span>
                            </div>
                          )}
                        </TableCell>
                      </TableRow>
                    )
                  })
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Conversões Trial → Pago */}
      <Card>
        <CardHeader>
          <CardTitle>Conversões Trial → Pago</CardTitle>
          <CardDescription>
            Empresas que converteram de trial para plano pago
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Mês</TableHead>
                  <TableHead className="text-right">Conversões</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.conversions_by_month.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={2} className="text-center py-8 text-muted-foreground">
                      Nenhuma conversão registrada
                    </TableCell>
                  </TableRow>
                ) : (
                  data.conversions_by_month.map((month) => (
                    <TableRow key={month.month}>
                      <TableCell className="font-medium">
                        {new Date(month.month + '-01').toLocaleDateString('pt-BR', {
                          month: 'long',
                          year: 'numeric',
                        })}
                      </TableCell>
                      <TableCell className="text-right">
                        <Badge variant="default" className="bg-green-600">
                          {month.count}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Churn por Mês */}
      <Card>
        <CardHeader>
          <CardTitle>Churn por Mês</CardTitle>
          <CardDescription>
            Empresas que cancelaram ou expiraram
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Mês</TableHead>
                  <TableHead className="text-right">Empresas Perdidas</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.churn_by_month.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={2} className="text-center py-8 text-muted-foreground">
                      Nenhum churn registrado
                    </TableCell>
                  </TableRow>
                ) : (
                  data.churn_by_month.map((month) => (
                    <TableRow key={month.month}>
                      <TableCell className="font-medium">
                        {new Date(month.month + '-01').toLocaleDateString('pt-BR', {
                          month: 'long',
                          year: 'numeric',
                        })}
                      </TableCell>
                      <TableCell className="text-right">
                        <Badge variant="destructive">{month.count}</Badge>
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

