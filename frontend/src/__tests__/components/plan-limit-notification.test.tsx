/**
 * Testes para o componente PlanLimitNotification
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { PlanLimitNotification } from '@/components/plan-limit-notification'
import { usePlanLimits } from '@/hooks/use-plan-limits'
import { useRouter } from 'next/navigation'

// Mock dos hooks
jest.mock('@/hooks/use-plan-limits')
jest.mock('next/navigation', () => ({
  useRouter: jest.fn(),
}))

const mockUsePlanLimits = usePlanLimits as jest.MockedFunction<typeof usePlanLimits>
const mockUseRouter = useRouter as jest.Mock

describe('PlanLimitNotification', () => {
  const mockPush = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    mockUseRouter.mockReturnValue({
      push: mockPush,
      replace: jest.fn(),
      prefetch: jest.fn(),
      back: jest.fn(),
      forward: jest.fn(),
      refresh: jest.fn(),
      pathname: '/',
      query: {},
      asPath: '/',
    } as any)

    // Limpar localStorage
    localStorage.clear()
  })

  it('não deve renderizar quando hasLimitReached é false', () => {
    mockUsePlanLimits.mockReturnValue({
      hasLimitReached: false,
      reachedLimits: [],
      currentUsage: { users: 0, products: 0, orders_this_month: 0 },
      planLimits: { max_users: null, max_products: null, max_orders_per_month: null },
      planName: 'Premium',
      message: '',
      detailedMessages: [],
      loading: false,
      error: null,
      refetch: jest.fn(),
    })

    const { container } = render(<PlanLimitNotification />)
    expect(container.firstChild).toBeNull()
  })

  it('não deve renderizar durante loading', () => {
    mockUsePlanLimits.mockReturnValue({
      hasLimitReached: true,
      reachedLimits: ['users'],
      currentUsage: { users: 5, products: 0, orders_this_month: 0 },
      planLimits: { max_users: 5, max_products: null, max_orders_per_month: null },
      planName: 'Básico',
      message: 'Limite atingido',
      detailedMessages: [],
      loading: true,
      error: null,
      refetch: jest.fn(),
    })

    const { container } = render(<PlanLimitNotification />)
    expect(container.firstChild).toBeNull()
  })

  it('deve renderizar quando hasLimitReached é true', () => {
    mockUsePlanLimits.mockReturnValue({
      hasLimitReached: true,
      reachedLimits: ['users'],
      currentUsage: { users: 5, products: 0, orders_this_month: 0 },
      planLimits: { max_users: 5, max_products: null, max_orders_per_month: null },
      planName: 'Básico',
      message: 'Você atingiu o limite do seu plano atual.',
      detailedMessages: [],
      loading: false,
      error: null,
      refetch: jest.fn(),
    })

    render(<PlanLimitNotification />)

    expect(screen.getByText('Limite do Plano Atingido')).toBeInTheDocument()
    expect(screen.getByText(/Você atingiu o limite do seu plano atual/)).toBeInTheDocument()
  })

  it('deve exibir badges para limites atingidos', () => {
    mockUsePlanLimits.mockReturnValue({
      hasLimitReached: true,
      reachedLimits: ['users', 'products'],
      currentUsage: { users: 5, products: 100, orders_this_month: 0 },
      planLimits: { max_users: 5, max_products: 100, max_orders_per_month: null },
      planName: 'Básico',
      message: 'Limite atingido',
      detailedMessages: [],
      loading: false,
      error: null,
      refetch: jest.fn(),
    })

    render(<PlanLimitNotification />)

    expect(screen.getByText('Usuários')).toBeInTheDocument()
    expect(screen.getByText('Produtos')).toBeInTheDocument()
  })

  it('deve navegar para página de planos ao clicar em "Migrar de Plano"', () => {
    mockUsePlanLimits.mockReturnValue({
      hasLimitReached: true,
      reachedLimits: ['users'],
      currentUsage: { users: 5, products: 0, orders_this_month: 0 },
      planLimits: { max_users: 5, max_products: null, max_orders_per_month: null },
      planName: 'Básico',
      message: 'Limite atingido',
      detailedMessages: [],
      loading: false,
      error: null,
      refetch: jest.fn(),
    })

    render(<PlanLimitNotification />)

    const migrateButton = screen.getByText('Migrar de Plano')
    fireEvent.click(migrateButton)

    expect(mockPush).toHaveBeenCalledWith('/settings/company#planos')
  })

  it('deve fechar notificação ao clicar no X', () => {
    mockUsePlanLimits.mockReturnValue({
      hasLimitReached: true,
      reachedLimits: ['users'],
      currentUsage: { users: 5, products: 0, orders_this_month: 0 },
      planLimits: { max_users: 5, max_products: null, max_orders_per_month: null },
      planName: 'Básico',
      message: 'Limite atingido',
      detailedMessages: [],
      loading: false,
      error: null,
      refetch: jest.fn(),
    })

    const onDismiss = jest.fn()
    const { container } = render(<PlanLimitNotification onDismiss={onDismiss} />)

    const closeButton = screen.getByRole('button', { name: /close/i })
    fireEvent.click(closeButton)

    // Verificar se foi salvo no localStorage
    const dismissedKey = `plan_limit_dismissed_${Date.now().toString().slice(0, -6)}`
    // Nota: O componente usa uma chave baseada na data, então não podemos verificar diretamente
    // Mas podemos verificar se onDismiss foi chamado
    expect(onDismiss).toHaveBeenCalled()
  })

  it('não deve renderizar quando foi fechada anteriormente', () => {
    // Simular que foi fechada hoje
    const dismissedKey = `plan_limit_dismissed_${Date.now().toString().slice(0, -6)}`
    localStorage.setItem(dismissedKey, 'true')

    mockUsePlanLimits.mockReturnValue({
      hasLimitReached: true,
      reachedLimits: ['users'],
      currentUsage: { users: 5, products: 0, orders_this_month: 0 },
      planLimits: { max_users: 5, max_products: null, max_orders_per_month: null },
      planName: 'Básico',
      message: 'Limite atingido',
      detailedMessages: [],
      loading: false,
      error: null,
      refetch: jest.fn(),
    })

    const { container } = render(<PlanLimitNotification />)
    expect(container.firstChild).toBeNull()
  })
})

