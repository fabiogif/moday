"use client"

import { CalendarRange } from "lucide-react"
import { format } from "date-fns"
import { ptBR } from "date-fns/locale"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"
import { Calendar } from "@/components/ui/calendar"
import { Button } from "@/components/ui/button"
import { useDashboardFilters } from "../context/dashboard-filters-context"
import type { DashboardPeriod } from "../lib/resolve-period"

const PRESET_OPTIONS: { value: DashboardPeriod; label: string }[] = [
  { value: "today", label: "Hoje" },
  { value: "yesterday", label: "Ontem" },
  { value: "7d", label: "Últimos 7 dias" },
  { value: "30d", label: "Últimos 30 dias" },
  { value: "90d", label: "Últimos 90 dias" },
  { value: "custom", label: "Personalizado" },
]

export function GlobalFilters() {
  const { period, setPeriod, customRange, setCustomRange, dateRange } = useDashboardFilters()

  return (
    <div className="flex items-center gap-2">
      <Select
        value={period}
        onValueChange={(value) => setPeriod(value as DashboardPeriod)}
      >
        <SelectTrigger className="w-[180px]" size="sm">
          <CalendarRange className="h-4 w-4 text-muted-foreground" />
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          {PRESET_OPTIONS.map((option) => (
            <SelectItem key={option.value} value={option.value}>
              {option.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      {period === "custom" && (
        <Popover>
          <PopoverTrigger asChild>
            <Button variant="outline" size="sm" className="font-normal">
              {customRange
                ? `${format(customRange.from, "dd/MM", { locale: ptBR })} – ${format(customRange.to, "dd/MM", { locale: ptBR })}`
                : "Selecionar datas"}
            </Button>
          </PopoverTrigger>
          <PopoverContent className="w-auto p-0" align="start">
            <Calendar
              mode="range"
              selected={customRange ? { from: customRange.from, to: customRange.to } : undefined}
              onSelect={(range) => {
                if (range?.from && range?.to) {
                  setCustomRange({ from: range.from, to: range.to })
                } else if (range?.from) {
                  setCustomRange({ from: range.from, to: range.from })
                }
              }}
              numberOfMonths={2}
              locale={ptBR}
            />
          </PopoverContent>
        </Popover>
      )}

      <span className="hidden text-xs text-muted-foreground md:inline">
        {dateRange.label}
      </span>
    </div>
  )
}
