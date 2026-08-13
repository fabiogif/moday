import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Building2, CheckCircle2, Clock, XCircle } from 'lucide-react'

interface StatCardsProps {
  stats: {
    total: number
    active: number
    trial: number
    expired: number
  }
}

export function StatCards({ stats }: StatCardsProps) {
  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total de Empresas</CardTitle>
          <Building2 className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.total}</div>
          <p className="text-xs text-muted-foreground mt-1">
            Todas as empresas cadastradas
          </p>
        </CardContent>
      </Card>

      <Card className="border-green-200 bg-green-50/50 dark:border-green-900 dark:bg-green-950/50">
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Ativas</CardTitle>
          <CheckCircle2 className="h-4 w-4 text-green-600 dark:text-green-400" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.active}</div>
          <p className="text-xs text-muted-foreground mt-1">
            {((stats.active / stats.total) * 100).toFixed(0)}% do total
          </p>
        </CardContent>
      </Card>

      <Card className="border-blue-200 bg-blue-50/50 dark:border-blue-900 dark:bg-blue-950/50">
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Trial</CardTitle>
          <Clock className="h-4 w-4 text-blue-600 dark:text-blue-400" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.trial}</div>
          <p className="text-xs text-muted-foreground mt-1">
            Período de teste
          </p>
        </CardContent>
      </Card>

      <Card className="border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50">
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Expiradas</CardTitle>
          <XCircle className="h-4 w-4 text-red-600 dark:text-red-400" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.expired}</div>
          <p className="text-xs text-muted-foreground mt-1">
            Precisam renovar
          </p>
        </CardContent>
      </Card>
    </div>
  )
}

