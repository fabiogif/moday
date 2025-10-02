"use client"

import { 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Users, 
  ShoppingCart, 
  BarChart3 
} from "lucide-react"
import { Card, CardAction, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"

const metrics = [
  {
    title: "Receita total",
    value: "$54,230",
    description: "Receita mensal",
    change: "+12%",
    trend: "up",
    icon: DollarSign,
    footer: "Tendência em alta neste mês",
    subfooter: "Receita dos últimos 6 meses"
  },
  {
    title: "Clientes Ativos",
    value: "2,350",
    description: "Total de clientes ativos",
    change: "+5.2%", 
    trend: "up",
    icon: Users,
    footer: "Forte retenção de usuários",
    subfooter: "O engajamento excede as metas"
  },
  {
    title: "Total de pedidos",
    value: "1,247",
    description: "Pedidos este mês",
    change: "-2.1%",
    trend: "down", 
    icon: ShoppingCart,
    footer: "Queda de 2% neste período",
    subfooter: "O volume de pedidos precisa de atenção"
  },
  {
    title: "Taxa de conversão",
    value: "3.24%",
    description: "Conversão média",
    change: "+8.3%",
    trend: "up",
    icon: BarChart3,
    footer: "Aumento constante do desempenho",
    subfooter: "Atende às projeções de conversão"
  },
]

export function MetricsOverview() {
  return (
    <div className="*:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card dark:*:data-[slot=card]:bg-card *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:shadow-xs grid gap-4 sm:grid-cols-2 @5xl:grid-cols-4">
      {metrics.map((metric) => {
        const TrendIcon = metric.trend === "up" ? TrendingUp : TrendingDown
        
        return (
          <Card key={metric.title} className=" cursor-pointer">
            <CardHeader>
              <CardDescription>{metric.title}</CardDescription>
              <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                {metric.value}
              </CardTitle>
              <CardAction>
                <Badge variant="outline">
                  <TrendIcon className="h-4 w-4" />
                  {metric.change}
                </Badge>
              </CardAction>
            </CardHeader>
            <CardFooter className="flex-col items-start gap-1.5 text-sm">
              <div className="line-clamp-1 flex gap-2 font-medium">
                {metric.footer} <TrendIcon className="size-4" />
              </div>
              <div className="text-muted-foreground">
                {metric.subfooter}
              </div>
            </CardFooter>
          </Card>
        )
      })}
    </div>
  )
}
