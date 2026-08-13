"use client"

import { useAuthenticatedProductStats } from "@/hooks/use-authenticated-api"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Package, CheckCircle2, XCircle, AlertTriangle } from "lucide-react"

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

  // API atual retorna: { total, active, inactive, out_of_stock }
  const statCards = [
    {
      title: "Total de Produtos",
      value: getSafeValue(stats, 'total', 0),
      icon: Package,
      description: "Produtos cadastrados"
    },
    {
      title: "Produtos Ativos",
      value: getSafeValue(stats, 'active', 0),
      icon: CheckCircle2,
      description: "Disponíveis para venda"
    },
    {
      title: "Produtos Inativos",
      value: getSafeValue(stats, 'inactive', 0),
      icon: XCircle,
      description: "Indisponíveis no momento"
    },
    {
      title: "Sem Estoque",
      value: getSafeValue(stats, 'out_of_stock', 0),
      icon: AlertTriangle,
      description: "Zerados no estoque"
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
