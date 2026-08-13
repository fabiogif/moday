"use client"

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Shield, Users, Lock, CheckCircle } from "lucide-react"

interface Permission {
  id: number
  name: string
  slug: string
  description?: string
  created_at: string
  updated_at: string
}

interface StatCardsProps {
  permissions: Permission[]
}

export function StatCards({ permissions }: StatCardsProps) {
  const totalPermissions = permissions.length
  const recentPermissions = permissions.filter(permission => {
    const createdAt = new Date(permission.created_at)
    const thirtyDaysAgo = new Date()
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
    return createdAt >= thirtyDaysAgo
  }).length

  const systemPermissions = permissions.filter(permission => 
    permission.slug.startsWith('system.')
  ).length

  const userPermissions = permissions.filter(permission => 
    permission.slug.startsWith('user.')
  ).length

  const stats = [
    {
      title: "Total de Permissões",
      value: totalPermissions,
      icon: Shield,
      description: "Permissões cadastradas no sistema"
    },
    {
      title: "Permissões Recentes",
      value: recentPermissions,
      icon: CheckCircle,
      description: "Criadas nos últimos 30 dias"
    },
    {
      title: "Permissões do Sistema",
      value: systemPermissions,
      icon: Lock,
      description: "Permissões administrativas"
    },
    {
      title: "Permissões de Usuário",
      value: userPermissions,
      icon: Users,
      description: "Permissões de usuários"
    }
  ]

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {stats.map((stat, index) => (
        <Card key={index} className="border">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              {stat.title}
            </CardTitle>
            <stat.icon className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stat.value}</div>
            <p className="text-xs text-muted-foreground">
              {stat.description}
            </p>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}
