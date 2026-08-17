/**
 * Testes para o hook usePlanLimits
 */

import { renderHook, waitFor, act } from '@testing-library/react'
import { usePlanLimits, useCurrentUsage } from '@/hooks/use-plan-limits'
import { apiClient } from '@/lib/api-client'
import { useAuth } from '@/contexts/auth-context'

jest.mock('@/contexts/auth-context', () => ({
  useAuth: jest.fn(),
}))

// Mock do apiClient
jest.mock('@/lib/api-client', () => ({
  apiClient: {
    get: jest.fn(),
    setToken: jest.fn(),
    reloadToken: jest.fn(),
  },
  endpoints: {
    planLimits: {
      check: '/api/plan/limits/check',
      currentUsage: '/api/plan/current-usage',
    },
  },
}))

describe('usePlanLimits', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    ;(useAuth as jest.Mock).mockReturnValue({
      token: 'mock-token',
      isAuthenticated: true,
      isLoading: false,
    })
  })

  it('deve retornar dados iniciais vazios', () => {
    ;(useAuth as jest.Mock).mockReturnValue({
      token: 'mock-token',
      isAuthenticated: true,
      isLoading: true,
    })

    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: {
        has_limit_reached: false,
        reached_limits: [],
        current_usage: {
          users: 0,
          products: 0,
          orders_this_month: 0,
        },
        plan_limits: {
          max_users: null,
          max_products: null,
          max_orders_per_month: null,
        },
        plan_name: '',
        message: '',
        detailed_messages: [],
      },
    })

    const { result } = renderHook(() => usePlanLimits())

    expect(result.current.hasLimitReached).toBe(false)
    expect(result.current.reachedLimits).toEqual([])
    expect(result.current.loading).toBe(false)
  })

  it('deve buscar limites ao montar o componente', async () => {
    const mockData = {
      has_limit_reached: true,
      reached_limits: ['users', 'products'],
      current_usage: {
        users: 5,
        products: 100,
        orders_this_month: 30,
      },
      plan_limits: {
        max_users: 5,
        max_products: 100,
        max_orders_per_month: 100,
      },
      plan_name: 'Básico',
      message: 'Você atingiu o limite do seu plano atual.',
      detailed_messages: [
        'Você atingiu o limite de 5 usuário(s).',
        'Você atingiu o limite de 100 produto(s).',
      ],
    }

    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: mockData,
    })

    const { result } = renderHook(() => usePlanLimits())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
      expect(result.current.hasLimitReached).toBe(true)
    })

    expect(result.current.reachedLimits).toEqual(['users', 'products'])
    expect(result.current.currentUsage.users).toBe(5)
    expect(result.current.planName).toBe('Básico')
    expect(apiClient.get).toHaveBeenCalledWith('/api/plan/limits/check')
  })

  it('deve tratar erros corretamente', async () => {
    ;(apiClient.get as jest.Mock).mockRejectedValue(new Error('Erro na requisição'))

    const { result } = renderHook(() => usePlanLimits())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
      expect(result.current.error).toBe('Erro na requisição')
    })

    expect(result.current.hasLimitReached).toBe(false)
  })

  it('deve atualizar limites periodicamente', async () => {
    jest.useFakeTimers()

    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: {
        has_limit_reached: false,
        reached_limits: [],
        current_usage: { users: 0, products: 0, orders_this_month: 0 },
        plan_limits: { max_users: null, max_products: null, max_orders_per_month: null },
        plan_name: 'Premium',
        message: '',
        detailed_messages: [],
      },
    })

    const { result } = renderHook(() => usePlanLimits())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    expect(apiClient.get).toHaveBeenCalledTimes(1)

    await act(async () => {
      jest.advanceTimersByTime(5 * 60 * 1000)
    })

    await waitFor(() => {
      expect(apiClient.get).toHaveBeenCalledTimes(2)
    })

    jest.useRealTimers()
  })

  it('deve permitir refetch manual', async () => {
    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: {
        has_limit_reached: false,
        reached_limits: [],
        current_usage: { users: 0, products: 0, orders_this_month: 0 },
        plan_limits: { max_users: null, max_products: null, max_orders_per_month: null },
        plan_name: 'Premium',
        message: '',
        detailed_messages: [],
      },
    })

    const { result } = renderHook(() => usePlanLimits())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    expect(apiClient.get).toHaveBeenCalledTimes(1)

    await act(async () => {
      await result.current.refetch()
    })

    expect(apiClient.get).toHaveBeenCalledTimes(2)
  })
})

describe('useCurrentUsage', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('deve buscar uso atual ao montar', async () => {
    const mockUsage = {
      users: 3,
      products: 25,
      orders_this_month: 15,
    }

    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: mockUsage,
    })

    const { result } = renderHook(() => useCurrentUsage())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    expect(result.current.usage).toEqual(mockUsage)
    expect(apiClient.get).toHaveBeenCalledWith('/api/plan/current-usage')
  })

  it('deve tratar erros corretamente', async () => {
    ;(apiClient.get as jest.Mock).mockRejectedValue(new Error('Erro na requisição'))

    const { result } = renderHook(() => useCurrentUsage())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
      expect(result.current.error).toBe('Erro na requisição')
    })

    expect(result.current.usage).toBeNull()
  })

  it('deve permitir refetch manual', async () => {
    const mockUsage = {
      users: 3,
      products: 25,
      orders_this_month: 15,
    }

    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: mockUsage,
    })

    const { result } = renderHook(() => useCurrentUsage())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    expect(apiClient.get).toHaveBeenCalledTimes(1)

    await act(async () => {
      await result.current.refetch()
    })

    expect(apiClient.get).toHaveBeenCalledTimes(2)
  })
})
