import { format, subDays } from "date-fns"

export type DashboardPeriod = "today" | "yesterday" | "7d" | "30d" | "90d" | "custom"

export interface CustomRange {
  from: Date
  to: Date
}

export interface ResolvedDateRange {
  start_date: string
  end_date: string
  days: number
  label: string
}

const DATE_FORMAT = "yyyy-MM-dd"

const PERIOD_LABELS: Record<Exclude<DashboardPeriod, "custom">, string> = {
  today: "Hoje",
  yesterday: "Ontem",
  "7d": "Últimos 7 dias",
  "30d": "Últimos 30 dias",
  "90d": "Últimos 90 dias",
}

/**
 * Traduz o período selecionado (preset ou range customizado) para o formato
 * de datas que a API de sales-performance espera (start_date/end_date/days).
 * Função pura — sem dependência de contexto/React, testável isoladamente.
 */
export function resolvePeriod(period: DashboardPeriod, customRange?: CustomRange): ResolvedDateRange {
  const today = new Date()

  if (period === "custom" && customRange) {
    const days = Math.max(
      1,
      Math.round((customRange.to.getTime() - customRange.from.getTime()) / (1000 * 60 * 60 * 24)) + 1
    )
    return {
      start_date: format(customRange.from, DATE_FORMAT),
      end_date: format(customRange.to, DATE_FORMAT),
      days,
      label: `${format(customRange.from, "dd/MM/yyyy")} – ${format(customRange.to, "dd/MM/yyyy")}`,
    }
  }

  if (period === "today") {
    const iso = format(today, DATE_FORMAT)
    return { start_date: iso, end_date: iso, days: 1, label: PERIOD_LABELS.today }
  }

  if (period === "yesterday") {
    const iso = format(subDays(today, 1), DATE_FORMAT)
    return { start_date: iso, end_date: iso, days: 1, label: PERIOD_LABELS.yesterday }
  }

  const daysByPreset: Record<"7d" | "30d" | "90d", number> = { "7d": 7, "30d": 30, "90d": 90 }
  const days = daysByPreset[period as "7d" | "30d" | "90d"] ?? 30

  return {
    start_date: format(subDays(today, days - 1), DATE_FORMAT),
    end_date: format(today, DATE_FORMAT),
    days,
    label: PERIOD_LABELS[period as "7d" | "30d" | "90d"] ?? PERIOD_LABELS["30d"],
  }
}
