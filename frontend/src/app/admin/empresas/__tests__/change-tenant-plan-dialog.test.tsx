import React from 'react'
import { render, screen, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import { ChangeTenantPlanDialog } from '../components/change-tenant-plan-dialog'
import adminApi from '@/lib/admin-api-client'

jest.mock('@/lib/admin-api-client', () => ({
  __esModule: true,
  default: {
    getPlans: jest.fn(),
    migrateTenantPlan: jest.fn(),
  },
}))

const mockPlans = [
  {
    id: 1,
    name: 'Básico',
    url: 'basico',
    price: '49.90',
    description: 'Plano básico',
    is_active: true,
    max_users: 3,
    max_products: 100,
    max_orders_per_month: 500,
    has_marketing: false,
    has_order_completion_email: true,
    has_reports: true,
    details: [],
  },
  {
    id: 2,
    name: 'Pro',
    url: 'pro',
    price: '99.90',
    description: 'Plano profissional',
    is_active: true,
    max_users: 10,
    max_products: null,
    max_orders_per_month: null,
    has_marketing: true,
    has_order_completion_email: true,
    has_reports: true,
    details: [],
  },
]

describe('ChangeTenantPlanDialog', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(adminApi.getPlans as jest.Mock).mockResolvedValue({ data: mockPlans })
    ;(adminApi.migrateTenantPlan as jest.Mock).mockResolvedValue({ success: true })
  })

  test('carrega planos ao abrir o diálogo', async () => {
    render(
      <ChangeTenantPlanDialog
        open
        onOpenChange={jest.fn()}
        tenant={{
          id: 10,
          name: 'Restaurante Alfa',
          plan_id: 1,
          subscription_plan: 'Básico',
        }}
      />
    )

    await waitFor(() => {
      expect(adminApi.getPlans).toHaveBeenCalledWith({ per_page: 100 })
    })

    expect(screen.getByText('Alterar plano da empresa')).toBeInTheDocument()
    expect(screen.getByText('Restaurante Alfa')).toBeInTheDocument()
    expect(screen.getByText('Básico')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /confirmar alteração/i })).toBeInTheDocument()
  })
})
