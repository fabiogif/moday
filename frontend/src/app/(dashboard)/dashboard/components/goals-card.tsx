"use client"

import { Target } from "lucide-react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Progress } from "@/components/ui/progress"

const PLACEHOLDER_GOALS = [
  { label: "Receita", value: 0 },
  { label: "Pedidos", value: 0 },
  { label: "Clientes", value: 0 },
]

/**
 * Não existe tabela/endpoint de metas configuráveis no backend hoje — este
 * card fica em estado "Em breve" (barras desabilitadas) até essa
 * funcionalidade ser construída, em vez de simular metas com dado falso.
 */
export function GoalsCard() {
  return (
    <Card className="border-dashed bg-muted/20">
      <CardHeader>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Target className="h-4 w-4 text-muted-foreground" />
            <CardTitle className="text-base">Metas</CardTitle>
          </div>
          <Badge variant="outline" className="text-xs font-medium text-muted-foreground">
            Em breve
          </Badge>
        </div>
        <CardDescription>Defina suas metas de receita, pedidos e clientes em breve.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {PLACEHOLDER_GOALS.map((goal) => (
          <div key={goal.label} className="space-y-1.5 opacity-50">
            <div className="flex items-center justify-between text-sm">
              <span className="font-medium">{goal.label}</span>
              <span className="text-muted-foreground">—</span>
            </div>
            <Progress value={goal.value} className="h-2" />
          </div>
        ))}
      </CardContent>
    </Card>
  )
}
