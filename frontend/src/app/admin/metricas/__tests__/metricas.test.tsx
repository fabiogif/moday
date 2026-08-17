import React from 'react'
import { render, screen, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import FaturamentoPage from '../faturamento/page'
import UsoPage from '../uso/page'
import MensagensPage from '../mensagens/page'
import CrescimentoPage from '../crescimento/page'
import adminApi from '@/lib/admin-api-client'

const mockRedirect = jest.fn()

jest.mock('next/navigation', () => ({
  useRouter: () => ({ push: jest.fn() }),
  redirect: (...args: unknown[]) => mockRedirect(...args),
}))

jest.mock('@/contexts/admin-auth-context', () => ({
  useAdminAuth: () => ({
    isAuthenticated: true,
    isLoading: false,
    admin: { id: 1, name: 'Admin', email: 'admin@test.com' },
  }),
}))

jest.mock('@/lib/admin-api-client', () => ({
  __esModule: true,
  default: {
    getRevenueMetrics: jest.fn(),
    getUsageMetrics: jest.fn(),
    getTenants: jest.fn(),
    getGrowthMetrics: jest.fn(),
  },
}))

jest.mock('sonner', () => ({
  toast: { error: jest.fn(), success: jest.fn() },
}))

describe('Admin Métricas Pages', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  test('faturamento carrega métricas de receita', async () => {
    ;(adminApi.getRevenueMetrics as jest.Mock).mockResolvedValue({
      data: {
        mrr: 5000,
        revenue_by_month: [],
        by_plan: [],
        top_tenants: [],
      },
    })

    render(<FaturamentoPage />)

    await waitFor(() => {
      expect(screen.getByText('Métricas de Faturamento')).toBeInTheDocument()
    })
    expect(adminApi.getRevenueMetrics).toHaveBeenCalled()
  })

  test('uso carrega métricas de uso', async () => {
    ;(adminApi.getUsageMetrics as jest.Mock).mockResolvedValue({
      data: {
        logins_by_day: [{ metric_date: '2025-01-01', total_logins: 10 }],
        active_tenants_per_day: [{ metric_date: '2025-01-01', active_count: 5 }],
        most_active_tenants: [],
        adoption_rate: 75.5,
        inactive_tenants_count: 2,
      },
    })

    render(<UsoPage />)

    await waitFor(() => {
      expect(screen.getByText('Métricas de Uso')).toBeInTheDocument()
    })
    expect(adminApi.getUsageMetrics).toHaveBeenCalled()
  })

  test('mensagens redireciona para informativo', () => {
    render(<MensagensPage />)

    expect(mockRedirect).toHaveBeenCalledWith('/admin/informativo')
  })

  test('crescimento carrega métricas de crescimento', async () => {
    ;(adminApi.getGrowthMetrics as jest.Mock).mockResolvedValue({
      data: {
        new_tenants_by_month: [{ month: '2025-01', count: 5 }],
        conversions_by_month: [{ month: '2025-01', count: 2 }],
        churn_by_month: [{ month: '2025-01', count: 1 }],
        conversion_rate: 40,
        current_stats: { total_trials: 3, total_active: 10, total_expired: 1 },
      },
    })

    render(<CrescimentoPage />)

    await waitFor(() => {
      expect(screen.getByText('Métricas de Crescimento')).toBeInTheDocument()
    })
    expect(adminApi.getGrowthMetrics).toHaveBeenCalled()
  })
})
