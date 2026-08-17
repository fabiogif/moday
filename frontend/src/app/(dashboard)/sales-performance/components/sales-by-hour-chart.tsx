"use client"

import { Bar, BarChart, CartesianGrid, XAxis, YAxis, Tooltip, ResponsiveContainer } from "recharts"
import { ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart"

interface SalesByHourChartProps {
  data: Array<{
    hour: number
    count: number
    hour_label: string
  }>
}

const chartConfig = {
  count: {
    label: "Vendas",
    color: "hsl(var(--primary))",
  },
}

export function SalesByHourChart({ data }: SalesByHourChartProps) {
  const chartData = data.map(item => ({
    hour: item.hour_label,
    vendas: item.count
  }))

  return (
    <ChartContainer config={chartConfig} className="h-[300px] w-full">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={chartData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" className="stroke-muted/30" />
          <XAxis 
            dataKey="hour" 
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

