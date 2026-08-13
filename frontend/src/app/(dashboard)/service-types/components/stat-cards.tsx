import { Card, CardContent } from "@/components/ui/card"
import { Settings, CheckCircle, XCircle, TrendingUp, TrendingDown, ArrowUpRight } from "lucide-react"
import { Badge } from "@/components/ui/badge"
import { cn } from '@/lib/utils'
import { ServiceTypeData } from "../types"

interface ServiceTypeStats {
  total: number
  active: number
  inactive: number
  menu_available: number
}

interface StatCardsProps {
  serviceTypes?: ServiceTypeData[]
}

export function StatCards({ serviceTypes = [] }: StatCardsProps) {
  const stats: ServiceTypeStats = {
    total: serviceTypes.length,
    active: serviceTypes.filter(st => st.is_active).length,
    inactive: serviceTypes.filter(st => !st.is_active).length,
    menu_available: serviceTypes.filter(st => st.available_in_menu).length,
  }

  const performanceMetrics = [
    {
      title: 'Total de Tipos',
      current: stats.total.toString(),
      previous: '0',
      growth: 0,
      icon: Settings,
    },
    {
      title: 'Tipos Ativos',
      current: stats.active.toString(),
      previous: '0',
      growth: 0,
      icon: CheckCircle,
    },
    {
      title: 'Tipos Inativos',
      current: stats.inactive.toString(),
      previous: '0',
      growth: 0,
      icon: XCircle,
    },
    {
      title: 'Disponíveis no Menu',
      current: stats.menu_available.toString(),
      previous: '0',
      growth: 0,
      icon: TrendingUp,
    },
  ]

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
                <ArrowUpRight className='size-3' />
              </div>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}

