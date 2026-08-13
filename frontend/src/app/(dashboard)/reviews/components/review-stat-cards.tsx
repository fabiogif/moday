'use client'

import { Card, CardContent } from '@/components/ui/card'
import { Star, AlertCircle, Check, Award } from 'lucide-react'
import { cn } from '@/lib/utils'
import type { ReviewStats } from '../page'

interface ReviewStatCardsProps {
  stats: ReviewStats | null
}

export function ReviewStatCards({ stats }: ReviewStatCardsProps) {
  const statCards = [
    {
      title: 'Total de Avaliações',
      value: stats?.total || 0,
      subtitle: `Média: ${stats?.average_rating?.toFixed(1) || 0} ⭐`,
      icon: Star,
      variant: 'default' as const
    },
    {
      title: 'Pendentes',
      value: stats?.pending_count || 0,
      subtitle: 'Aguardando moderação',
      icon: AlertCircle,
      variant: 'warning' as const
    },
    {
      title: 'Aprovadas',
      value: stats?.approved_count || 0,
      subtitle: 'Publicadas',
      icon: Check,
      variant: 'success' as const
    },
    {
      title: 'Em Destaque',
      value: stats?.featured_count || 0,
      subtitle: 'Destacadas',
      icon: Award,
      variant: 'featured' as const
    },
  ]

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {statCards.map((stat, index) => (
        <Card key={index} className='border'>
          <CardContent className='space-y-4'>
            <div className='flex items-center justify-between'>
              <stat.icon className={cn(
                'size-6',
                stat.variant === 'default' && 'text-muted-foreground',
                stat.variant === 'warning' && 'text-orange-500',
                stat.variant === 'success' && 'text-green-500',
                stat.variant === 'featured' && 'text-amber-500'
              )} />
            </div>
            <div className='space-y-2'>
              <p className='text-muted-foreground text-sm font-medium'>{stat.title}</p>
              <div className={cn(
                'text-2xl font-bold',
                stat.variant === 'warning' && 'text-orange-600',
                stat.variant === 'success' && 'text-green-600',
                stat.variant === 'featured' && 'text-amber-600'
              )}>
                {stat.value}
              </div>
              <div className='text-muted-foreground text-xs'>
                {stat.subtitle}
              </div>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}

