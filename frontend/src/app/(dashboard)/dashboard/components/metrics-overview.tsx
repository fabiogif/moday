"use client"

import { useEffect, useState } from "react"
import {
  TrendingUp,
  TrendingDown,
  DollarSign,
  Users,
  ShoppingCart,
  BarChart3,
  Receipt,
  UserPlus,
  CheckCircle2,
  XCircle,
  Star,
  Clock,
  Repeat,
  TrendingUp as ProjectedIcon,
  EyeOff,
  type LucideIcon,
} from "lucide-react"
import { Card, CardAction, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Skeleton } from "@/components/ui/skeleton"
import { EmptyState } from "@/components/ui/empty-state"
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip"
import { useAuth } from "@/contexts/auth-context"
import { useRealtimeDashboard } from "@/hooks/use-realtime-dashboard"
import { useAuthenticatedApi, useAuthenticatedOrderStats, useAuthenticatedReviewStats, useAuthenticatedClientStats } from "@/hooks/use-authenticated-api"
import { useSalesPerformance } from "@/hooks/use-sales-performance"
import { apiClient } from "@/lib/api-client"
import { useDashboardFilters } from "../context/dashboard-filters-context"
import { KpiSectionHeader } from "./kpi-section-header"
import {
  MONTH_DERIVED_KPI_CARDS,
  MONTH_KPI_CARDS,
  PERIOD_KPI_CARDS,
  useKpiCardVisibility,
} from "../lib/kpi-card-visibility"
import { Area, AreaChart, ResponsiveContainer } from "recharts"

interface MetricData {
  value: number
  formatted?: string
  growth: number
  trend: "up" | "down"
  subtitle: string
  description: string
  chart_data?: Array<{ month: string; revenue: number }>
}

interface MetricsData {
  total_revenue?: MetricData
  active_clients?: MetricData
  total_orders?: MetricData
  conversion_rate?: MetricData
}

interface StatTrio {
  current: number
  previous: number
  growth: number
}

interface OrderStats {
  delivered_orders?: StatTrio
  canceled_orders?: StatTrio
  average_service_time_minutes?: {
    current: number | null
    previous: number | null
    growth: number
  }
  projected_revenue?: StatTrio
}

interface ClientStats {
  recurring_clients_rate?: StatTrio
}

interface ReviewStats {
  average_rating: number
  total: number
}

function MiniSparkline({ data, trend }: { data?: Array<{ month: string; revenue: number }>; trend: "up" | "down" }) {
  if (!data || data.length < 2) return null
  const color = trend === "up" ? "var(--chart-2)" : "var(--chart-5)"
  return (
    <div className="h-10 w-full opacity-60">
      <ResponsiveContainer width="100%" height="100%">
        <AreaChart data={data} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
          <defs>
            <linearGradient id={`sparkGrad-${trend}`} x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={color} stopOpacity={0.3} />
              <stop offset="95%" stopColor={color} stopOpacity={0} />
            </linearGradient>
          </defs>
          <Area
            type="monotone"
            dataKey="revenue"
            stroke={color}
            strokeWidth={1.5}
            fill={`url(#sparkGrad-${trend})`}
            dot={false}
            isAnimationActive={false}
          />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  )
}

interface KpiCardProps {
  title: string
  value: string
  change?: string
  trend?: "up" | "down"
  icon: LucideIcon
  iconClassName: string
  footer?: string
  subfooter?: string
  chartData?: Array<{ month: string; revenue: number }>
  tooltip: string
  liveIndicator?: boolean
  onHide?: () => void
}

function KpiCard({ title, value, change, trend, icon: Icon, iconClassName, footer, subfooter, chartData, tooltip, liveIndicator, onHide }: KpiCardProps) {
  const TrendIcon = trend === "up" ? TrendingUp : TrendingDown
  const trendColor =
    trend === "up"
      ? "text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-400"
      : "text-rose-600 border-rose-200 bg-rose-50 dark:bg-rose-950/30 dark:border-rose-800 dark:text-rose-400"

  return (
    <Card className="group relative cursor-default overflow-hidden transition-shadow hover:shadow-md">
      {liveIndicator && (
        <span className="absolute top-3 right-3 flex h-2 w-2">
          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
          <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500" />
        </span>
      )}
      <CardHeader className="pb-2">
        <div className="flex items-center justify-between">
          <Tooltip>
            <TooltipTrigger asChild>
              <CardDescription className="text-sm font-medium underline decoration-dotted underline-offset-4">
                {title}
              </CardDescription>
            </TooltipTrigger>
            <TooltipContent dismissible>
              <p className="max-w-56">{tooltip}</p>
            </TooltipContent>
          </Tooltip>
          <div className="flex items-center gap-1">
            {onHide && (
              <Button
                type="button"
                variant="ghost"
                size="icon"
                className="h-7 w-7 text-muted-foreground opacity-0 group-hover:opacity-100 focus-visible:opacity-100"
                onClick={onHide}
                aria-label={`Ocultar card ${title}`}
                title={`Ocultar ${title}`}
              >
                <EyeOff className="h-3.5 w-3.5" />
              </Button>
            )}
            <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${iconClassName}`}>
              <Icon className="h-4 w-4" />
            </div>
          </div>
        </div>
        <CardTitle className="text-2xl font-bold tabular-nums @[250px]/card:text-3xl mt-1">
          {value}
        </CardTitle>
        {change && trend && (
          <CardAction>
            <Badge variant="outline" className={`text-xs font-medium ${trendColor}`}>
              <TrendIcon className="h-3 w-3 mr-1" />
              {change}
            </Badge>
          </CardAction>
        )}
      </CardHeader>

      {chartData && chartData.length > 1 && trend && (
        <div className="px-6 pb-0">
          <MiniSparkline data={chartData} trend={trend} />
        </div>
      )}

      {(footer || subfooter) && (
        <CardFooter className="flex-col items-start gap-1 text-sm pt-2">
          {footer && <div className="line-clamp-1 flex gap-2 font-medium text-foreground/80">{footer}</div>}
          {subfooter && <div className="text-xs text-muted-foreground">{subfooter}</div>}
        </CardFooter>
      )}
    </Card>
  )
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(value)
}

function formatGrowth(growth: number) {
  return `${growth >= 0 ? "+" : ""}${growth.toFixed(1)}%`
}

function formatDuration(minutes: number) {
  if (minutes < 60) {
    return `${Math.round(minutes)}min`
  }
  const hours = Math.floor(minutes / 60)
  const remainingMinutes = Math.round(minutes % 60)
  return remainingMinutes > 0 ? `${hours}h ${remainingMinutes}min` : `${hours}h`
}

function hasStatTrio(value: unknown): value is StatTrio {
  return typeof value === "object" && value !== null && typeof (value as StatTrio).current === "number"
}

export function MetricsOverview() {
  const { user, isAuthenticated, isLoading: authLoading, token } = useAuth()
  const [metrics, setMetrics] = useState<MetricsData | null>(null)
  const { dateRange } = useDashboardFilters()
  const { hidden, isVisible, setVisible, hide, showAll } = useKpiCardVisibility()

  const { data: metricsData, loading, error, refetch } = useAuthenticatedApi<MetricsData>(
    "/api/dashboard/metrics",
    { immediate: false }
  )

  const {
    data: salesPerformance,
    loading: salesLoading,
    error: salesError,
  } = useSalesPerformance({
    start_date: dateRange.start_date,
    end_date: dateRange.end_date,
    days: dateRange.days,
  })

  const { data: orderStats, loading: orderStatsLoading } = useAuthenticatedOrderStats() as {
    data: OrderStats | null
    loading: boolean
  }
  const { data: reviewStats, loading: reviewStatsLoading } = useAuthenticatedReviewStats() as {
    data: ReviewStats | null
    loading: boolean
  }
  const { data: clientStats, loading: clientStatsLoading } = useAuthenticatedClientStats() as {
    data: ClientStats | null
    loading: boolean
  }

  useEffect(() => {
    if (token) {
      apiClient.setToken(token)
      apiClient.reloadToken()
    }
  }, [token])

  const { isConnected } = useRealtimeDashboard({
    tenantId: user?.tenant_id ? parseInt(user.tenant_id) : 0,
    enabled: isAuthenticated && !!user?.tenant_id,
    onMetricsUpdate: () => {
      refetch()
    },
  })

  useEffect(() => {
    if (!authLoading && isAuthenticated && user?.tenant_id) {
      refetch()
    }
  }, [authLoading, isAuthenticated, user?.tenant_id, refetch])

  useEffect(() => {
    if (metricsData) {
      setMetrics(metricsData)
    }
  }, [metricsData])

  if (error && !metrics) {
    return (
      <Card>
        <CardHeader>
          <CardDescription className="text-sm font-medium">Métricas</CardDescription>
        </CardHeader>
        <CardFooter>
          <EmptyState
            variant="error"
            title="Não foi possível carregar as métricas do dashboard."
            action={{ label: "Tentar novamente", onClick: () => refetch() }}
          />
        </CardFooter>
      </Card>
    )
  }

  if (authLoading || loading || !metrics) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 @5xl:grid-cols-4">
        {[1, 2, 3, 4].map((i) => (
          <Card key={i}>
            <CardHeader>
              <Skeleton className="h-4 w-24 mb-2" />
              <Skeleton className="h-8 w-32 mb-2" />
              <Skeleton className="h-6 w-20" />
            </CardHeader>
            <CardFooter className="flex-col items-start gap-1.5 text-sm">
              <Skeleton className="h-4 w-full" />
              <Skeleton className="h-3 w-3/4" />
            </CardFooter>
          </Card>
        ))}
      </div>
    )
  }

  const indicators = salesPerformance?.indicators
  const sectionHeaderProps = { hidden, onSetVisible: setVisible, onShowAll: showAll }

  return (
    <div className="space-y-6">
      {/* Grupo 1 — reage ao seletor de período global */}
      <div className="space-y-2">
        <KpiSectionHeader
          label={`Período selecionado · ${dateRange.label}`}
          cards={PERIOD_KPI_CARDS}
          {...sectionHeaderProps}
        />
        {salesError && !indicators ? (
          <EmptyState
            variant="error"
            size="sm"
            title="Não foi possível carregar os indicadores do período."
          />
        ) : salesLoading || !indicators ? (
          <div className="grid gap-4 sm:grid-cols-2 @5xl:grid-cols-4">
            {[1, 2, 3, 4].map((i) => (
              <Card key={i}>
                <CardHeader>
                  <Skeleton className="h-4 w-24 mb-2" />
                  <Skeleton className="h-8 w-32" />
                </CardHeader>
              </Card>
            ))}
          </div>
        ) : PERIOD_KPI_CARDS.every((card) => !isVisible(card.id)) ? (
          <p className="text-sm text-muted-foreground">Nenhum card visível nesta seção.</p>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 @5xl:grid-cols-4">
            {isVisible("period-revenue") && (
              <KpiCard
                title="Receita"
                value={hasStatTrio(indicators.total_sales_value) ? formatCurrency(indicators.total_sales_value.current) : "—"}
                change={hasStatTrio(indicators.total_sales_value) ? formatGrowth(indicators.total_sales_value.growth) : undefined}
                trend={hasStatTrio(indicators.total_sales_value) ? (indicators.total_sales_value.growth >= 0 ? "up" : "down") : undefined}
                icon={DollarSign}
                iconClassName="text-primary bg-primary/10"
                tooltip="Soma do valor dos pedidos no período selecionado, comparada ao período anterior de mesma duração."
                onHide={() => hide("period-revenue")}
              />
            )}
            {isVisible("period-orders") && (
              <KpiCard
                title="Pedidos"
                value={hasStatTrio(indicators.total_sales) ? indicators.total_sales.current.toString() : "—"}
                change={hasStatTrio(indicators.total_sales) ? formatGrowth(indicators.total_sales.growth) : undefined}
                trend={hasStatTrio(indicators.total_sales) ? (indicators.total_sales.growth >= 0 ? "up" : "down") : undefined}
                icon={ShoppingCart}
                iconClassName="text-violet-600 bg-violet-50 dark:bg-violet-950 dark:text-violet-400"
                tooltip="Total de pedidos no período selecionado, comparado ao período anterior de mesma duração."
                onHide={() => hide("period-orders")}
              />
            )}
            {isVisible("period-ticket") && (
              <KpiCard
                title="Ticket Médio"
                value={hasStatTrio(indicators.average_ticket) ? formatCurrency(indicators.average_ticket.current) : "—"}
                change={hasStatTrio(indicators.average_ticket) ? formatGrowth(indicators.average_ticket.growth) : undefined}
                trend={hasStatTrio(indicators.average_ticket) ? (indicators.average_ticket.growth >= 0 ? "up" : "down") : undefined}
                icon={Receipt}
                iconClassName="text-amber-600 bg-amber-50 dark:bg-amber-950 dark:text-amber-400"
                tooltip="Valor médio por pedido (receita ÷ pedidos) no período selecionado."
                onHide={() => hide("period-ticket")}
              />
            )}
            {isVisible("period-new-clients") && (
              <KpiCard
                title="Novos Clientes"
                value={hasStatTrio(indicators.new_clients) ? indicators.new_clients.current.toString() : "—"}
                change={hasStatTrio(indicators.new_clients) ? formatGrowth(indicators.new_clients.growth) : undefined}
                trend={hasStatTrio(indicators.new_clients) ? (indicators.new_clients.growth >= 0 ? "up" : "down") : undefined}
                icon={UserPlus}
                iconClassName="text-emerald-600 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-400"
                tooltip="Clientes que fizeram o primeiro pedido dentro do período selecionado."
                onHide={() => hide("period-new-clients")}
              />
            )}
          </div>
        )}
      </div>

      {/* Grupo 2 — fixo (mês atual), independente do seletor */}
      <div className="space-y-2">
        <KpiSectionHeader
          label="Este mês (vs. mês anterior)"
          cards={MONTH_KPI_CARDS}
          {...sectionHeaderProps}
        />
        {MONTH_KPI_CARDS.every((card) => !isVisible(card.id)) ? (
          <p className="text-sm text-muted-foreground">Nenhum card visível nesta seção.</p>
        ) : (
        <div className="grid gap-4 sm:grid-cols-2 @5xl:grid-cols-4">
          {isVisible("month-active-clients") && (
            <KpiCard
              title="Clientes Ativos"
              value={metrics.active_clients ? metrics.active_clients.value.toString() : "—"}
              change={metrics.active_clients ? formatGrowth(metrics.active_clients.growth) : undefined}
              trend={metrics.active_clients?.trend}
              icon={Users}
              iconClassName="text-emerald-600 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-400"
              footer={metrics.active_clients?.subtitle}
              subfooter={metrics.active_clients?.description}
              tooltip="Clientes com pelo menos um pedido no mês atual."
              liveIndicator={isConnected}
              onHide={() => hide("month-active-clients")}
            />
          )}
          {isVisible("month-conversion") && (
            <KpiCard
              title="Taxa de Conversão"
              value={
                metrics.conversion_rate
                  ? metrics.conversion_rate.formatted || `${metrics.conversion_rate.value.toFixed(1)}%`
                  : "—"
              }
              change={metrics.conversion_rate ? formatGrowth(metrics.conversion_rate.growth) : undefined}
              trend={metrics.conversion_rate?.trend}
              icon={BarChart3}
              iconClassName="text-primary bg-primary/10"
              footer={metrics.conversion_rate?.subtitle}
              subfooter={metrics.conversion_rate?.description}
              tooltip="Percentual de visitas/pedidos iniciados que viraram pedidos concluídos, no mês atual."
              onHide={() => hide("month-conversion")}
            />
          )}
          {isVisible("month-delivered") && (orderStatsLoading || !orderStats ? (
            <Card><CardHeader><Skeleton className="h-4 w-24 mb-2" /><Skeleton className="h-8 w-16" /></CardHeader></Card>
          ) : (
            <KpiCard
              title="Pedidos Concluídos"
              value={hasStatTrio(orderStats.delivered_orders) ? orderStats.delivered_orders.current.toString() : "—"}
              change={hasStatTrio(orderStats.delivered_orders) ? formatGrowth(orderStats.delivered_orders.growth) : undefined}
              trend={hasStatTrio(orderStats.delivered_orders) ? (orderStats.delivered_orders.growth >= 0 ? "up" : "down") : undefined}
              icon={CheckCircle2}
              iconClassName="text-emerald-600 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-400"
              tooltip="Pedidos com status Concluído no mês atual."
              onHide={() => hide("month-delivered")}
            />
          ))}
          {isVisible("month-canceled") && (orderStatsLoading || !orderStats ? (
            <Card><CardHeader><Skeleton className="h-4 w-24 mb-2" /><Skeleton className="h-8 w-16" /></CardHeader></Card>
          ) : (
            <KpiCard
              title="Pedidos Cancelados"
              value={hasStatTrio(orderStats.canceled_orders) ? orderStats.canceled_orders.current.toString() : "—"}
              change={hasStatTrio(orderStats.canceled_orders) ? formatGrowth(orderStats.canceled_orders.growth) : undefined}
              trend={hasStatTrio(orderStats.canceled_orders) ? (orderStats.canceled_orders.growth >= 0 ? "down" : "up") : undefined}
              icon={XCircle}
              iconClassName="text-rose-600 bg-rose-50 dark:bg-rose-950 dark:text-rose-400"
              tooltip="Pedidos com status Cancelado no mês atual. Uma redução (verde) é o resultado desejado."
              onHide={() => hide("month-canceled")}
            />
          ))}
          {isVisible("month-rating") && (reviewStatsLoading || !reviewStats ? (
            <Card><CardHeader><Skeleton className="h-4 w-24 mb-2" /><Skeleton className="h-8 w-16" /></CardHeader></Card>
          ) : (
            <KpiCard
              title="Avaliação Média"
              value={reviewStats.total > 0 ? reviewStats.average_rating.toFixed(1) : "—"}
              icon={Star}
              iconClassName="text-amber-600 bg-amber-50 dark:bg-amber-950 dark:text-amber-400"
              footer={reviewStats.total > 0 ? `${reviewStats.total} avaliações` : undefined}
              subfooter={reviewStats.total === 0 ? "Ainda sem avaliações" : undefined}
              tooltip="Nota média das avaliações aprovadas de clientes."
              onHide={() => hide("month-rating")}
            />
          ))}
        </div>
        )}
      </div>

      {/* Grupo 3 — métricas derivadas, também no recorte "mês atual vs. anterior" */}
      <div className="space-y-2">
        <KpiSectionHeader
          label="Este mês (vs. mês anterior)"
          cards={MONTH_DERIVED_KPI_CARDS}
          {...sectionHeaderProps}
        />
        {MONTH_DERIVED_KPI_CARDS.every((card) => !isVisible(card.id)) ? (
          <p className="text-sm text-muted-foreground">Nenhum card visível nesta seção.</p>
        ) : (
        <div className="grid gap-4 sm:grid-cols-2 @5xl:grid-cols-3">
          {isVisible("month-projected") && (orderStatsLoading || !orderStats ? (
            <Card><CardHeader><Skeleton className="h-4 w-24 mb-2" /><Skeleton className="h-8 w-16" /></CardHeader></Card>
          ) : (
            <KpiCard
              title="Receita Projetada"
              value={hasStatTrio(orderStats.projected_revenue) ? formatCurrency(orderStats.projected_revenue.current) : "—"}
              change={hasStatTrio(orderStats.projected_revenue) ? formatGrowth(orderStats.projected_revenue.growth) : undefined}
              trend={hasStatTrio(orderStats.projected_revenue) ? (orderStats.projected_revenue.growth >= 0 ? "up" : "down") : undefined}
              icon={ProjectedIcon}
              iconClassName="text-primary bg-primary/10"
              subfooter="Projeção linear com base na receita do mês até agora"
              tooltip="Projeção de fechamento do mês, extrapolando a receita acumulada até hoje. Menos precisa no início do mês."
              onHide={() => hide("month-projected")}
            />
          ))}

          {isVisible("month-recurring") && (clientStatsLoading || !clientStats ? (
            <Card><CardHeader><Skeleton className="h-4 w-24 mb-2" /><Skeleton className="h-8 w-16" /></CardHeader></Card>
          ) : (
            <KpiCard
              title="Clientes Recorrentes"
              value={hasStatTrio(clientStats.recurring_clients_rate) ? `${clientStats.recurring_clients_rate.current.toFixed(1)}%` : "—"}
              change={hasStatTrio(clientStats.recurring_clients_rate) ? formatGrowth(clientStats.recurring_clients_rate.growth) : undefined}
              trend={hasStatTrio(clientStats.recurring_clients_rate) ? (clientStats.recurring_clients_rate.growth >= 0 ? "up" : "down") : undefined}
              icon={Repeat}
              iconClassName="text-violet-600 bg-violet-50 dark:bg-violet-950 dark:text-violet-400"
              tooltip="Percentual de clientes (dentre os que já fizeram pedido) com mais de um pedido, considerando todo o histórico."
              onHide={() => hide("month-recurring")}
            />
          ))}

          {isVisible("month-service-time") && (orderStatsLoading || !orderStats ? (
            <Card><CardHeader><Skeleton className="h-4 w-24 mb-2" /><Skeleton className="h-8 w-16" /></CardHeader></Card>
          ) : orderStats.average_service_time_minutes?.current == null ? (
            <KpiCard
              title="Tempo Médio de Atendimento"
              value="—"
              icon={Clock}
              iconClassName="text-amber-600 bg-amber-50 dark:bg-amber-950 dark:text-amber-400"
              subfooter="Ainda sem pedidos concluídos neste período"
              tooltip="Tempo médio entre a criação e a conclusão do pedido, no mês atual."
              onHide={() => hide("month-service-time")}
            />
          ) : (
            <KpiCard
              title="Tempo Médio de Atendimento"
              value={formatDuration(orderStats.average_service_time_minutes.current)}
              change={formatGrowth(orderStats.average_service_time_minutes.growth)}
              trend={orderStats.average_service_time_minutes.growth >= 0 ? "down" : "up"}
              icon={Clock}
              iconClassName="text-amber-600 bg-amber-50 dark:bg-amber-950 dark:text-amber-400"
              tooltip="Tempo médio entre a criação e a conclusão do pedido, no mês atual. Uma redução (verde) é o resultado desejado."
              onHide={() => hide("month-service-time")}
            />
          ))}
        </div>
        )}
      </div>
    </div>
  )
}
