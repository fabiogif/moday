import { renderHook, waitFor } from '@testing-library/react'
import { useAdminPlans, useAdminMutation } from '@/hooks/use-admin-api'

jest.mock('@/contexts/admin-auth-context', () => ({
  useAdminAuth: jest.fn(),
}))

jest.mock('@/lib/admin-api-client', () => ({
  __esModule: true,
  default: {
    getPlans: jest.fn(),
    reloadToken: jest.fn(),
  },
}))

import { useAdminAuth } from '@/contexts/admin-auth-context'
import adminApi from '@/lib/admin-api-client'

const mockUseAdminAuth = useAdminAuth as jest.Mock
const mockGetPlans = adminApi.getPlans as jest.Mock

describe('useAdminApi hooks', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    mockUseAdminAuth.mockReturnValue({
      isAuthenticated: true,
      isLoading: false,
      token: 'admin-token',
    })
  })

  describe('useAdminPlans', () => {
    it('carrega planos quando autenticado', async () => {
      mockGetPlans.mockResolvedValue({
        success: true,
        data: [{ id: 1, name: 'Grátis', url: 'gratis', price: '0' }],
      })

      const { result } = renderHook(() => useAdminPlans())

      await waitFor(() => {
        expect(result.current.loading).toBe(false)
      })

      expect(result.current.data).toHaveLength(1)
      expect(result.current.data?.[0].name).toBe('Grátis')
      expect(adminApi.reloadToken).toHaveBeenCalled()
    })

    it('não carrega quando não autenticado', async () => {
      mockUseAdminAuth.mockReturnValue({
        isAuthenticated: false,
        isLoading: false,
        token: null,
      })

      const { result } = renderHook(() => useAdminPlans())

      await waitFor(() => {
        expect(result.current.loading).toBe(false)
      })

      expect(mockGetPlans).not.toHaveBeenCalled()
      expect(result.current.data).toBeNull()
    })

    it('define erro quando requisição falha', async () => {
      mockGetPlans.mockRejectedValue(new Error('Falha na API'))

      const { result } = renderHook(() => useAdminPlans())

      await waitFor(() => {
        expect(result.current.error).toBe('Falha na API')
      })
    })
  })

  describe('useAdminMutation', () => {
    it('executa mutation quando autenticado', async () => {
      const { result } = renderHook(() => useAdminMutation<{ ok: boolean }>())

      const response = await result.current.mutate(async () => ({ ok: true }))

      expect(response).toEqual({ ok: true })
      expect(adminApi.reloadToken).toHaveBeenCalled()
    })

    it('rejeita mutation sem autenticação', async () => {
      mockUseAdminAuth.mockReturnValue({
        isAuthenticated: false,
        isLoading: false,
        token: null,
      })

      const { result } = renderHook(() => useAdminMutation())

      await expect(
        result.current.mutate(async () => 'ok')
      ).rejects.toThrow('Administrador não autenticado')
    })
  })
})
