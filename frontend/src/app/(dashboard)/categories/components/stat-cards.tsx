import { Card, CardContent } from "@/components/ui/card"
import {Tag, Package, CheckCircle, XCircle, TrendingUp, TrendingDown, ArrowUpRight} from "lucide-react"
import { Badge } from "@/components/ui/badge"
import { cn } from '@/lib/utils'
import { useAuthenticatedCategoryStats } from "@/hooks/use-authenticated-api"

interface CategoryStats {
  total_categories: number
  active_categories: number
  inactive_categories: number
  avg_products_per_category: number
  total_products: number
}

export function StatCards() {
  const { data: stats, loading, error } = useAuthenticatedCategoryStats()
  
  // Type assertion para garantir que stats tem o tipo correto
  const categoryStats = stats as CategoryStats | null

  if (loading) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[1, 2, 3, 4].map((i) => (
          <Card key={i} className='border'>
            <CardContent className='space-y-4'>
              <div className='flex items-center justify-between'>
                <div className='w-6 h-6 bg-muted animate-pulse rounded' />
                <div className='w-16 h-5 bg-muted animate-pulse rounded' />
              </div>
              <div className='space-y-2'>
                <div className='h-4 w-24 bg-muted animate-pulse rounded' />
                <div className='h-8 w-12 bg-muted animate-pulse rounded' />
                <div className='h-3 w-20 bg-muted animate-pulse rounded' />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  if (error || !categoryStats) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[1, 2, 3, 4].map((i) => (
          <Card key={i} className='border'>
            <CardContent className='space-y-4'>
              <div className='text-center text-muted-foreground'>
                Erro ao carregar estatísticas
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  const performanceMetrics = [
    {
      title: 'Total Categorias',
      current: categoryStats.total_categories.toString(),
      previous: '0',
      growth: 0,
      icon: Tag,
    },
    {
      title: 'Categorias Ativas',
      current: categoryStats.active_categories.toString(),
      previous: '0',
      growth: 0,
      icon: CheckCircle,
    },
    {
      title: 'Produtos por Categoria',
      current: categoryStats.avg_products_per_category.toString(),
      previous: '0',
      growth: 0,
      icon: Package,
    },
    {
      title: 'Categorias Inativas',
      current: categoryStats.inactive_categories.toString(),
      previous: '0',
      growth: 0,
      icon: XCircle,
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
                {/* <span>dados reais</span> */}
                {/* <ArrowUpRight className='size-3' /> */}
              </div>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}
