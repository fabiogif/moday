"use client"

import { createContext, useContext, useMemo, useState, type ReactNode } from "react"
import { resolvePeriod, type CustomRange, type DashboardPeriod, type ResolvedDateRange } from "../lib/resolve-period"

interface DashboardFiltersContextValue {
  period: DashboardPeriod
  setPeriod: (period: DashboardPeriod) => void
  customRange?: CustomRange
  setCustomRange: (range: CustomRange | undefined) => void
  dateRange: ResolvedDateRange
}

const DashboardFiltersContext = createContext<DashboardFiltersContextValue | undefined>(undefined)

export function DashboardFiltersProvider({ children }: { children: ReactNode }) {
  const [period, setPeriod] = useState<DashboardPeriod>("30d")
  const [customRange, setCustomRange] = useState<CustomRange | undefined>(undefined)

  const dateRange = useMemo(() => resolvePeriod(period, customRange), [period, customRange])

  const value = useMemo<DashboardFiltersContextValue>(
    () => ({ period, setPeriod, customRange, setCustomRange, dateRange }),
    [period, customRange, dateRange]
  )

  return <DashboardFiltersContext.Provider value={value}>{children}</DashboardFiltersContext.Provider>
}

export function useDashboardFilters() {
  const ctx = useContext(DashboardFiltersContext)
  if (!ctx) {
    throw new Error("useDashboardFilters deve ser usado dentro de DashboardFiltersProvider")
  }
  return ctx
}
