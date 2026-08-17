"use client"

import { useMemo } from "react"
import { Bar, BarChart, CartesianGrid, XAxis, YAxis, ResponsiveContainer } from "recharts"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart"
import { Skeleton } from "@/components/ui/skeleton"
import { EmptyState } from "@/components/ui/empty-state"
import { Users, TrendingUp, TrendingDown } from "lucide-react"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"

interface CustomersData {
  month: string
  newCustomers: number
}

const chartConfig = {
  newCustomers: {
    label: "Novos Clientes",
    color: "hsl(262, 83%, 58%)",
  },
}

/**
 * Fonte: lista completa de clientes, agrupada no cliente por mês de criação
 * (não existe endpoint agregado de "clientes por mês" no backend — ver
 * backlog). A série "Clientes Recorrentes" que existia aqui era uma
 * estimativa fabricada (30% dos novos) — removida; o indicador real de
 * clientes recorrentes vive como card "Em breve" no KPI Grid até o backend
 * calcular isso de verdade.
 */
export function CustomersChart() {
  const { data: clients, loading, error, refetch } = useAuthenticatedApi<any>(endpoints.clients.list, {
    queryParams: { per_page: 200 },
  })

  const clientsList: any[] = Array.isArray(clients) ? clients : (clients as any)?.data ?? []

  const chartData = useMemo<CustomersData[]>(() => {
    const last6Months = Array.from({ length: 6 }, (_, i) => {
      const date = new Date()
      date.setMonth(date.getMonth() - (5 - i))
      return {
        month: date.toLocaleDateString("pt-BR", { month: "short" }),
        year: date.getFullYear(),
        monthIndex: date.getMonth(),
      }
    })

    return last6Months.map(({ month, year, monthIndex }) => {
      const monthClients = clientsList.filter((client) => {
        const dateValue = client.created_at || client.createdAt
        if (!dateValue) return false
        try {
          const clientDate = new Date(dateValue)
          return clientDate.getMonth() === monthIndex && clientDate.getFullYear() === year
        } catch {
          return false
        }
      })
      return {
        month: month.charAt(0).toUpperCase() + month.slice(1),
        newCustomers: monthClients.length,
      }
    })
  }, [clientsList])

  const totalNewInWindow = chartData.reduce((sum, d) => sum + d.newCustomers, 0)
  const growth =
    chartData.length >= 2
      ? (
          ((chartData[chartData.length - 1].newCustomers - chartData[chartData.length - 2].newCustomers) /
            (chartData[chartData.length - 2].newCustomers || 1)) *
          100
        ).toFixed(1)
      : "0"

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Novos Clientes</CardTitle>
          <CardDescription>Últimos 6 meses</CardDescription>
        </CardHeader>
        <CardContent>
          <Skeleton className="h-[300px] w-full rounded-lg" />
        </CardContent>
      </Card>
    )
  }

  if (error && clientsList.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Novos Clientes</CardTitle>
        </CardHeader>
        <CardContent>
          <EmptyState
            variant="error"
            icon={Users}
            title="Não foi possível carregar os dados de clientes."
            action={{ label: "Tentar novamente", onClick: () => refetch() }}
          />
        </CardContent>
      </Card>
    )
  }

  const GrowthIcon = Number(growth) >= 0 ? TrendingUp : TrendingDown

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle className="flex items-center gap-2">
              <Users className="h-5 w-5" />
              Novos Clientes
            </CardTitle>
            <CardDescription>Últimos 6 meses • {clientsList.length} clientes totais</CardDescription>
          </div>
          {totalNewInWindow > 0 && (
            <div className="flex items-center gap-2 text-sm">
              <GrowthIcon className={`h-4 w-4 ${Number(growth) >= 0 ? "text-emerald-500" : "text-rose-500"}`} />
              <span className={`font-medium ${Number(growth) >= 0 ? "text-emerald-500" : "text-rose-500"}`}>
                {growth}%
              </span>
            </div>
          )}
        </div>
      </CardHeader>
      <CardContent>
        {totalNewInWindow === 0 ? (
          <EmptyState
            icon={Users}
            title="Nenhum cliente novo nos últimos 6 meses."
            description="O crescimento de clientes aparece aqui assim que novos cadastros forem feitos."
          />
        ) : (
          <ChartContainer config={chartConfig} className="h-[300px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis dataKey="month" tickLine={false} axisLine={false} className="text-xs" />
                <YAxis tickLine={false} axisLine={false} className="text-xs" allowDecimals={false} />
                <ChartTooltip content={<ChartTooltipContent />} />
                <Bar dataKey="newCustomers" fill="var(--color-newCustomers)" radius={[4, 4, 0, 0]} isAnimationActive />
              </BarChart>
            </ResponsiveContainer>
          </ChartContainer>
        )}
      </CardContent>
    </Card>
  )
}
