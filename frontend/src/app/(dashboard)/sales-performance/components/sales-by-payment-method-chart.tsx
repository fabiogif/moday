"use client"

import { Pie, PieChart, Cell, Tooltip, Legend, ResponsiveContainer } from "recharts"
import { ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart"

interface SalesByPaymentMethodChartProps {
  data: Array<{
    payment_method: string
    count: number
    total_value: number
  }>
}

const COLORS = [
  "#6366f1", // Indigo
  "#f97316", // Orange
  "#10b981", // Emerald
  "#06b6d4", // Cyan
  "#ec4899", // Pink
  "#facc15", // Amber
  "#a855f7", // Purple
  "#f43f5e", // Rose
  "#22d3ee", // Sky
]

const chartConfig = {
  vendas: {
    label: "Vendas",
    color: "hsl(var(--primary))",
  },
} as const

export function SalesByPaymentMethodChart({ data }: SalesByPaymentMethodChartProps) {
  const chartData = data.map((item, index) => ({
    name: item.payment_method,
    value: item.count,
    total_value: item.total_value,
    color: COLORS[index % COLORS.length]
  }))

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value)
  }

  const CustomTooltip = ({ active, payload }: any) => {
    if (active && payload && payload.length) {
      const data = payload[0]
      return (
        <div className="bg-background border border-border rounded-lg p-3 shadow-lg">
          <p className="font-medium">{data.name}</p>
          <p className="text-sm text-muted-foreground">
            Vendas: {data.value}
          </p>
          <p className="text-sm text-muted-foreground">
            Valor: {formatCurrency(data.payload.total_value)}
          </p>
        </div>
      )
    }
    return null
  }

  return (
    <ChartContainer config={chartConfig} className="h-[400px] w-full">
      <ResponsiveContainer width="100%" height="100%">
        <PieChart>
          <Pie
            data={chartData}
            cx="50%"
            cy="50%"
            labelLine={false}
            label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
            outerRadius={120}
            fill="#8884d8"
            dataKey="value"
          >
            {chartData.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={entry.color} />
            ))}
          </Pie>
          <Tooltip content={<CustomTooltip />} />
          <Legend />
        </PieChart>
      </ResponsiveContainer>
    </ChartContainer>
  )
}

