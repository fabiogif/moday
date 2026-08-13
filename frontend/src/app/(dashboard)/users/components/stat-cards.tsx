"use client"

import { Card, CardContent } from "@/components/ui/card"
import { Users, UserCheck, Clock5 } from "lucide-react"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"

interface UserStats {
  total_users: number
  active_users: number
  pending_users: number
  inactive_users: number
}

export function StatCards() {
  const { data: stats, loading } = useAuthenticatedApi<UserStats>(endpoints.users.stats)

  const metrics = [
    {
      title: 'Total de usuários',
      value: stats?.total_users || 0,
      icon: Users,
      color: 'text-blue-600',
    },
    {
      title: 'Usuários ativos',
      value: stats?.active_users || 0,
      icon: UserCheck,
      color: 'text-green-600',
    },
    {
      title: 'Usuários pendentes',
      value: stats?.pending_users || 0,
      icon: Clock5,
      color: 'text-yellow-600',
    },
  ]

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {metrics.map((metric, index) => (
        <Card key={index} className='border'>
          <CardContent className='p-6'>
            <div className='flex items-center justify-between'>
              <div className='space-y-2'>
                <p className='text-muted-foreground text-sm font-medium'>{metric.title}</p>
                <div className='text-3xl font-bold'>
                  {loading ? (
                    <div className="h-9 w-16 bg-gray-200 dark:bg-gray-700 animate-pulse rounded" />
                  ) : (
                    metric.value
                  )}
                </div>
              </div>
              <metric.icon className={`${metric.color} size-8`} />
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}
