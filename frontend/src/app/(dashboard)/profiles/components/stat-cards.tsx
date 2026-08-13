"use client"

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Settings, Users, CheckCircle, Shield } from "lucide-react"

interface Profile {
  id: number
  name: string
  description?: string
  created_at: string
  updated_at: string
}

interface StatCardsProps {
  profiles: Profile[]
}

export function StatCards({ profiles }: StatCardsProps) {
  const totalProfiles = profiles.length
  const recentProfiles = profiles.filter(profile => {
    const createdAt = new Date(profile.created_at)
    const thirtyDaysAgo = new Date()
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
    return createdAt >= thirtyDaysAgo
  }).length

  const adminProfiles = profiles.filter(profile => 
    profile.name.toLowerCase().includes('admin') || 
    profile.name.toLowerCase().includes('gerente')
  ).length

  const userProfiles = profiles.filter(profile => 
    profile.name.toLowerCase().includes('user') || 
    profile.name.toLowerCase().includes('cliente')
  ).length

  const stats = [
    {
      title: "Total de Perfis",
      value: totalProfiles,
      icon: Settings,
      description: "Perfis cadastrados no sistema"
    },
    {
      title: "Perfis Recentes",
      value: recentProfiles,
      icon: CheckCircle,
      description: "Criados nos últimos 30 dias"
    },
    {
      title: "Perfis Administrativos",
      value: adminProfiles,
      icon: Shield,
      description: "Perfis de administração"
    },
    {
      title: "Perfis de Usuário",
      value: userProfiles,
      icon: Users,
      description: "Perfis para usuários"
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
