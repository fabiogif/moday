'use client'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { CalendarDays, TrendingUp, Clock, Bell, Loader2 } from 'lucide-react'

interface EventStatsCardsProps {
  stats: {
    total: number
    active: number
    upcoming: number
    past: number
    notifications_sent: number
  } | null
  loading?: boolean
}

export function EventStatsCards({ stats, loading }: EventStatsCardsProps) {
  if (loading) {
    return (
      <div className="grid gap-4 md:grid-cols-5">
        {[...Array(5)].map((_, i) => (
          <Card key={i}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <div className="h-4 w-20 bg-muted animate-pulse rounded" />
            </CardHeader>
            <CardContent>
              <div className="h-8 w-16 bg-muted animate-pulse rounded" />
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  if (!stats) return null

  const cards = [
    {
      title: 'Total de Eventos',
      value: stats.total,
      icon: CalendarDays,
      description: 'Todos os eventos cadastrados',
    },
    {
      title: 'Eventos Ativos',
      value: stats.active,
      icon: TrendingUp,
      description: 'Eventos que estão ativos',
    },
    {
      title: 'Próximos Eventos',
      value: stats.upcoming,
      icon: Clock,
      description: 'Eventos futuros agendados',
    },
    {
      title: 'Eventos Passados',
      value: stats.past,
      icon: CalendarDays,
      description: 'Eventos já realizados',
    },
    {
      title: 'Notificações Enviadas',
      value: stats.notifications_sent,
      icon: Bell,
      description: 'Eventos com notificações enviadas',
    },
  ]

  return (
    <div className="grid gap-4 md:grid-cols-5">
      {cards.map((card) => (
        <Card key={card.title}>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{card.title}</CardTitle>
            <card.icon className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{card.value}</div>
            <p className="text-xs text-muted-foreground mt-1">{card.description}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}

