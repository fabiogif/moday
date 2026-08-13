"use client"

import { Lightbulb, TrendingUp, TrendingDown, CalendarDays, Clock3, CreditCard } from "lucide-react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Skeleton } from "@/components/ui/skeleton"
import { EmptyState } from "@/components/ui/empty-state"
import { useSalesPerformance } from "@/hooks/use-sales-performance"
import { useDashboardFilters } from "../context/dashboard-filters-context"
import type { LucideIcon } from "lucide-react"

interface Insight {
  icon: LucideIcon
  text: string
  tone: "positive" | "negative" | "neutral"
}

const PAYMENT_METHOD_LABELS: Record<string, string> = {
  pix: "Pix",
  credit_card: "Cartão de crédito",
  debit_card: "Cartão de débito",
  cash: "Dinheiro",
  money: "Dinheiro",
}

function buildInsights(data: NonNullable<ReturnType<typeof useSalesPerformance>["data"]>): Insight[] {
  const insights: Insight[] = []
  const { indicators, best_day, best_hour, sales_by_payment_method } = data

  if (indicators.total_sales_value.growth !== 0) {
    const growth = indicators.total_sales_value.growth
    const isUp = growth > 0
    insights.push({
      icon: isUp ? TrendingUp : TrendingDown,
      tone: isUp ? "positive" : "negative",
      text: `Receita ${isUp ? "cresceu" : "caiu"} ${Math.abs(growth).toFixed(1)}% em relação ao período anterior.`,
    })
  }

  if (best_day && best_day.count > 0) {
    insights.push({
      icon: CalendarDays,
      tone: "neutral",
      text: `${best_day.day_name} é o melhor dia de vendas no período, com ${best_day.count} pedido${best_day.count > 1 ? "s" : ""}.`,
    })
  }

  if (best_hour && best_hour.count > 0) {
    insights.push({
      icon: Clock3,
      tone: "neutral",
      text: `O horário de pico é ${best_hour.hour_label}, com ${best_hour.count} pedido${best_hour.count > 1 ? "s" : ""}.`,
    })
  }

  if (sales_by_payment_method && sales_by_payment_method.length > 0) {
    const totalCount = sales_by_payment_method.reduce((sum, m) => sum + m.count, 0)
    const top = [...sales_by_payment_method].sort((a, b) => b.count - a.count)[0]
    if (totalCount > 0 && top) {
      const percentage = Math.round((top.count / totalCount) * 100)
      const label = PAYMENT_METHOD_LABELS[top.payment_method.toLowerCase()] ?? top.payment_method
      insights.push({
        icon: CreditCard,
        tone: "neutral",
        text: `${percentage}% dos pagamentos no período foram via ${label}.`,
      })
    }
  }

  if (indicators.average_ticket.growth !== 0) {
    const growth = indicators.average_ticket.growth
    const isUp = growth > 0
    insights.push({
      icon: isUp ? TrendingUp : TrendingDown,
      tone: isUp ? "positive" : "negative",
      text: `O ticket médio ${isUp ? "aumentou" : "diminuiu"} ${Math.abs(growth).toFixed(1)}% no período.`,
    })
  }

  return insights.slice(0, 5)
}

const TONE_CLASSES: Record<Insight["tone"], string> = {
  positive: "text-emerald-600 dark:text-emerald-400",
  negative: "text-rose-600 dark:text-rose-400",
  neutral: "text-primary",
}

export function InsightsCard() {
  const { dateRange } = useDashboardFilters()
  const { data, loading, error } = useSalesPerformance({
    start_date: dateRange.start_date,
    end_date: dateRange.end_date,
    days: dateRange.days,
  })

  const insights = data ? buildInsights(data) : []

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center gap-2">
          <Lightbulb className="h-4 w-4 text-amber-500" />
          <CardTitle className="text-base">Insights</CardTitle>
        </div>
        <CardDescription>
          Análise automática baseada em regras sobre o período selecionado. Análise por IA em breve.
        </CardDescription>
      </CardHeader>
      <CardContent>
        {loading ? (
          <div className="space-y-3">
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-5/6" />
            <Skeleton className="h-4 w-4/6" />
          </div>
        ) : error && insights.length === 0 ? (
          <EmptyState size="sm" variant="error" title="Não foi possível gerar insights para este período." />
        ) : insights.length === 0 ? (
          <EmptyState
            size="sm"
            title="Sem dados suficientes para gerar insights."
            description="Insights aparecem aqui assim que houver pedidos no período selecionado."
          />
        ) : (
          <ul className="space-y-3">
            {insights.map((insight, i) => (
              <li key={i} className="flex items-start gap-2.5 text-sm">
                <insight.icon className={`h-4 w-4 mt-0.5 shrink-0 ${TONE_CLASSES[insight.tone]}`} />
                <span className="text-foreground/90">{insight.text}</span>
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}
