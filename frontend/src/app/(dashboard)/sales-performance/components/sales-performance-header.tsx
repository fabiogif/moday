"use client"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { RefreshCw, Download, Calendar } from "lucide-react"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

interface SalesPerformanceHeaderProps {
  days: number
  startDate?: string
  endDate?: string
  onDaysChange: (days: number) => void
  onStartDateChange: (date?: string) => void
  onEndDateChange: (date?: string) => void
  onRefresh: () => void
  onExport: () => void
  refreshing: boolean
  exporting: boolean
}

export function SalesPerformanceHeader({
  days,
  startDate,
  endDate,
  onDaysChange,
  onStartDateChange,
  onEndDateChange,
  onRefresh,
  onExport,
  refreshing,
  exporting
}: SalesPerformanceHeaderProps) {
  return (
    <Card>
      <CardHeader>
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <CardTitle>Desempenho/Vendas</CardTitle>
            <CardDescription>
              Análise detalhada de vendas e indicadores de desempenho da loja
            </CardDescription>
          </div>
          <div className="flex flex-wrap gap-2">
            <Button
              onClick={onRefresh}
              disabled={refreshing}
              variant="outline"
              size="sm"
            >
              <RefreshCw className={`h-4 w-4 mr-2 ${refreshing ? 'animate-spin' : ''}`} />
              Atualizar
            </Button>
            <Button
              onClick={onExport}
              disabled={exporting}
              variant="outline"
              size="sm"
            >
              <Download className={`h-4 w-4 mr-2 ${exporting ? 'animate-spin' : ''}`} />
              Exportar
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="grid gap-4 grid-cols-1 md:grid-cols-3">
          <div className="space-y-2">
            <Label htmlFor="days">Período (dias)</Label>
            <Select 
              value={startDate && endDate ? "custom" : days.toString()} 
              onValueChange={(value) => {
                if (value === "custom") {
                  onDaysChange(0)
                } else {
                  onDaysChange(parseInt(value))
                  onStartDateChange(undefined)
                  onEndDateChange(undefined)
                }
              }}
            >
              <SelectTrigger id="days">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="7">Últimos 7 dias</SelectItem>
                <SelectItem value="15">Últimos 15 dias</SelectItem>
                <SelectItem value="30">Últimos 30 dias</SelectItem>
                <SelectItem value="60">Últimos 60 dias</SelectItem>
                <SelectItem value="90">Últimos 90 dias</SelectItem>
                <SelectItem value="custom">Personalizado</SelectItem>
              </SelectContent>
            </Select>
          </div>
          {(startDate && endDate) || days === 0 ? (
            <>
              <div className="space-y-2">
                <Label htmlFor="start_date">Data de início</Label>
                <Input
                  id="start_date"
                  type="date"
                  value={startDate || ''}
                  onChange={(e) => {
                    onStartDateChange(e.target.value || undefined)
                    if (e.target.value) {
                      onDaysChange(0)
                    }
                  }}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="end_date">Data de fim</Label>
                <Input
                  id="end_date"
                  type="date"
                  value={endDate || ''}
                  onChange={(e) => {
                    onEndDateChange(e.target.value || undefined)
                    if (e.target.value) {
                      onDaysChange(0)
                    }
                  }}
                />
              </div>
            </>
          ) : null}
        </div>
      </CardContent>
    </Card>
  )
}

