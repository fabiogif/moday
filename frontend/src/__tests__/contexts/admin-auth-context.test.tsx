import { renderHook, act, waitFor } from '@testing-library/react'
import { AdminAuthProvider, useAdminAuth } from '@/contexts/admin-auth-context'
import { ReactNode } from 'react'

// Mock do next/navigation
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: jest.fn(),
    replace: jest.fn(),
    pathname: '/',
  }),
}))

// Mock do fetch
global.fetch = jest.fn()

const wrapper = ({ children }: { children: ReactNode }) => (
  <AdminAuthProvider>{children}</AdminAuthProvider>
)

describe('AdminAuthContext', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    localStorage.clear()
    ;(global.fetch as jest.Mock).mockClear()
  })

  describe('Login', () => {
    it('should login successfully with valid credentials', async () => {
      const mockResponse = {
        success: true,
        data: {
          admin: {
            id: 1,
            name: 'Admin Test',
            email: 'admin@test.com',
            role: 'super_admin',
            is_active: true,
          },
          token: 'test-token-123',
        },
      }

      ;(global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      })

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      await act(async () => {
        await result.current.login('admin@test.com', 'password123')
      })

      await waitFor(() => {
        expect(result.current.isAuthenticated).toBe(true)
        expect(result.current.admin?.email).toBe('admin@test.com')
        expect(result.current.token).toBe('test-token-123')
      })

      // Verifica se salvou no localStorage
      expect(localStorage.getItem('admin-token')).toBe('test-token-123')
      expect(localStorage.getItem('admin-user')).toContain('admin@test.com')
    })

    it('should reject invalid credentials', async () => {
      ;(global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        json: async () => ({
          success: false,
          message: 'Credenciais inválidas',
        }),
      })

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      await expect(
        result.current.login('wrong@test.com', 'wrongpass')
      ).rejects.toThrow()

      expect(result.current.isAuthenticated).toBe(false)
    })

    it('should load token from localStorage on mount', () => {
      const mockAdmin = {
        id: 1,
        name: 'Admin Test',
        email: 'admin@test.com',
        role: 'admin',
      }

      localStorage.setItem('admin-token', 'stored-token')
      localStorage.setItem('admin-user', JSON.stringify(mockAdmin))

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      waitFor(() => {
        expect(result.current.token).toBe('stored-token')
        expect(result.current.admin?.email).toBe('admin@test.com')
        expect(result.current.isAuthenticated).toBe(true)
      })
    })
  })

  describe('Logout', () => {
    it('should logout successfully', async () => {
      localStorage.setItem('admin-token', 'test-token')
      localStorage.setItem('admin-user', JSON.stringify({ id: 1, name: 'Test' }))

      ;(global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      })

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      await act(async () => {
        await result.current.logout()
      })

      await waitFor(() => {
        expect(result.current.isAuthenticated).toBe(false)
        expect(result.current.admin).toBeNull()
        expect(result.current.token).toBeNull()
        expect(localStorage.getItem('admin-token')).toBeNull()
      })
    })
  })

  describe('Permissions', () => {
    it('should check if user is super admin', () => {
      const mockAdmin = {
        id: 1,
        name: 'Super Admin',
        email: 'super@test.com',
        role: 'super_admin' as const,
        is_active: true,
        last_login_at: null,
        permissions: {},
      }

      localStorage.setItem('admin-token', 'test-token')
      localStorage.setItem('admin-user', JSON.stringify(mockAdmin))

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      waitFor(() => {
        expect(result.current.isSuperAdmin).toBe(true)
        expect(result.current.isAdmin).toBe(false)
        expect(result.current.isAnalyst).toBe(false)
      })
    })

    it('should check if user is admin', () => {
      const mockAdmin = {
        id: 1,
        name: 'Admin',
        email: 'admin@test.com',
        role: 'admin' as const,
        is_active: true,
        last_login_at: null,
        permissions: {},
      }

      localStorage.setItem('admin-token', 'test-token')
      localStorage.setItem('admin-user', JSON.stringify(mockAdmin))

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      waitFor(() => {
        expect(result.current.isAdmin).toBe(true)
        expect(result.current.isSuperAdmin).toBe(false)
      })
    })

    it('super admin should have all permissions', () => {
      const mockAdmin = {
        id: 1,
        name: 'Super Admin',
        email: 'super@test.com',
        role: 'super_admin' as const,
        is_active: true,
        last_login_at: null,
        permissions: {},
      }

      localStorage.setItem('admin-token', 'test-token')
      localStorage.setItem('admin-user', JSON.stringify(mockAdmin))

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      waitFor(() => {
        expect(result.current.hasPermission('tenants.view')).toBe(true)
        expect(result.current.hasPermission('tenants.manage')).toBe(true)
        expect(result.current.hasPermission('any.permission')).toBe(true)
      })
    })

    it('analyst should have limited permissions', () => {
      const mockAdmin = {
        id: 1,
        name: 'Analyst',
        email: 'analyst@test.com',
        role: 'analyst' as const,
        is_active: true,
        last_login_at: null,
        permissions: {
          tenants: ['view'],
          metrics: ['view'],
        },
      }

      localStorage.setItem('admin-token', 'test-token')
      localStorage.setItem('admin-user', JSON.stringify(mockAdmin))

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      waitFor(() => {
        expect(result.current.hasPermission('tenants.view')).toBe(true)
        expect(result.current.hasPermission('tenants.manage')).toBe(false)
      })
    })
  })

  describe('Refresh Token', () => {
    it('should refresh token successfully', async () => {
      localStorage.setItem('admin-token', 'old-token')
      localStorage.setItem('admin-user', JSON.stringify({
        id: 1,
        name: 'Admin',
        email: 'admin@test.com',
        role: 'admin',
        is_active: true,
        last_login_at: null,
        permissions: {},
      }))

      ;(global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          data: { token: 'new-token' },
        }),
      })

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      await waitFor(() => {
        expect(result.current.token).toBe('old-token')
      })

      await act(async () => {
        await result.current.refreshToken()
      })

      await waitFor(() => {
        expect(result.current.token).toBe('new-token')
        expect(localStorage.getItem('admin-token')).toBe('new-token')
      })
    })

    it('should logout if refresh token fails', async () => {
      localStorage.setItem('admin-token', 'invalid-token')

      ;(global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        json: async () => ({ success: false }),
      })

      const { result } = renderHook(() => useAdminAuth(), { wrapper })

      await act(async () => {
        await result.current.refreshToken()
      })

      await waitFor(() => {
        expect(result.current.token).toBeNull()
        expect(result.current.isAuthenticated).toBe(false)
      })
    })
  })
})

