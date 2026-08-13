"use client"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { TrendingUp, TrendingDown, ShoppingCart, DollarSign, Ticket, Users } from "lucide-react"
import { Badge } from "@/components/ui/badge"

interface Indicator {
  current: number
  previous: number
  growth: number
}

interface SalesPerformanceIndicatorsProps {
  data: {
    total_sales: Indicator
    total_sales_value: Indicator
    average_ticket: Indicator
    new_clients: Indicator
  }
}

export function SalesPerformanceIndicators({ data }: SalesPerformanceIndicatorsProps) {
  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value)
  }

  const formatNumber = (value: number) => {
    return new Intl.NumberFormat('pt-BR').format(value)
  }

  const formatGrowth = (growth: number) => {
    const sign = growth >= 0 ? '+' : ''
    return `${sign}${growth.toFixed(2)}%`
  }

  const indicators = [
    {
      title: "Total de Vendas",
      value: formatNumber(data.total_sales.current),
      previous: formatNumber(data.total_sales.previous),
      growth: data.total_sales.growth,
      icon: ShoppingCart,
      description: "Número total de vendas realizadas"
    },
    {
      title: "Valor Total das Vendas",
      value: formatCurrency(data.total_sales_value.current),
      previous: formatCurrency(data.total_sales_value.previous),
      growth: data.total_sales_value.growth,
      icon: DollarSign,
      description: "Valor total das vendas realizadas"
    },
    {
      title: "Ticket Médio",
      value: formatCurrency(data.average_ticket.current),
      previous: formatCurrency(data.average_ticket.previous),
      growth: data.average_ticket.growth,
      icon: Ticket,
      description: "Valor médio por venda"
    },
    {
      title: "Novos Clientes",
      value: formatNumber(data.new_clients.current),
      previous: formatNumber(data.new_clients.previous),
      growth: data.new_clients.growth,
      icon: Users,
      description: "Número de novos clientes"
    }
  ]

  return (
    <div className="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
      {indicators.map((indicator) => {
        const Icon = indicator.icon
        const isPositive = indicator.growth >= 0
        const TrendIcon = isPositive ? TrendingUp : TrendingDown

        return (
          <Card key={indicator.title}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                {indicator.title}
              </CardTitle>
              <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{indicator.value}</div>
              <div className="flex items-center gap-2 mt-2">
                <Badge variant={isPositive ? "default" : "destructive"} className="flex items-center gap-1">
                  <TrendIcon className="h-3 w-3" />
                  {formatGrowth(indicator.growth)}
                </Badge>
                <span className="text-xs text-muted-foreground">
                  vs. período anterior
                </span>
              </div>
              <CardDescription className="mt-2 text-xs">
                {indicator.description}
              </CardDescription>
              <div className="mt-2 text-xs text-muted-foreground">
                Período anterior: {indicator.previous}
              </div>
            </CardContent>
          </Card>
        )
      })}
    </div>
  )
}

