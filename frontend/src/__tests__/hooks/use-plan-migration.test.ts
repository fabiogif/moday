/**
 * Testes para o hook usePlanMigration
 */

import { renderHook, waitFor, act } from '@testing-library/react'
import { usePlanMigration } from '@/hooks/use-plan-migration'
import { apiClient } from '@/lib/api-client'

// Mock do apiClient
jest.mock('@/lib/api-client', () => ({
  apiClient: {
    post: jest.fn(),
    get: jest.fn(),
  },
  endpoints: {
    planMigration: {
      migrate: '/api/plan/migrate',
      history: '/api/plan/migrations/history',
    },
  },
}))

describe('usePlanMigration', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('deve inicializar com estados vazios', () => {
    const { result } = renderHook(() => usePlanMigration())

    expect(result.current.isMigrating).toBe(false)
    expect(result.current.isLoadingHistory).toBe(false)
    expect(result.current.error).toBeNull()
    expect(result.current.history).toEqual([])
  })

  it('deve migrar plano com sucesso', async () => {
    const mockResponse = {
      success: true,
      message: 'Plano migrado com sucesso.',
      data: {
        id: 1,
        plan_id: 2,
        subscription_plan: 'Básico',
      },
    }

    ;(apiClient.post as jest.Mock).mockResolvedValue(mockResponse)

    const { result } = renderHook(() => usePlanMigration())

    let success = false
    await act(async () => {
      const migration = await result.current.migratePlan({
        plan_id: 2,
        notes: 'Migração de teste',
      })
      success = migration.success
    })

    expect(success).toBe(true)
    await waitFor(() => {
      expect(result.current.isMigrating).toBe(false)
      expect(result.current.error).toBeNull()
    })
    expect(apiClient.post).toHaveBeenCalledWith('/api/plan/migrate', {
      plan_id: 2,
      notes: 'Migração de teste',
    })
  })

  it('deve tratar erro na migração', async () => {
    ;(apiClient.post as jest.Mock).mockResolvedValue({
      success: false,
      message: 'Erro ao migrar plano',
    })

    const { result } = renderHook(() => usePlanMigration())

    let success = true
    await act(async () => {
      const migration = await result.current.migratePlan({
        plan_id: 2,
      })
      success = migration.success
    })

    expect(success).toBe(false)
    await waitFor(() => {
      expect(result.current.error).toBe('Erro ao migrar plano')
      expect(result.current.isMigrating).toBe(false)
    })
  })

  it('deve tratar exceção na migração', async () => {
    ;(apiClient.post as jest.Mock).mockRejectedValue(new Error('Erro na requisição'))

    const { result } = renderHook(() => usePlanMigration())

    let success = true
    await act(async () => {
      const migration = await result.current.migratePlan({
        plan_id: 2,
      })
      success = migration.success
    })

    expect(success).toBe(false)
    await waitFor(() => {
      expect(result.current.error).toBe('Erro na requisição')
      expect(result.current.isMigrating).toBe(false)
    })
  })

  it('deve buscar histórico de migrações', async () => {
    const mockHistory = [
      {
        id: 1,
        from_plan: 'Grátis',
        to_plan: 'Básico',
        status: 'completed',
        migrated_at: '2025-11-18T10:00:00Z',
        notes: 'Primeira migração',
      },
      {
        id: 2,
        from_plan: 'Básico',
        to_plan: 'Premium',
        status: 'completed',
        migrated_at: '2025-11-19T10:00:00Z',
        notes: 'Segunda migração',
      },
    ]

    ;(apiClient.get as jest.Mock).mockResolvedValue({
      success: true,
      data: mockHistory,
    })

    const { result } = renderHook(() => usePlanMigration())

    let history: typeof mockHistory = []
    await act(async () => {
      history = await result.current.getHistory()
    })

    expect(history).toEqual(mockHistory)
    await waitFor(() => {
      expect(result.current.history).toEqual(mockHistory)
      expect(result.current.isLoadingHistory).toBe(false)
      expect(result.current.error).toBeNull()
    })
    expect(apiClient.get).toHaveBeenCalledWith('/api/plan/migrations/history')
  })

  it('deve tratar erro ao buscar histórico', async () => {
    ;(apiClient.get as jest.Mock).mockRejectedValue(new Error('Erro na requisição'))

    const { result } = renderHook(() => usePlanMigration())

    let history: unknown[] = [{ id: 1 }]
    await act(async () => {
      history = await result.current.getHistory()
    })

    expect(history).toEqual([])
    await waitFor(() => {
      expect(result.current.history).toEqual([])
      expect(result.current.error).toBe('Erro na requisição')
      expect(result.current.isLoadingHistory).toBe(false)
    })
  })

  it('deve atualizar estado de loading durante migração', async () => {
    let resolvePromise: (value: unknown) => void
    const promise = new Promise((resolve) => {
      resolvePromise = resolve
    })

    ;(apiClient.post as jest.Mock).mockReturnValue(promise)

    const { result } = renderHook(() => usePlanMigration())

    let migratePromise: Promise<{ success: boolean; error?: string }>
    act(() => {
      migratePromise = result.current.migratePlan({
        plan_id: 2,
      })
    })

    await waitFor(() => {
      expect(result.current.isMigrating).toBe(true)
    })

    await act(async () => {
      resolvePromise!({
        success: true,
        message: 'Plano migrado com sucesso.',
        data: {},
      })
      await migratePromise!
    })

    await waitFor(() => {
      expect(result.current.isMigrating).toBe(false)
    })
  })

  it('deve atualizar estado de loading durante busca de histórico', async () => {
    let resolvePromise: (value: unknown) => void
    const promise = new Promise((resolve) => {
      resolvePromise = resolve
    })

    ;(apiClient.get as jest.Mock).mockReturnValue(promise)

    const { result } = renderHook(() => usePlanMigration())

    let historyPromise: Promise<unknown[]>
    act(() => {
      historyPromise = result.current.getHistory()
    })

    await waitFor(() => {
      expect(result.current.isLoadingHistory).toBe(true)
    })

    await act(async () => {
      resolvePromise!({
        success: true,
        data: [],
      })
      await historyPromise!
    })

    await waitFor(() => {
      expect(result.current.isLoadingHistory).toBe(false)
    })
  })
})
