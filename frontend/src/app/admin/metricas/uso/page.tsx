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
import { Activity, Users, TrendingUp, AlertTriangle } from 'lucide-react'

interface UsageData {
  logins_by_day: Array<{
    metric_date: string
    total_logins: number
  }>
  active_tenants_per_day: Array<{
    metric_date: string
    active_count: number
  }>
  most_active_tenants: Array<{
    tenant_id: number
    tenant_name: string
    total_logins: number
  }>
  inactive_tenants_count: number
  adoption_rate: number
}

export default function UsoPage() {
  const router = useRouter()
  const { isAuthenticated, isLoading: authLoading } = useAdminAuth()
  const [data, setData] = useState<UsageData | null>(null)
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
      const response = await adminApi.getUsageMetrics()
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
    return <PageLoading message="Carregando métricas de uso..." />
  }

  if (!data) {
    return (
      <div className="flex-1 space-y-6 px-6 pt-0">
        <p className="text-muted-foreground">Erro ao carregar dados</p>
      </div>
    )
  }

  const totalLogins = data.logins_by_day.reduce((sum, day) => sum + day.total_logins, 0)
  const avgActivePerDay = data.active_tenants_per_day.reduce((sum, day) => sum + day.active_count, 0) / 
    (data.active_tenants_per_day.length || 1)

  return (
    <div className="flex-1 space-y-6 px-6 pt-0">
      {/* Header */}
      <div className="flex md:flex-row flex-col md:items-center justify-between gap-4 md:gap-6">
        <div className="flex flex-col gap-2">
          <h1 className="text-2xl font-bold tracking-tight">Métricas de Uso</h1>
          <p className="text-muted-foreground">
            Acompanhe o engajamento e atividade das empresas
          </p>
        </div>
      </div>

      {/* Stat Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Total de Logins"
          value={totalLogins}
          description="Últimos 30 dias"
          icon={Activity}
        />
        <StatCard
          title="Empresas Ativas/Dia"
          value={Math.round(avgActivePerDay)}
          description="Média diária"
          icon={Users}
        />
        <StatCard
          title="Taxa de Adoção"
          value={`${data.adoption_rate.toFixed(1)}%`}
          description="Empresas usando ativamente"
          icon={TrendingUp}
          className="border-green-200 bg-green-50/50 dark:border-green-900 dark:bg-green-950/50"
        />
        <StatCard
          title="Empresas Inativas"
          value={data.inactive_tenants_count}
          description="Sem acesso há 7+ dias"
          icon={AlertTriangle}
          className={data.inactive_tenants_count > 10 ? "border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50" : ""}
        />
      </div>

      {/* Empresas Mais Ativas */}
      <Card>
        <CardHeader>
          <CardTitle>Top 10 Empresas Mais Ativas</CardTitle>
          <CardDescription>
            Empresas com maior número de logins nos últimos 30 dias
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Posição</TableHead>
                  <TableHead>Empresa</TableHead>
                  <TableHead className="text-right">Total de Logins</TableHead>
                  <TableHead className="text-right">Média/Dia</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.most_active_tenants.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                      Nenhum dado disponível
                    </TableCell>
                  </TableRow>
                ) : (
                  data.most_active_tenants.map((tenant, index) => (
                    <TableRow key={tenant.tenant_id}>
                      <TableCell>
                        <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold">
                          {index + 1}
                        </div>
                      </TableCell>
                      <TableCell className="font-medium">{tenant.tenant_name}</TableCell>
                      <TableCell className="text-right">
                        <Badge variant="secondary">{tenant.total_logins}</Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        {Math.round(tenant.total_logins / 30)}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* Atividade Diária */}
      <Card>
        <CardHeader>
          <CardTitle>Atividade Diária</CardTitle>
          <CardDescription>
            Logins por dia nos últimos 30 dias
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Data</TableHead>
                  <TableHead className="text-right">Logins</TableHead>
                  <TableHead className="text-right">Empresas Ativas</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.logins_by_day.slice(-10).reverse().map((day, index) => {
                  const activeDay = data.active_tenants_per_day.find(
                    (d) => d.metric_date === day.metric_date
                  )
                  
                  return (
                    <TableRow key={day.metric_date}>
                      <TableCell className="font-medium">
                        {new Date(day.metric_date).toLocaleDateString('pt-BR', {
                          day: '2-digit',
                          month: 'short',
                          year: 'numeric',
                        })}
                      </TableCell>
                      <TableCell className="text-right">
                        <Badge variant="outline">{day.total_logins}</Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        <Badge variant="secondary">{activeDay?.active_count || 0}</Badge>
                      </TableCell>
                    </TableRow>
                  )
                })}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

