"use client"

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { CreditCard, CheckCircle, XCircle, Activity } from "lucide-react"

interface PaymentMethod {
  id?: number
  uuid: string
  name: string
  description?: string
  is_active: boolean
  tenant_id?: number
  created_at: string
  updated_at: string
}

interface StatCardsProps {
  paymentMethods: PaymentMethod[]
}

export function StatCards({ paymentMethods }: StatCardsProps) {
  const totalMethods = paymentMethods.length
  const activeMethods = paymentMethods.filter(method => method.is_active).length
  const inactiveMethods = paymentMethods.filter(method => !method.is_active).length
  const activePercentage = totalMethods > 0 ? Math.round((activeMethods / totalMethods) * 100) : 0

  const stats = [
    {
      title: "Total de Formas",
      value: totalMethods,
      description: "Formas de pagamento cadastradas",
      icon: CreditCard,
      color: "text-blue-600"
    },
    {
      title: "Ativas",
      value: activeMethods,
      description: "Formas de pagamento disponíveis",
      icon: CheckCircle,
      color: "text-green-600"
    },
    {
      title: "Inativas",
      value: inactiveMethods,
      description: "Formas de pagamento desabilitadas",
      icon: XCircle,
      color: "text-red-600"
    },
    {
      title: "Taxa de Ativação",
      value: `${activePercentage}%`,
      description: "Percentual de formas ativas",
      icon: Activity,
      color: "text-purple-600"
    }
  ]

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      {stats.map((stat, index) => {
        const Icon = stat.icon
        return (
          <Card key={index}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                {stat.title}
              </CardTitle>
              <Icon className={`h-4 w-4 ${stat.color}`} />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stat.value}</div>
              <p className="text-xs text-muted-foreground">
                {stat.description}
              </p>
            </CardContent>
          </Card>
        )
      })}
    </div>
  )
}