"use client"

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { BadgeCheck, BadgeX, Layers2, Workflow } from "lucide-react"
import type { OrderStatus } from "@/types/order-status"

interface StatusStatCardsProps {
  statuses: OrderStatus[]
}

export function StatusStatCards({ statuses }: StatusStatCardsProps) {
  const total = statuses.length
  const active = statuses.filter((status) => status.is_active).length
  const inactive = statuses.filter((status) => !status.is_active).length
  const finalStatuses = statuses.filter((status) => status.is_final).length

  const cards = [
    {
      title: "Status Totais",
      icon: Layers2,
      value: total,
      description: "Status cadastrados",
    },
    {
      title: "Status Ativos",
      icon: BadgeCheck,
      value: active,
      description: "Disponíveis para uso",
    },
    {
      title: "Status Inativos",
      icon: BadgeX,
      value: inactive,
      description: "Ocultos dos pedidos",
    },
    {
      title: "Status Finais",
      icon: Workflow,
      value: finalStatuses,
      description: "Finalizam pedidos",
    },
  ]

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      {cards.map((card, index) => (
        <Card key={index}>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{card.title}</CardTitle>
            <card.icon className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{card.value}</div>
            <p className="text-xs text-muted-foreground">{card.description}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}

