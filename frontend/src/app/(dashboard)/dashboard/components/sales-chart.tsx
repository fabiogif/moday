"use client"

import { useEffect, useState } from "react"
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from "recharts"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Button } from "@/components/ui/button"
import { Skeleton } from "@/components/ui/skeleton"
import { useAuth } from "@/contexts/auth-context"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"

interface MonthlyData {
  month: string
  sales: number
  goal: number
  orders: number
  performance: number
}

const chartConfig = {
  sales: {
    label: "Vendas",
    color: "hsl(var(--primary))",
  },
  goal: {
    label: "Meta",
    color: "hsl(var(--muted-foreground))",
  },
}

export function SalesChart() {
  const { isAuthenticated, isLoading: authLoading } = useAuth()
  const [timeRange, setTimeRange] = useState("12m")
  const [monthlyData, setMonthlyData] = useState<MonthlyData[]>([])
  const [currentMonth, setCurrentMonth] = useState<MonthlyData | null>(null)

  // Use authenticated API hook for sales performance
  const { data: salesData, loading, error, refetch } = useAuthenticatedApi<{
    monthly_data: MonthlyData[]
    current_month: MonthlyData
  }>(
    '/api/dashboard/sales-performance',
    { immediate: false }
  )

  useEffect(() => {
    // Aguardar autenticação completa antes de carregar
    if (!authLoading && isAuthenticated) {
      refetch()
    }
  }, [authLoading, isAuthenticated, refetch])

  useEffect(() => {
    if (salesData) {
      setMonthlyData(salesData.monthly_data)
      setCurrentMonth(salesData.current_month)
    }
  }, [salesData])

  const filteredData = monthlyData.slice(
    timeRange === "3m" ? -3 : timeRange === "6m" ? -6 : -12
  )

  // if (authLoading || loading) {
  //   return (
  //     <Card>
  //       <CardHeader>
  //         <CardTitle>Desempenho de vendas</CardTitle>
  //         <CardDescription>Vendas mensais vs Metas</CardDescription>
  //       </CardHeader>
  //       <CardContent>
  //         <Skeleton className="h-[350px] w-full" />
  //       </CardContent>
  //     </Card>
  //   )
  // }

  return (<div></div>
    // <Card className="cursor-pointer">
    //   <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
    //     <div>
    //       <CardTitle>Desempenho de vendas</CardTitle>
    //       <CardDescription>
    //         Vendas mensais vs Metas
    //         {currentMonth && (
    //           <span className="ml-2 text-xs">
    //             • Performance atual: {currentMonth.performance.toFixed(1)}%
    //           </span>
    //         )}
    //       </CardDescription>
    //     </div>
    //     <div className="flex items-center space-x-2">
    //       <Select value={timeRange} onValueChange={setTimeRange}>
    //         <SelectTrigger className="w-32 cursor-pointer">
    //           <SelectValue />
    //         </SelectTrigger>
    //         <SelectContent>
    //           <SelectItem value="3m" className="cursor-pointer">Últimos 3 meses</SelectItem>
    //           <SelectItem value="6m" className="cursor-pointer">Últimos 6 meses</SelectItem>
    //           <SelectItem value="12m" className="cursor-pointer">Últimos 12 meses</SelectItem>
    //         </SelectContent>
    //       </Select>
    //     </div>
    //   </CardHeader>
    //   <CardContent className="p-0 pt-6">
    //     <div className="px-6 pb-6">
    //       <ChartContainer config={chartConfig} className="h-[350px] w-full">
    //         <AreaChart data={filteredData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
    //           <defs>
    //             <linearGradient id="colorSales" x1="0" y1="0" x2="0" y2="1">
    //               <stop offset="5%" stopColor="var(--color-sales)" stopOpacity={0.4} />
    //               <stop offset="95%" stopColor="var(--color-sales)" stopOpacity={0.05} />
    //             </linearGradient>
    //             <linearGradient id="colorGoal" x1="0" y1="0" x2="0" y2="1">
    //               <stop offset="5%" stopColor="var(--color-goal)" stopOpacity={0.2} />
    //               <stop offset="95%" stopColor="var(--color-goal)" stopOpacity={0} />
    //             </linearGradient>
    //           </defs>
    //           <CartesianGrid strokeDasharray="3 3" className="stroke-muted/30" />
    //           <XAxis 
    //             dataKey="month" 
    //             axisLine={false}
    //             tickLine={false}
    //             className="text-xs"
    //             tick={{ fontSize: 12 }}
    //           />
    //           <YAxis 
    //             axisLine={false}
    //             tickLine={false}
    //             className="text-xs"
    //             tick={{ fontSize: 12 }}
    //             tickFormatter={(value) => `R$ ${value.toLocaleString()}`}
    //           />
    //           <ChartTooltip content={<ChartTooltipContent />} />
    //           <Area
    //             type="monotone"
    //             dataKey="goal"
    //             stackId="1"
    //             stroke="var(--color-goal)"
    //             fill="url(#colorGoal)"
    //             strokeDasharray="5 5"
    //             strokeWidth={1}
    //           />
    //           <Area
    //             type="monotone"
    //             dataKey="sales"
    //             stackId="2"
    //             stroke="var(--color-sales)"
    //             fill="url(#colorSales)"
    //             strokeWidth={1}
    //           />
    //         </AreaChart>
    //       </ChartContainer>
    //     </div>
    //   </CardContent>
    // </Card>
  )
}