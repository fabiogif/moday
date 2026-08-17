/**
 * Testes para o componente PlansSection
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { PlansSection } from '@/app/(dashboard)/settings/company/components/plans-section'
import { useAuthenticatedPlans } from '@/hooks/use-authenticated-api'
import { usePlanMigration } from '@/hooks/use-plan-migration'
import { usePlanLimits } from '@/hooks/use-plan-limits'
import { useAuth } from '@/contexts/auth-context'
import { apiClient } from '@/lib/api-client'

const mockPush = jest.fn()

// Mock dos hooks e módulos
jest.mock('@/hooks/use-authenticated-api')
jest.mock('@/hooks/use-plan-migration')
jest.mock('@/hooks/use-plan-limits')
jest.mock('@/contexts/auth-context')
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: jest.fn(),
    refresh: jest.fn(),
    back: jest.fn(),
    forward: jest.fn(),
    prefetch: jest.fn(),
  }),
}))
jest.mock('@/lib/api-client', () => ({
  apiClient: {
    get: jest.fn(),
    post: jest.fn(),
    put: jest.fn(),
    delete: jest.fn(),
    setToken: jest.fn(),
    reloadToken: jest.fn(),
  },
  endpoints: {},
}))
jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
  },
}))

const mockUseAuthenticatedPlans = useAuthenticatedPlans as jest.MockedFunction<typeof useAuthenticatedPlans>
const mockUsePlanMigration = usePlanMigration as jest.MockedFunction<typeof usePlanMigration>
const mockUsePlanLimits = usePlanLimits as jest.MockedFunction<typeof usePlanLimits>
const mockUseAuth = useAuth as jest.MockedFunction<typeof useAuth>
const mockApiClient = apiClient as jest.Mocked<typeof apiClient>

describe('PlansSection', () => {
  const mockPlans = [
    {
      id: 1,
      name: 'Grátis',
      url: 'gratis',
      price: 0,
      max_users: 1,
      max_products: 50,
      max_orders_per_month: 30,
      has_marketing: false,
      has_order_completion_email: false,
      has_reports: false,
    },
    {
      id: 2,
      name: 'Básico',
      url: 'basico',
      price: 49.90,
      max_users: 5,
      max_products: 100,
      max_orders_per_month: 100,
      has_marketing: true,
      has_order_completion_email: true,
      has_reports: true,
    },
  ]

  const mockMigratePlan = jest.fn().mockResolvedValue({ success: true })
  const mockGetHistory = jest.fn().mockResolvedValue([])

  beforeEach(() => {
    jest.clearAllMocks()

    mockUseAuth.mockReturnValue({
      user: {
        id: '1',
        name: 'Test User',
        email: 'test@example.com',
        tenant: {
          uuid: 'tenant-uuid',
          name: 'Test Tenant',
        },
      },
      token: 'mock-token',
      isAuthenticated: true,
      isLoading: false,
      login: jest.fn(),
      logout: jest.fn(),
      setUser: jest.fn(),
      setToken: jest.fn(),
      trialStatus: { is_active: true },
      refreshTrialStatus: jest.fn(),
    } as any)

    mockUseAuthenticatedPlans.mockReturnValue({
      data: mockPlans,
      loading: false,
      error: null,
      refetch: jest.fn(),
      pagination: null,
    } as any)

    mockUsePlanMigration.mockReturnValue({
      migratePlan: mockMigratePlan,
      getHistory: mockGetHistory,
      isMigrating: false,
      isLoadingHistory: false,
      error: null,
      history: [],
    })

    mockUsePlanLimits.mockReturnValue({
      hasLimitReached: false,
      reachedLimits: [],
      currentUsage: {
        users: 1,
        products: 10,
        orders_this_month: 5,
      },
      planLimits: {
        max_users: 1,
        max_products: 50,
        max_orders_per_month: 30,
      },
      planName: 'Grátis',
      message: '',
      detailedMessages: [],
      loading: false,
      error: null,
      refetch: jest.fn(),
    })

    mockApiClient.get = jest.fn().mockResolvedValue({
      success: true,
      data: { plan_id: 1 },
    } as any)
  })

  it('deve renderizar loading quando planos estão carregando', () => {
    mockUseAuthenticatedPlans.mockReturnValue({
      data: null,
      loading: true,
      error: null,
      refetch: jest.fn(),
      pagination: null,
    } as any)

    render(<PlansSection />)

    expect(screen.getByText(/Carregando planos/)).toBeInTheDocument()
  })

  it('deve renderizar erro quando há erro ao carregar planos', () => {
    mockUseAuthenticatedPlans.mockReturnValue({
      data: null,
      loading: false,
      error: 'Erro ao carregar',
      refetch: jest.fn(),
      pagination: null,
    } as any)

    render(<PlansSection />)

    expect(screen.getByText(/Erro ao carregar planos/)).toBeInTheDocument()
  })

  it('deve renderizar cards de planos quando carregados', () => {
    render(<PlansSection />)

    expect(screen.getByText('Grátis')).toBeInTheDocument()
    expect(screen.getByText('Básico')).toBeInTheDocument()
  })

  it('deve exibir card do plano atual com informações de uso', async () => {
    render(<PlansSection />)

    await waitFor(() => {
      expect(screen.getAllByText('Plano Atual').length).toBeGreaterThan(0)
    }, { timeout: 3000 })
  })

  it('deve abrir modal de migração ao clicar em migrar', async () => {
    render(<PlansSection />)

    await waitFor(() => {
      expect(screen.getByText('Básico')).toBeInTheDocument()
    })

    const migrateButtons = screen.getAllByText(/Migrar para este Plano/)
    fireEvent.click(migrateButtons[0])

    await waitFor(() => {
      expect(screen.getByText('Confirmar Migração de Plano')).toBeInTheDocument()
    })
  })

  it('deve exibir barras de progresso para uso de recursos', async () => {
    render(<PlansSection />)

    await waitFor(() => {
      expect(screen.getAllByText('Plano Atual').length).toBeGreaterThan(0)
    })

    expect(screen.getAllByText(/Usuários/).length).toBeGreaterThan(0)
    expect(screen.getAllByText(/Produtos/).length).toBeGreaterThan(0)
    expect(screen.getAllByText(/Pedidos este mês/).length).toBeGreaterThan(0)
  })

  it('deve exibir alerta quando limite é atingido', () => {
    mockUsePlanLimits.mockReturnValue({
      hasLimitReached: true,
      reachedLimits: ['users'],
      currentUsage: {
        users: 1,
        products: 10,
        orders_this_month: 5,
      },
      planLimits: {
        max_users: 1,
        max_products: 50,
        max_orders_per_month: 30,
      },
      planName: 'Grátis',
      message: 'Limite atingido',
      detailedMessages: [],
      loading: false,
      error: null,
      refetch: jest.fn(),
    })

    render(<PlansSection />)

    expect(screen.getByText(/Você atingiu um ou mais limites/)).toBeInTheDocument()
  })
})
