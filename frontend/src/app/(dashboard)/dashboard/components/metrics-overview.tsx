"use client"

import { useEffect, useState } from "react"
import { 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Users, 
  ShoppingCart, 
  BarChart3 
} from "lucide-react"
import { Card, CardAction, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import { useAuth } from "@/contexts/auth-context"
import { useRealtimeDashboard } from "@/hooks/use-realtime-dashboard"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"

interface MetricData {
  value: number
  formatted?: string
  growth: number
  trend: 'up' | 'down'
  subtitle: string
  description: string
  chart_data?: Array<{ month: string; revenue: number }>
}

interface MetricsData {
  total_revenue: MetricData
  active_clients: MetricData
  total_orders: MetricData
  conversion_rate: MetricData
}

export function MetricsOverview() {
  const { user, isAuthenticated, isLoading: authLoading } = useAuth()
  const [metrics, setMetrics] = useState<MetricsData | null>(null)

  // Use authenticated API hook for metrics
  const { data: metricsData, loading, error, refetch } = useAuthenticatedApi<MetricsData>(
    '/api/dashboard/metrics',
    { immediate: false }
  )

  // Real-time updates via WebSocket
  const { isConnected } = useRealtimeDashboard({
    tenantId: user?.tenant_id ? parseInt(user.tenant_id) : 0,
    enabled: isAuthenticated && !!user?.tenant_id,
    onMetricsUpdate: (data) => {
      console.log('Dashboard metrics updated in real-time:', data)
      refetch() // Reload when update received
    }
  })

  useEffect(() => {
    // Aguardar autenticação completa antes de carregar
    if (!authLoading && isAuthenticated && user?.tenant_id) {
      refetch()
    }
  }, [authLoading, isAuthenticated, user?.tenant_id, refetch])

  useEffect(() => {
    if (metricsData) {
      setMetrics(metricsData)
    }
  }, [metricsData])

  // Show loading skeletons while auth is loading or data is loading
  if (authLoading || loading || !metrics) {
    return (
      <div className="*:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card dark:*:data-[slot=card]:bg-card *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:shadow-xs grid gap-4 sm:grid-cols-2 @5xl:grid-cols-4">
        {[1, 2, 3, 4].map((i) => (
          <Card key={i} className="cursor-pointer">
            <CardHeader>
              <Skeleton className="h-4 w-24 mb-2" />
              <Skeleton className="h-8 w-32 mb-2" />
              <Skeleton className="h-6 w-20" />
            </CardHeader>
            <CardFooter className="flex-col items-start gap-1.5 text-sm">
              <Skeleton className="h-4 w-full" />
              <Skeleton className="h-3 w-3/4" />
            </CardFooter>
          </Card>
        ))}
      </div>
    )
  }

  const metricsConfig = [
    {
      title: "Receita total",
      value: metrics.total_revenue.formatted || `R$ ${metrics.total_revenue.value.toFixed(2)}`,
      description: "Receita mensal",
      change: `${metrics.total_revenue.growth >= 0 ? '+' : ''}${metrics.total_revenue.growth.toFixed(1)}%`,
      trend: metrics.total_revenue.trend,
      icon: DollarSign,
      footer: metrics.total_revenue.subtitle,
      subfooter: metrics.total_revenue.description
    },
    {
      title: "Clientes Ativos",
      value: metrics.active_clients.value.toString(),
      description: "Total de clientes ativos",
      change: `${metrics.active_clients.growth >= 0 ? '+' : ''}${metrics.active_clients.growth.toFixed(1)}%`,
      trend: metrics.active_clients.trend,
      icon: Users,
      footer: metrics.active_clients.subtitle,
      subfooter: metrics.active_clients.description
    },
    {
      title: "Total de pedidos",
      value: metrics.total_orders.value.toString(),
      description: "Pedidos este mês",
      change: `${metrics.total_orders.growth >= 0 ? '+' : ''}${metrics.total_orders.growth.toFixed(1)}%`,
      trend: metrics.total_orders.trend,
      icon: ShoppingCart,
      footer: metrics.total_orders.subtitle,
      subfooter: metrics.total_orders.description
    },
    {
      title: "Taxa de conversão",
      value: metrics.conversion_rate.formatted || `${metrics.conversion_rate.value.toFixed(1)}%`,
      description: "Conversão média",
      change: `${metrics.conversion_rate.growth >= 0 ? '+' : ''}${metrics.conversion_rate.growth.toFixed(1)}%`,
      trend: metrics.conversion_rate.trend,
      icon: BarChart3,
      footer: metrics.conversion_rate.subtitle,
      subfooter: metrics.conversion_rate.description
    },
  ]

  return (
    <div className="*:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card dark:*:data-[slot=card]:bg-card *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:shadow-xs grid gap-4 sm:grid-cols-2 @5xl:grid-cols-4">
      {metricsConfig.map((metric, index) => {
        const TrendIcon = metric.trend === "up" ? TrendingUp : TrendingDown
        
        return (
          <Card key={metric.title} className="cursor-pointer relative">
            {isConnected && index === 0 && (
              <Badge 
                variant="outline" 
                className="absolute top-2 right-2 text-xs bg-green-50 text-green-700 border-green-200"
              >
                Live
              </Badge>
            )}
            <CardHeader>
              <CardDescription>{metric.title}</CardDescription>
              <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                {metric.value}
              </CardTitle>
              <CardAction>
                <Badge variant="outline">
                  <TrendIcon className="h-4 w-4" />
                  {metric.change}
                </Badge>
              </CardAction>
            </CardHeader>
            <CardFooter className="flex-col items-start gap-1.5 text-sm">
              <div className="line-clamp-1 flex gap-2 font-medium">
                {metric.footer} <TrendIcon className="size-4" />
              </div>
              <div className="text-muted-foreground">
                {metric.subfooter}
              </div>
            </CardFooter>
          </Card>
        )
      })}
    </div>
  )
}
