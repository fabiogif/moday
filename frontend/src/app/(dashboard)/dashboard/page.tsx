"use client"

import dynamic from "next/dynamic"
import { Skeleton } from "@/components/ui/skeleton"
import { MetricsOverview } from "./components/metrics-overview"
import { RecentTransactions } from "./components/recent-transactions"
import { TopProducts } from "./components/top-products"
import { QuickActions } from "./components/quick-actions"
import { RecentReviewsCard } from "./components/recent-reviews-card"
import { DashboardGreeting } from "./components/dashboard-greeting"
import { GlobalFilters } from "./components/global-filters"
import { InsightsCard } from "./components/insights-card"
import { GoalsCard } from "./components/goals-card"
import { FinancialSummary } from "./components/financial-summary"
import { RecentCustomers } from "./components/recent-customers"
import { DashboardFiltersProvider } from "./context/dashboard-filters-context"

// Gráficos (Recharts) carregados sob demanda — não bloqueiam o pintar
// inicial do header/KPIs, que é o que o usuário precisa ver primeiro.
const ChartSkeleton = () => <Skeleton className="h-[300px] w-full rounded-lg" />
const RevenueChart = dynamic(() => import("./components/revenue-chart").then(m => m.RevenueChart), {
  ssr: false,
  loading: () => <Skeleton className="h-[350px] w-full rounded-lg" />,
})
const OrdersVolumeChart = dynamic(() => import("./components/orders-volume-chart").then(m => m.OrdersVolumeChart), {
  ssr: false,
  loading: ChartSkeleton,
})
const CustomersChart = dynamic(() => import("./components/customers-chart").then(m => m.CustomersChart), {
  ssr: false,
  loading: ChartSkeleton,
})

export default function Dashboard() {
  return (
    <DashboardFiltersProvider>
    <div className="flex-1 space-y-6 px-6 pt-0">
        <div className="flex md:flex-row flex-col md:items-start justify-between gap-4 md:gap-6">
          <DashboardGreeting />
          <div className="flex flex-col items-start gap-2 md:items-end">
            <GlobalFilters />
            <QuickActions />
          </div>
        </div>

        {/* Main Dashboard Grid */}
        <div className="@container/main space-y-6">
          {/* Top Row - Key Metrics with WebSocket */}
          <MetricsOverview />

          {/* Insights e Metas */}
          <div className="grid gap-6 grid-cols-1 @5xl:grid-cols-3">
            <div className="@5xl:col-span-2">
              <InsightsCard />
            </div>
            <GoalsCard />
          </div>

          {/* Sales Performance Chart */}
          <div className="grid gap-6 grid-cols-1">
            <RevenueChart />
          </div>

          {/* Orders Volume and Customers Charts */}
          <div className="grid gap-6 grid-cols-1 @5xl:grid-cols-2">
            <OrdersVolumeChart />
            <CustomersChart />
          </div>

          {/* Financeiro */}
          <div className="grid gap-6 grid-cols-1">
            <FinancialSummary />
          </div>

          {/* Produtos mais vendidos */}
          <div className="grid gap-6 grid-cols-1">
            <TopProducts />
          </div>

          {/* Clientes recentes e Pedidos recentes */}
          <div className="grid gap-6 grid-cols-1 @5xl:grid-cols-2">
            <RecentCustomers />
            <RecentTransactions />
          </div>

          {/* Avaliações Recentes */}
          <div className="grid gap-6 grid-cols-1">
            <RecentReviewsCard limit={5} />
          </div>
        </div>
      </div>
    </DashboardFiltersProvider>
  )
}
