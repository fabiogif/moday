import React from 'react'
import { render, screen, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import AdminEmpresasPage from '../page'
import adminApi from '@/lib/admin-api-client'

jest.mock('next/navigation', () => ({
  useRouter: () => ({ push: jest.fn() }),
}))

jest.mock('@/contexts/admin-auth-context', () => ({
  useAdminAuth: () => ({
    isAuthenticated: true,
    isLoading: false,
    hasPermission: () => true,
  }),
}))

jest.mock('@/lib/admin-api-client', () => ({
  __esModule: true,
  default: {
    getTenants: jest.fn(),
    getDashboardStats: jest.fn(),
  },
}))

const mockTenants = [
  {
    id: 1,
    name: 'Restaurante Teste',
    subdomain: 'teste',
    account_status: 'active',
    plan_id: 1,
    subscription_plan: 'basico',
    is_blocked: false,
    mrr: 49.9,
    users_limit: 5,
    messages_limit: 1000,
    last_login_at: null,
    created_at: '2025-01-01',
    trial_expires_at: null,
  },
]

describe('Admin Empresas Page', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(adminApi.getTenants as jest.Mock).mockResolvedValue({ data: mockTenants })
    ;(adminApi.getDashboardStats as jest.Mock).mockResolvedValue({
      data: { tenants: { total: 1, active: 1, trial: 0, expired: 0 } },
    })
  })

  test('renderiza lista de empresas', async () => {
    render(<AdminEmpresasPage />)

    await waitFor(() => {
      expect(screen.getByText('Empresas')).toBeInTheDocument()
    })

    expect(screen.getByText('Restaurante Teste')).toBeInTheDocument()
    expect(adminApi.getTenants).toHaveBeenCalledWith({ per_page: 100 })
  })
})
