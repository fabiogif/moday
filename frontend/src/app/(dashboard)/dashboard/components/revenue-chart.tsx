"use client"

import { useState } from "react"
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from "recharts"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"
import { EmptyState } from "@/components/ui/empty-state"
import { LineChart } from "lucide-react"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"

interface MonthlyData {
  month: string
  sales: number
  goal: number
  orders: number
  performance: number
}

interface SalesPerformancePayload {
  monthly_data: MonthlyData[]
  current_month: MonthlyData | null
  summary: {
    total_sales: number
    total_goal: number
    average_performance: number
  }
}

const chartConfig = {
  sales: {
    label: "Vendas",
    color: "hsl(var(--primary))",
  },
  goal: {
    label: "Meta (referência)",
    color: "hsl(var(--muted-foreground))",
  },
}

/**
 * Fonte: /api/dashboard/sales-performance — endpoint fixo (últimos 12 meses),
 * não aceita período customizado, por isso este card não reage ao seletor
 * global de período (diferente de MetricsOverview/InsightsCard, que usam
 * /api/sales-performance). "Meta" é uma referência calculada (receita x1.2),
 * não uma meta configurável pelo usuário — ver GoalsCard para metas reais.
 */
export function RevenueChart() {
  const [timeRange, setTimeRange] = useState("12m")

  const { data, loading, error, refetch } = useAuthenticatedApi<SalesPerformancePayload>(
    "/api/dashboard/sales-performance"
  )

  const monthlyData = data?.monthly_data ?? []
  const currentMonth = data?.current_month ?? null

  const filteredData = monthlyData.slice(
    timeRange === "3m" ? -3 : timeRange === "6m" ? -6 : -12
  )

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Receita</CardTitle>
          <CardDescription>Vendas mensais vs. referência de meta</CardDescription>
        </CardHeader>
        <CardContent>
          <Skeleton className="h-[350px] w-full" />
        </CardContent>
      </Card>
    )
  }

  if (error && monthlyData.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Receita</CardTitle>
        </CardHeader>
        <CardContent>
          <EmptyState
            variant="error"
            icon={LineChart}
            title="Não foi possível carregar o histórico de receita."
            action={{ label: "Tentar novamente", onClick: () => refetch() }}
          />
        </CardContent>
      </Card>
    )
  }

  return (
    <Card className="cursor-default">
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <div>
          <CardTitle>Receita</CardTitle>
          <CardDescription>
            Vendas mensais vs. referência de meta
            {currentMonth && (
              <span className="ml-2 text-xs">
                • Performance atual: {currentMonth.performance.toFixed(1)}%
              </span>
            )}
          </CardDescription>
        </div>
        <div className="flex items-center space-x-2">
          <Select value={timeRange} onValueChange={setTimeRange}>
            <SelectTrigger className="w-32 cursor-pointer" size="sm">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="3m" className="cursor-pointer">Últimos 3 meses</SelectItem>
              <SelectItem value="6m" className="cursor-pointer">Últimos 6 meses</SelectItem>
              <SelectItem value="12m" className="cursor-pointer">Últimos 12 meses</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CardHeader>
      <CardContent className="p-0 pt-6">
        {filteredData.length === 0 ? (
          <div className="px-6 pb-6">
            <EmptyState
              icon={LineChart}
              title="Sem dados de receita ainda."
              description="O histórico mensal aparece aqui assim que houver pedidos concluídos."
            />
          </div>
        ) : (
          <div className="px-6 pb-6">
            <ChartContainer config={chartConfig} className="h-[350px] w-full">
              <AreaChart data={filteredData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
                <defs>
                  <linearGradient id="colorSales" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="var(--color-sales)" stopOpacity={0.4} />
                    <stop offset="95%" stopColor="var(--color-sales)" stopOpacity={0.05} />
                  </linearGradient>
                  <linearGradient id="colorGoal" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="var(--color-goal)" stopOpacity={0.2} />
                    <stop offset="95%" stopColor="var(--color-goal)" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted/30" />
                <XAxis
                  dataKey="month"
                  axisLine={false}
                  tickLine={false}
                  className="text-xs"
                  tick={{ fontSize: 12 }}
                />
                <YAxis
                  axisLine={false}
                  tickLine={false}
                  className="text-xs"
                  tick={{ fontSize: 12 }}
                  tickFormatter={(value) => `R$ ${value.toLocaleString("pt-BR")}`}
                />
                <ChartTooltip content={<ChartTooltipContent />} />
                <Area
                  type="monotone"
                  dataKey="goal"
                  stackId="1"
                  stroke="var(--color-goal)"
                  fill="url(#colorGoal)"
                  strokeDasharray="5 5"
                  strokeWidth={1}
                />
                <Area
                  type="monotone"
                  dataKey="sales"
                  stackId="2"
                  stroke="var(--color-sales)"
                  fill="url(#colorSales)"
                  strokeWidth={1}
                />
              </AreaChart>
            </ChartContainer>
          </div>
        )}
      </CardContent>
    </Card>
  )
}
