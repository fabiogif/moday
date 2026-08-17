"use client"

import { useState } from "react"
import { SalesPerformanceHeader } from "./components/sales-performance-header"
import { SalesPerformanceIndicators } from "./components/sales-performance-indicators"
import { SalesByHourChart } from "./components/sales-by-hour-chart"
import { SalesByDayChart } from "./components/sales-by-day-chart"
import { SalesByPaymentMethodChart } from "./components/sales-by-payment-method-chart"
import { SalesPerformanceGlossary } from "./components/sales-performance-glossary"
import { SalesPerformanceCallToAction } from "./components/sales-performance-call-to-action"
import { useSalesPerformance, useRefreshSalesPerformance, useExportSalesPerformance } from "@/hooks/use-sales-performance"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Skeleton } from "@/components/ui/skeleton"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { AlertCircle } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Download, RefreshCw } from "lucide-react"
import { useToast } from "@/hooks/use-toast"

export default function SalesPerformancePage() {
  const [days, setDays] = useState(7)
  const [startDate, setStartDate] = useState<string | undefined>()
  const [endDate, setEndDate] = useState<string | undefined>()

  const { data, loading, error, refetch } = useSalesPerformance({
    days,
    start_date: startDate,
    end_date: endDate
  })

  const { refresh, loading: refreshing } = useRefreshSalesPerformance()
  const { exportData, loading: exporting } = useExportSalesPerformance()
  const { toast } = useToast()

  const handleRefresh = async () => {
    try {
      await refresh()
      await refetch()
      toast({
        title: "Dados atualizados",
        description: "Os dados de desempenho/vendas foram atualizados com sucesso.",
      })
    } catch (error) {
      toast({
        title: "Erro ao atualizar",
        description: "Não foi possível atualizar os dados. Tente novamente.",
        variant: "destructive",
      })
    }
  }

  const handleExport = async () => {
    try {
      const exportedData = await exportData({
        days,
        start_date: startDate,
        end_date: endDate
      })
      
      if (exportedData) {
        // Criar arquivo JSON para download
        const dataStr = JSON.stringify(exportedData, null, 2)
        const dataBlob = new Blob([dataStr], { type: 'application/json' })
        const url = URL.createObjectURL(dataBlob)
        const link = document.createElement('a')
        link.href = url
        link.download = `sales-performance-${new Date().toISOString().split('T')[0]}.json`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)

        toast({
          title: "Dados exportados",
          description: "Os dados de desempenho/vendas foram exportados com sucesso.",
        })
      }
    } catch (error) {
      toast({
        title: "Erro ao exportar",
        description: "Não foi possível exportar os dados. Tente novamente.",
        variant: "destructive",
      })
    }
  }

  if (loading && !data) {
    return (
      <div className="flex-1 space-y-6 px-6 pt-0">
        <div className="flex flex-col gap-4">
          <Skeleton className="h-10 w-64" />
          <Skeleton className="h-6 w-96" />
        </div>
        <div className="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
          {[1, 2, 3, 4].map((i) => (
            <Card key={i}>
              <CardHeader>
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-8 w-32" />
              </CardHeader>
              <CardContent>
                <Skeleton className="h-4 w-full" />
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex-1 space-y-6 px-6 pt-0">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            {error || "Erro ao carregar dados de desempenho/vendas. Tente novamente."}
          </AlertDescription>
        </Alert>
      </div>
    )
  }

  if (!data) {
    return null
  }

  return (
    <div className="flex-1 space-y-6 px-6 pt-0">
      {/* Header */}
      <SalesPerformanceHeader
        days={days}
        startDate={startDate}
        endDate={endDate}
        onDaysChange={setDays}
        onStartDateChange={setStartDate}
        onEndDateChange={setEndDate}
        onRefresh={handleRefresh}
        onExport={handleExport}
        refreshing={refreshing}
        exporting={exporting}
      />

      {/* Indicators */}
      <SalesPerformanceIndicators data={data.indicators} />

      {/* Charts */}
      <div className="grid gap-6 grid-cols-1 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Vendas por Horário</CardTitle>
            <CardDescription>
              Distribuição de vendas ao longo do dia
              {data.best_hour && (
                <span className="block mt-1 text-sm font-medium text-primary">
                  Melhor horário: {data.best_hour.hour_label} ({data.best_hour.count} vendas)
                </span>
              )}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <SalesByHourChart data={data.sales_by_hour} />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Vendas por Dia da Semana</CardTitle>
            <CardDescription>
              Distribuição de vendas por dia da semana
              {data.best_day && (
                <span className="block mt-1 text-sm font-medium text-primary">
                  Melhor dia: {data.best_day.day_name} ({data.best_day.count} vendas)
                </span>
              )}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <SalesByDayChart data={data.sales_by_day} />
          </CardContent>
        </Card>
      </div>

      {/* Payment Methods Chart */}
      <Card>
        <CardHeader>
          <CardTitle>Vendas por Forma de Pagamento</CardTitle>
          <CardDescription>
            Distribuição de vendas por forma de pagamento
          </CardDescription>
        </CardHeader>
        <CardContent>
          <SalesByPaymentMethodChart data={data.sales_by_payment_method} />
        </CardContent>
      </Card>

      {/* Glossary */}
      <SalesPerformanceGlossary />

      {/* Call to Action */}
      <SalesPerformanceCallToAction />
    </div>
  )
}

