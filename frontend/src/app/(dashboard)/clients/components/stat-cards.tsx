import { Card, CardContent } from "@/components/ui/card"
import {Users, ShoppingCart, TrendingUp, UserCheck, TrendingUp as TrendingUpIcon, TrendingDown, ArrowUpRight} from "lucide-react"
import { Badge } from "@/components/ui/badge"
import { cn } from '@/lib/utils'
import { useAuthenticatedClientStats } from "@/hooks/use-authenticated-api"

interface ClientStats {
  total_clients: {
    current: number
    previous: number
    growth: number
  }
  active_clients: {
    current: number
    previous: number
    growth: number
  }
  orders_per_client: {
    current: number
    previous: number
    growth: number
  }
  new_clients: {
    current: number
    previous: number
    growth: number
  }
}

export function StatCards() {
  const { data: statsData, loading, error } = useAuthenticatedClientStats()
  
  // Function to format numbers
  const formatNumber = (value: number, isDecimal: boolean = false): string => {
    if (isDecimal) {
      return value.toFixed(1)
    }
    if (value >= 1000) {
      return `${(value / 1000).toFixed(1)}K`
    }
    return value.toString()
  }

  // Default stats while loading or on error
  const defaultStats: ClientStats = {
    total_clients: { current: 0, previous: 0, growth: 0 },
    active_clients: { current: 0, previous: 0, growth: 0 },
    orders_per_client: { current: 0, previous: 0, growth: 0 },
    new_clients: { current: 0, previous: 0, growth: 0 }
  }

  // Extract stats from API response
  const getStatsFromData = (data: any): ClientStats => {
    if (!data) return defaultStats
    if (data.data) return data.data
    return data
  }

  const stats = getStatsFromData(statsData) || defaultStats

  const performanceMetrics = [
    {
      title: 'Total Clientes',
      current: formatNumber(stats.total_clients.current),
      previous: stats.total_clients.previous.toString(),
      growth: stats.total_clients.growth,
      icon: Users,
    },
    {
      title: 'Clientes Ativos',
      current: formatNumber(stats.active_clients.current),
      previous: stats.active_clients.previous.toString(),
      growth: stats.active_clients.growth,
      icon: UserCheck,
    },
    {
      title: 'Pedidos por Cliente',
      current: formatNumber(stats.orders_per_client.current, true),
      previous: formatNumber(stats.orders_per_client.previous, true),
      growth: stats.orders_per_client.growth,
      icon: ShoppingCart,
    },
    {
      title: 'Novos Clientes',
      current: formatNumber(stats.new_clients.current),
      previous: stats.new_clients.previous.toString(),
      growth: stats.new_clients.growth,
      icon: TrendingUp,
    },
  ]

  if (loading) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[1, 2, 3, 4].map((i) => (
          <Card key={i} className='border'>
            <CardContent className='space-y-4'>
              <div className='flex items-center justify-center h-24'>
                <div className="text-muted-foreground">Carregando...</div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  if (error) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[1, 2, 3, 4].map((i) => (
          <Card key={i} className='border'>
            <CardContent className='space-y-4'>
              <div className='flex items-center justify-center h-24'>
                <div className="text-destructive text-sm">Erro ao carregar</div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {performanceMetrics.map((metric, index) => (
        <Card key={index} className='border'>
          <CardContent className='space-y-4'>
            <div className='flex items-center justify-between'>
              <metric.icon className='text-muted-foreground size-6' />
              <Badge
                variant='outline'
                className={cn(
                  metric.growth >= 0
                    ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950/20 dark:text-green-400'
                    : 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950/20 dark:text-red-400',
                )}
              >
                {metric.growth >= 0 ? (
                  <>
                    <TrendingUpIcon className='me-1 size-3' />
                    {metric.growth >= 0 ? '+' : ''}
                    {metric.growth}%
                  </>
                ) : (
                  <>
                    <TrendingDown className='me-1 size-3' />
                    {metric.growth}%
                  </>
                )}
              </Badge>
            </div>

            <div className='space-y-2'>
              <p className='text-muted-foreground text-sm font-medium'>{metric.title}</p>
              <div className='text-2xl font-bold'>{metric.current}</div>
              <div className='text-muted-foreground flex items-center gap-2 text-sm'>
                <span>from {metric.previous}</span>
                <ArrowUpRight className='size-3' />
              </div>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}
