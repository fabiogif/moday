import { MetricsOverview } from "./components/metrics-overview"
import { SalesChart } from "./components/sales-chart"
import { RecentTransactions } from "./components/recent-transactions"
import { TopProducts } from "./components/top-products"
import { QuickActions } from "./components/quick-actions"

export default function Dashboard() {
  return (
    <div className="flex-1 space-y-6 px-6 pt-0">
        {/* Enhanced Header */}

        <div className="flex md:flex-row flex-col md:items-center justify-between gap-4 md:gap-6">
          <div className="flex flex-col gap-2">
            <h1 className="text-2xl font-bold tracking-tight">Painel de Controle</h1>
            <p className="text-muted-foreground">
             Sistema de Gestão
            </p>
          </div>
          <QuickActions />
        </div>

        {/* Main Dashboard Grid */}
        <div className="@container/main space-y-6">
          {/* Top Row - Key Metrics with WebSocket */}
          <MetricsOverview />

          {/* Sales Performance Chart */}
          <div className="grid gap-6 grid-cols-1">
            <SalesChart />
          </div>

          {/* Recent Transactions and Top Products */}
          <div className="grid gap-6 grid-cols-1 @5xl:grid-cols-2">
            <RecentTransactions />
            <TopProducts />
          </div>
        </div>
      </div>
  )
}
