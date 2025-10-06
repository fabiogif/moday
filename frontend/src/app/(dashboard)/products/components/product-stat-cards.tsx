"use client"

import { useAuthenticatedProductStats } from "@/hooks/use-authenticated-api"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Package, DollarSign, TrendingUp, AlertTriangle } from "lucide-react"

export function ProductStatCards() {
  const { data: stats, loading, error } = useAuthenticatedProductStats()

  if (loading) {
    return (
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {[...Array(4)].map((_, i) => (
          <Card key={i}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Carregando...</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">-</div>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  if (error) {
    return (
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Erro</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-500">Erro ao carregar</div>
          </CardContent>
        </Card>
      </div>
    )
  }

  // Função auxiliar para obter valores seguros
  const getSafeValue = (obj: any, path: string, defaultValue: any = 0) => {
    return path.split('.').reduce((current, key) => current?.[key], obj) ?? defaultValue
  }

  const statCards = [
    {
      title: "Total Produtos",
      value: getSafeValue(stats, 'total_products', 0),
      icon: Package,
      description: "Produtos cadastrados"
    },
    {
      title: "Receita Total",
      value: `R$ ${getSafeValue(stats, 'total_revenue', 0).toFixed(2)}`,
      icon: DollarSign,
      description: "Valor total em produtos"
    },
    {
      title: "Produtos Ativos",
      value: getSafeValue(stats, 'active_products', 0),
      icon: TrendingUp,
      description: "Produtos com estoque"
    },
    {
      title: "Estoque Baixo",
      value: getSafeValue(stats, 'low_stock_products', 0),
      icon: AlertTriangle,
      description: "Produtos com ≤ 3 unidades"
    }
  ]

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      {statCards.map((stat, index) => (
        <Card key={index}>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
            <stat.icon className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stat.value}</div>
            <p className="text-xs text-muted-foreground">{stat.description}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}
