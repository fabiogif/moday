"use client"

import { useMemo } from "react"
import { Bar, BarChart, CartesianGrid, XAxis, YAxis, ResponsiveContainer } from "recharts"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart"
import { Skeleton } from "@/components/ui/skeleton"
import { EmptyState } from "@/components/ui/empty-state"
import { TrendingUp, TrendingDown, Package } from "lucide-react"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"

interface OrdersVolumeData {
  date: string
  orders: number
  completed: number
}

const chartConfig = {
  orders: {
    label: "Total de Pedidos",
    color: "hsl(217, 91%, 60%)",
  },
  completed: {
    label: "Pedidos Concluídos",
    color: "hsl(142, 71%, 45%)",
  },
}

function extractOrderDate(order: any): string | null {
  const dateValue = order.created_at || order.createdAt || order.date
  if (!dateValue) return null

  try {
    if (typeof dateValue === "string") {
      let orderDateString = dateValue.split(" ")[0].split("T")[0]
      if (orderDateString.includes("/")) {
        const [day, month, year] = orderDateString.split("/")
        orderDateString = `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`
      }
      return orderDateString
    }
    return new Date(dateValue).toISOString().split("T")[0]
  } catch {
    return null
  }
}

/**
 * Fonte: lista completa de pedidos, agrupada no cliente por data (não existe
 * um endpoint agregado de "pedidos por dia" no backend — ver backlog).
 * per_page elevado para reduzir o risco de a paginação (default 15) cortar
 * pedidos fora da janela dos últimos 7 dias; ainda é uma aproximação,
 * marcada como tal na legenda.
 */
export function OrdersVolumeChart() {
  const { data: orders, loading, error, refetch } = useAuthenticatedApi<any>(endpoints.orders.list, {
    queryParams: { per_page: 100 },
  })

  const ordersList: any[] = Array.isArray(orders) ? orders : (orders as any)?.data ?? []

  const chartData = useMemo<OrdersVolumeData[]>(() => {
    const last7Days = Array.from({ length: 7 }, (_, i) => {
      const date = new Date()
      date.setDate(date.getDate() - (6 - i))
      return {
        dateString: date.toISOString().split("T")[0],
        label: date.toLocaleDateString("pt-BR", { day: "2-digit", month: "short" }),
      }
    })

    return last7Days.map(({ dateString, label }) => {
      const dateOrders = ordersList.filter((order) => extractOrderDate(order) === dateString)
      const completed = dateOrders.filter((order) => order.status === "Concluído").length
      return { date: label, orders: dateOrders.length, completed }
    })
  }, [ordersList])

  const totalOrders = chartData.reduce((sum, d) => sum + d.orders, 0)
  const growth =
    chartData.length >= 2
      ? (
          ((chartData[chartData.length - 1].orders - chartData[chartData.length - 2].orders) /
            (chartData[chartData.length - 2].orders || 1)) *
          100
        ).toFixed(1)
      : "0"

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Volume de Pedidos</CardTitle>
          <CardDescription>Últimos 7 dias (aproximado)</CardDescription>
        </CardHeader>
        <CardContent>
          <Skeleton className="h-[300px] w-full rounded-lg" />
        </CardContent>
      </Card>
    )
  }

  if (error && ordersList.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Volume de Pedidos</CardTitle>
        </CardHeader>
        <CardContent>
          <EmptyState
            variant="error"
            icon={Package}
            title="Não foi possível carregar o volume de pedidos."
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
              <Package className="h-5 w-5" />
              Volume de Pedidos
            </CardTitle>
            <CardDescription>Últimos 7 dias (aproximado)</CardDescription>
          </div>
          {totalOrders > 0 && (
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
        {totalOrders === 0 ? (
          <EmptyState
            icon={Package}
            title="Nenhum pedido nos últimos 7 dias."
            description="Este gráfico é atualizado assim que novos pedidos forem criados."
          />
        ) : (
          <>
            <ChartContainer config={chartConfig} className="h-[300px] w-full">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={chartData}>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                  <XAxis dataKey="date" tickLine={false} axisLine={false} className="text-xs" />
                  <YAxis tickLine={false} axisLine={false} className="text-xs" allowDecimals={false} />
                  <ChartTooltip content={<ChartTooltipContent />} />
                  <Bar dataKey="orders" fill="var(--color-orders)" radius={[4, 4, 0, 0]} isAnimationActive />
                  <Bar dataKey="completed" fill="var(--color-completed)" radius={[4, 4, 0, 0]} isAnimationActive />
                </BarChart>
              </ResponsiveContainer>
            </ChartContainer>
            <div className="mt-4 flex items-center justify-center gap-6 text-sm">
              <div className="flex items-center gap-2">
                <div className="h-3 w-3 rounded-full" style={{ backgroundColor: "hsl(217, 91%, 60%)" }} />
                <span className="text-muted-foreground">Total de Pedidos</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="h-3 w-3 rounded-full" style={{ backgroundColor: "hsl(142, 71%, 45%)" }} />
                <span className="text-muted-foreground">Pedidos Concluídos</span>
              </div>
            </div>
          </>
        )}
      </CardContent>
    </Card>
  )
}
