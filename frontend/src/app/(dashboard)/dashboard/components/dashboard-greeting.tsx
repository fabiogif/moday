"use client"

import { useAuth } from "@/contexts/auth-context"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"
import { Skeleton } from "@/components/ui/skeleton"

interface MetricData {
  value: number
  formatted?: string
}

interface MetricsData {
  total_revenue: MetricData
  active_clients: MetricData
  total_orders: MetricData
}

function getGreeting() {
  const hour = new Date().getHours()
  if (hour < 12) return "Bom dia"
  if (hour < 18) return "Boa tarde"
  return "Boa noite"
}

function getFormattedDate() {
  return new Intl.DateTimeFormat("pt-BR", {
    weekday: "long",
    day: "numeric",
    month: "long",
  }).format(new Date())
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(value)
}

export function DashboardGreeting() {
  const { user } = useAuth()
  const firstName = user?.name?.split(" ")[0] ?? "bem-vindo"
  const greeting = getGreeting()
  const date = getFormattedDate()

  // Reaproveita o mesmo endpoint que o KPI Grid já busca — a chave de cache
  // (endpoint + params) é idêntica, então a rede não é duplicada quando ambos
  // ficam "quentes" no cache do useAuthenticatedApi.
  const { data: metrics, loading } = useAuthenticatedApi<MetricsData>("/api/dashboard/metrics")

  const revenue = metrics?.total_revenue
  const orders = metrics?.total_orders
  const clients = metrics?.active_clients
  const avgTicket =
    orders && orders.value > 0 && revenue ? revenue.value / orders.value : undefined

  return (
    <div className="flex flex-col gap-2">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">
          {greeting}, {firstName}! 👋
        </h1>
        <p className="text-muted-foreground capitalize">{date}</p>
      </div>

      {loading ? (
        <Skeleton className="h-4 w-64" />
      ) : revenue && orders && clients ? (
        <p className="text-sm text-muted-foreground">
          Hoje você possui{" "}
          <span className="font-medium text-foreground">
            {revenue.formatted ?? formatCurrency(revenue.value)} em receita
          </span>
          , <span className="font-medium text-foreground">{orders.value} pedidos</span> e{" "}
          <span className="font-medium text-foreground">{clients.value} clientes ativos</span>
          {avgTicket !== undefined && (
            <>
              {" "}
              — ticket médio de{" "}
              <span className="font-medium text-foreground">{formatCurrency(avgTicket)}</span>
            </>
          )}
          .
        </p>
      ) : null}
    </div>
  )
}
