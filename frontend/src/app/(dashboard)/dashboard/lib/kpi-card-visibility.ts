"use client"

import { useCallback, useEffect, useState } from "react"

export const KPI_CARD_STORAGE_KEY = "dashboardKpiCardsHidden"

export const PERIOD_KPI_CARDS = [
  { id: "period-revenue", label: "Receita" },
  { id: "period-orders", label: "Pedidos" },
  { id: "period-ticket", label: "Ticket Médio" },
  { id: "period-new-clients", label: "Novos Clientes" },
] as const

export const MONTH_KPI_CARDS = [
  { id: "month-active-clients", label: "Clientes Ativos" },
  { id: "month-conversion", label: "Taxa de Conversão" },
  { id: "month-delivered", label: "Pedidos Concluídos" },
  { id: "month-canceled", label: "Pedidos Cancelados" },
  { id: "month-rating", label: "Avaliação Média" },
] as const

export const MONTH_DERIVED_KPI_CARDS = [
  { id: "month-projected", label: "Receita Projetada" },
  { id: "month-recurring", label: "Clientes Recorrentes" },
  { id: "month-service-time", label: "Tempo Médio de Atendimento" },
] as const

export type KpiCardOption = { id: string; label: string }

export function parseHiddenKpiCards(raw: string | null): Set<string> {
  if (!raw) return new Set()
  try {
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed)) return new Set()
    return new Set(parsed.filter((id): id is string => typeof id === "string"))
  } catch {
    return new Set()
  }
}

export function serializeHiddenKpiCards(hidden: Set<string>): string {
  return JSON.stringify([...hidden])
}

function persistHidden(next: Set<string>) {
  localStorage.setItem(KPI_CARD_STORAGE_KEY, serializeHiddenKpiCards(next))
  return next
}

export function useKpiCardVisibility() {
  const [hidden, setHidden] = useState<Set<string>>(new Set())

  useEffect(() => {
    setHidden(parseHiddenKpiCards(localStorage.getItem(KPI_CARD_STORAGE_KEY)))
  }, [])

  const isVisible = useCallback((id: string) => !hidden.has(id), [hidden])

  const setVisible = useCallback((id: string, visible: boolean) => {
    setHidden((current) => {
      const next = new Set(current)
      if (visible) {
        next.delete(id)
      } else {
        next.add(id)
      }
      return persistHidden(next)
    })
  }, [])

  const hide = useCallback((id: string) => setVisible(id, false), [setVisible])

  const showAll = useCallback((cards: readonly KpiCardOption[]) => {
    setHidden((current) => {
      const next = new Set(current)
      for (const card of cards) {
        next.delete(card.id)
      }
      return persistHidden(next)
    })
  }, [])

  return { hidden, isVisible, setVisible, hide, showAll }
}
