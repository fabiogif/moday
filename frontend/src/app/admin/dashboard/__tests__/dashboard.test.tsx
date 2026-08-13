import React from 'react'
import { render, screen, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import AdminDashboardPage from '../page'
import adminApi from '@/lib/admin-api-client'

jest.mock('next/navigation', () => ({
  useRouter: () => ({ push: jest.fn() }),
}))

jest.mock('@/contexts/admin-auth-context', () => ({
  useAdminAuth: () => ({
    isAuthenticated: true,
    isLoading: false,
    token: 'test-token',
  }),
}))

jest.mock('@/lib/admin-api-client', () => ({
  __esModule: true,
  default: {
    setToken: jest.fn(),
    getDashboardStats: jest.fn(),
    getAlerts: jest.fn(),
  },
}))

const mockStats = {
  tenants: { total: 10, active: 8, trial: 2, expired: 0, suspended: 0, blocked: 0 },
  financial: { mrr: 1500, pending_invoices: 2, overdue_invoices: 0, total_revenue_month: 3000 },
  usage: { logins_today: 5, orders_today: 12, revenue_today: 450, messages_today: 30 },
  growth: { new_tenants_month: 3, new_tenants_week: 1, churn_rate: 2.5 },
}

describe('Admin Dashboard Page', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    localStorage.setItem('admin-token', 'test-token')
    ;(adminApi.getDashboardStats as jest.Mock).mockResolvedValue({ data: mockStats })
    ;(adminApi.getAlerts as jest.Mock).mockResolvedValue({ data: [] })
  })

  test('renderiza painel com estatísticas', async () => {
    render(<AdminDashboardPage />)

    await waitFor(() => {
      expect(screen.getByText('Painel de Controle')).toBeInTheDocument()
    })

    expect(screen.getByText('Total de Empresas')).toBeInTheDocument()
    expect(screen.getByText('MRR')).toBeInTheDocument()
    expect(screen.getByText('Crescimento')).toBeInTheDocument()
    expect(adminApi.getDashboardStats).toHaveBeenCalled()
    expect(adminApi.getAlerts).toHaveBeenCalled()
  })

  test('exibe erro quando API falha', async () => {
    ;(adminApi.getDashboardStats as jest.Mock).mockRejectedValue(new Error('API error'))

    render(<AdminDashboardPage />)

    await waitFor(() => {
      expect(
        screen.getByText(/Não foi possível carregar os dados do painel/i)
      ).toBeInTheDocument()
    })
  })
})
