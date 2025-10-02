import { Card, CardContent } from "@/components/ui/card"
import {ShoppingCart, CreditCard, CheckCircle, Clock, TrendingUp, TrendingDown, ArrowUpRight} from "lucide-react"
import { Badge } from "@/components/ui/badge"
import { cn } from '@/lib/utils'


const performanceMetrics = [
  {
    title: 'Total Pedidos',
    current: '1.2K',
    previous: '890',
    growth: 34.8,
    icon: ShoppingCart,
  },
  {
    title: 'Pedidos Pagos',
    current: '980',
    previous: '720',
    growth: 36.1,
    icon: CreditCard,
  },
  {
    title: 'Pedidos Entregues',
    current: '856',
    previous: '634',
    growth: 35.0,
    icon: CheckCircle,
  },
  {
    title: 'Pedidos Pendentes',
    current: '23%',
    previous: '31%',
    growth: -8.0,
    icon: Clock,
  },
]

export function StatCards() {
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
