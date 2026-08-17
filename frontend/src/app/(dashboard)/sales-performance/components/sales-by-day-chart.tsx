"use client"

import { Bar, BarChart, CartesianGrid, XAxis, YAxis, Tooltip, ResponsiveContainer } from "recharts"
import { ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart"

interface SalesByDayChartProps {
  data: Array<{
    day_of_week: number
    day_name: string
    count: number
  }>
}

const chartConfig = {
  count: {
    label: "Vendas",
    color: "hsl(var(--primary))",
  },
}

export function SalesByDayChart({ data }: SalesByDayChartProps) {
  const chartData = data.map(item => ({
    day: item.day_name.substring(0, 3), // Primeiras 3 letras do dia
    vendas: item.count
  }))

  return (
    <ChartContainer config={chartConfig} className="h-[300px] w-full">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={chartData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" className="stroke-muted/30" />
          <XAxis 
            dataKey="day" 
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
          />
          <Tooltip content={<ChartTooltipContent />} />
          <Bar
            dataKey="vendas"
            fill="var(--color-count)"
            radius={[4, 4, 0, 0]}
          />
        </BarChart>
      </ResponsiveContainer>
    </ChartContainer>
  )
}

