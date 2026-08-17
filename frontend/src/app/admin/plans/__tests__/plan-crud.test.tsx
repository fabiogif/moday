import React from 'react'
import { render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import '@testing-library/jest-dom'
import PlansPage from '../page'

const mockPlans = [
  {
    id: 1,
    name: 'Grátis',
    url: 'gratis',
    price: '0.00',
    description: 'Plano gratuito para testes',
    is_active: true,
    max_users: 1,
    max_products: 50,
    max_orders_per_month: 30,
    has_marketing: false,
    has_order_completion_email: false,
    has_reports: false,
    details: [{ id: 1, name: '1 usuário', plan_id: 1 }],
  },
  {
    id: 2,
    name: 'Básico',
    url: 'basico',
    price: '49.90',
    description: 'Plano básico',
    is_active: true,
    max_users: 5,
    max_products: 100,
    max_orders_per_month: 100,
    has_marketing: true,
    has_order_completion_email: true,
    has_reports: true,
    details: [],
  },
]

const mockRefetch = jest.fn()
const mockMutate = jest.fn()

jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: jest.fn(),
    refresh: jest.fn(),
  }),
}))

jest.mock('@/contexts/admin-auth-context', () => ({
  useAdminAuth: () => ({
    isAuthenticated: true,
    isLoading: false,
    token: 'test-token',
  }),
}))

jest.mock('@/hooks/use-admin-api', () => ({
  useAdminPlans: () => ({
    data: mockPlans,
    loading: false,
    error: null,
    refetch: mockRefetch,
    isAuthLoading: false,
    isAuthenticated: true,
  }),
  useAdminMutation: () => ({
    mutate: mockMutate,
    loading: false,
    error: null,
  }),
}))

jest.mock('@/lib/admin-api-client', () => ({
  __esModule: true,
  default: {
    createPlan: jest.fn(),
    updatePlan: jest.fn(),
    deletePlan: jest.fn(),
    reloadToken: jest.fn(),
  },
}))

describe('Admin Plans Page', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockMutate.mockImplementation(async (action: () => Promise<unknown>) => action())
  })

  test('renderiza página com título e botão novo plano', async () => {
    render(<PlansPage />)

    expect(screen.getByText('Planos de Assinatura')).toBeInTheDocument()
    expect(screen.getByText('Novo Plano')).toBeInTheDocument()
  })

  test('exibe planos na tabela', async () => {
    render(<PlansPage />)

    await waitFor(() => {
      expect(screen.getAllByText('Grátis').length).toBeGreaterThanOrEqual(1)
      expect(screen.getByText('Básico')).toBeInTheDocument()
    })
  })

  test('abre modal de criar plano', async () => {
    const user = userEvent.setup()
    render(<PlansPage />)

    await user.click(screen.getByText('Novo Plano'))

    await waitFor(() => {
      expect(screen.getByText('Novo Plano', { selector: 'h2' })).toBeInTheDocument()
      expect(screen.getByLabelText(/Nome do Plano/i)).toBeInTheDocument()
    })
  })

  test('abre modal de editar plano', async () => {
    const user = userEvent.setup()
    render(<PlansPage />)

    const row = screen.getByText('Plano gratuito para testes').closest('tr')
    expect(row).not.toBeNull()
    const actionButtons = within(row!).getAllByRole('button')
    await user.click(actionButtons[0])

    await waitFor(() => {
      expect(screen.getByText('Editar Plano')).toBeInTheDocument()
      expect(screen.getByDisplayValue('Grátis')).toBeInTheDocument()
    })
  })

  test('exibe badges de status ativo', async () => {
    render(<PlansPage />)

    const activeBadges = screen.getAllByText('Ativo')
    expect(activeBadges.length).toBeGreaterThan(0)
  })

})
