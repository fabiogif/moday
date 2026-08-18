import { render, screen } from "@testing-library/react"
import { MetricsOverview } from "../metrics-overview"
import { DashboardFiltersProvider } from "../../context/dashboard-filters-context"
import { TooltipProvider } from "@/components/ui/tooltip"

jest.mock("@/contexts/auth-context", () => ({
  useAuth: () => ({
    user: { id: 1, name: "Test User", tenant_id: "1" },
    isAuthenticated: true,
    isLoading: false,
    token: "test-token",
  }),
}))

jest.mock("@/hooks/use-realtime-dashboard", () => ({
  useRealtimeDashboard: () => ({ isConnected: false }),
}))

jest.mock("@/lib/api-client", () => ({
  apiClient: {
    setToken: jest.fn(),
    reloadToken: jest.fn(),
  },
}))

jest.mock("@/hooks/use-authenticated-api", () => ({
  useAuthenticatedApi: jest.fn(),
  useAuthenticatedOrderStats: jest.fn(),
  useAuthenticatedReviewStats: jest.fn(),
  useAuthenticatedClientStats: jest.fn(),
}))

jest.mock("@/hooks/use-sales-performance", () => ({
  useSalesPerformance: jest.fn(),
}))

const {
  useAuthenticatedApi,
  useAuthenticatedOrderStats,
  useAuthenticatedReviewStats,
  useAuthenticatedClientStats,
} = require("@/hooks/use-authenticated-api")
const { useSalesPerformance } = require("@/hooks/use-sales-performance")

function renderOverview() {
  return render(
    <TooltipProvider>
      <DashboardFiltersProvider>
        <MetricsOverview />
      </DashboardFiltersProvider>
    </TooltipProvider>,
  )
}

describe("MetricsOverview", () => {
  beforeEach(() => {
    jest.clearAllMocks()
    useAuthenticatedApi.mockReturnValue({
      data: {
        active_clients: { value: 4, growth: 10, trend: "up", subtitle: "", description: "" },
        total_orders: { value: 8, growth: 5, trend: "up", subtitle: "", description: "" },
      },
      loading: false,
      error: null,
      refetch: jest.fn(),
    })
    useSalesPerformance.mockReturnValue({
      data: { indicators: {} },
      loading: false,
      error: null,
    })
    useAuthenticatedOrderStats.mockReturnValue({
      data: { delivered_orders: { current: 3, previous: 2, growth: 50 } },
      loading: false,
    })
    useAuthenticatedReviewStats.mockReturnValue({
      data: { average_rating: 4.5, total: 2 },
      loading: false,
    })
    useAuthenticatedClientStats.mockReturnValue({
      data: {},
      loading: false,
    })
  })

  it("não quebra quando a API omite conversion_rate, projected_revenue e recurring_clients_rate", () => {
    renderOverview()

    expect(screen.getByText("Clientes Ativos")).toBeInTheDocument()
    expect(screen.getByText("Taxa de Conversão")).toBeInTheDocument()
    expect(screen.getByText("Receita Projetada")).toBeInTheDocument()
    expect(screen.getByText("Clientes Recorrentes")).toBeInTheDocument()
    expect(screen.getAllByText("—").length).toBeGreaterThan(0)
  })

  it("exibe os KPIs derivados quando a API envia current", () => {
    useAuthenticatedOrderStats.mockReturnValue({
      data: {
        delivered_orders: { current: 3, previous: 2, growth: 50 },
        canceled_orders: { current: 1, previous: 1, growth: 0 },
        projected_revenue: { current: 1500, previous: 1000, growth: 50 },
        average_service_time_minutes: { current: 25, previous: 30, growth: -16.7 },
      },
      loading: false,
    })
    useAuthenticatedClientStats.mockReturnValue({
      data: {
        recurring_clients_rate: { current: 40, previous: 20, growth: 100 },
      },
      loading: false,
    })

    renderOverview()

    expect(screen.getByText("R$ 1.500,00")).toBeInTheDocument()
    expect(screen.getByText("40.0%")).toBeInTheDocument()
    expect(screen.getByText("25min")).toBeInTheDocument()
  })
})
