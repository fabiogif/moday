"use client"

import { Card, CardContent } from "@/components/ui/card"
import {ShoppingCart, CreditCard, CheckCircle, Clock, TrendingUp, TrendingDown, ArrowUpRight, DollarSign} from "lucide-react"
import { Badge } from "@/components/ui/badge"
import { cn } from '@/lib/utils'
import { useAuthenticatedOrderStats } from "@/hooks/use-authenticated-api"
import { Skeleton } from "@/components/ui/skeleton"

interface OrderStats {
  total_orders: {
    current: number
    previous: number
    growth: number
  }
  pending_orders: {
    current: number
    previous: number
    growth: number
  }
  paid_orders: {
    current: number
    previous: number
    growth: number
  }
  delivered_orders: {
    current: number
    previous: number
    growth: number
  }
  total_revenue?: {
    current: number
    previous: number
    growth: number
  }
}

export function StatCards() {
  const { data: stats, loading, error } = useAuthenticatedOrderStats()

  if (loading) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[1, 2, 3, 4].map((i) => (
          <Card key={i} className="border">
            <CardContent className="space-y-4 pt-6">
              <div className="flex items-center justify-between">
                <Skeleton className="h-6 w-6 rounded" />
                <Skeleton className="h-5 w-16 rounded-full" />
              </div>
              <div className="space-y-2">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-8 w-16" />
                <Skeleton className="h-4 w-20" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  if (error || !stats) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card className="border col-span-full">
          <CardContent className="py-8 text-center text-muted-foreground">
            Erro ao carregar estatísticas
          </CardContent>
        </Card>
      </div>
    )
  }

  const performanceMetrics = [
    {
      title: 'Total Pedidos',
      current: stats.total_orders.current.toString(),
      previous: stats.total_orders.previous.toString(),
      growth: stats.total_orders.growth,
      icon: ShoppingCart,
    },
    {
      title: 'Pedidos Pagos',
      current: stats.paid_orders.current.toString(),
      previous: stats.paid_orders.previous.toString(),
      growth: stats.paid_orders.growth,
      icon: CreditCard,
    },
    {
      title: 'Pedidos Entregues',
      current: stats.delivered_orders.current.toString(),
      previous: stats.delivered_orders.previous.toString(),
      growth: stats.delivered_orders.growth,
      icon: CheckCircle,
    },
    {
      title: 'Pedidos Pendentes',
      current: stats.pending_orders.current.toString(),
      previous: stats.pending_orders.previous.toString(),
      growth: stats.pending_orders.growth,
      icon: Clock,
    },
  ]

  // Se houver dados de receita, adicionar ao início
  if (stats.total_revenue) {
    performanceMetrics.unshift({
      title: 'Receita Total',
      current: `R$ ${stats.total_revenue.current.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`,
      previous: `R$ ${stats.total_revenue.previous.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`,
      growth: stats.total_revenue.growth,
      icon: DollarSign,
    })
  }

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {performanceMetrics.slice(0, 4).map((metric, index) => (
        <Card key={index} className='border'>
          <CardContent className='space-y-4 pt-6'>
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
                    <TrendingUp className='me-1 size-3' />
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
                <span>de {metric.previous}</span>
                <ArrowUpRight className='size-3' />
              </div>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}
