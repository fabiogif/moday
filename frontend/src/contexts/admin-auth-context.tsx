'use client'

import React, { createContext, useContext, useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import adminApi from '@/lib/admin-api-client'
import { buildApiUrl } from '@/lib/api-config'

interface AdminUser {
  id: number
  name: string
  email: string
  role: 'super_admin' | 'admin' | 'analyst'
  is_active: boolean
  last_login_at: string | null
  permissions: Record<string, string[]>
}

interface AdminAuthContextType {
  admin: AdminUser | null
  token: string | null
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => void
  refreshToken: () => Promise<void>
  updateAdmin: (data: Partial<AdminUser>) => void
  isAuthenticated: boolean
  isSuperAdmin: boolean
  isAdmin: boolean
  isAnalyst: boolean
  hasPermission: (permission: string) => boolean
}

const AdminAuthContext = createContext<AdminAuthContextType | undefined>(undefined)

export function AdminAuthProvider({ children }: { children: React.ReactNode }) {
  const [admin, setAdmin] = useState<AdminUser | null>(null)
  const [token, setToken] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const router = useRouter()

  // Carrega dados do localStorage
  useEffect(() => {
    const loadAdminData = () => {
      try {
        const storedToken = localStorage.getItem('admin-token')
        const storedAdmin = localStorage.getItem('admin-user')

        if (storedToken && storedAdmin) {
          // IMPORTANTE: Configurar o token no adminApi quando carregar do localStorage
          adminApi.setToken(storedToken)
          setToken(storedToken)
          setAdmin(JSON.parse(storedAdmin))
        }
      } catch (error) {

      } finally {
        setIsLoading(false)
      }
    }

    loadAdminData()
  }, [])

  // Login
  const login = async (email: string, password: string) => {
    try {
      const response = await fetch(buildApiUrl('/api/admin/auth/login'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include',
        body: JSON.stringify({ email, password }),
      })

      if (!response.ok) {
        let errorMessage = 'Erro ao fazer login'
        let errors: Record<string, string[]> = {}
        
        // Verificar se a resposta é JSON antes de tentar fazer parse
        const contentType = response.headers.get('content-type')
        if (contentType && contentType.includes('application/json')) {
          try {
            const error = await response.json()
            
            // Tratar estrutura padrão da aplicação: { success: false, message: "...", errors: {...} }
            if (error.success === false) {
              // Usar a mensagem do backend se disponível
              if (error.message) {
                errorMessage = error.message
              }
              
              // Extrair erros de validação se disponíveis
              if (error.errors) {
                errors = error.errors
                // Se não tiver mensagem, usar primeira mensagem de erro
                if (!error.message) {
                  const firstError = Object.values(error.errors)[0]
                  if (Array.isArray(firstError) && firstError.length > 0) {
                    errorMessage = firstError[0] as string
                  } else if (typeof firstError === 'string') {
                    errorMessage = firstError
                  }
                }
              }
            } else if (error.errors) {
              // Formato alternativo: erro com errors direto
              errors = error.errors
              const firstError = Object.values(error.errors)[0]
              if (Array.isArray(firstError) && firstError.length > 0) {
                errorMessage = firstError[0] as string
              } else if (typeof firstError === 'string') {
                errorMessage = firstError
              } else {
                errorMessage = error.message || 'Credenciais inválidas'
              }
            } else if (error.message) {
              // Erro com mensagem específica
              errorMessage = error.message
            }
          } catch (parseError) {
            // Se falhar ao fazer parse JSON, usar mensagem genérica
            errorMessage = `Erro ${response.status}: ${response.statusText || 'Credenciais inválidas'}`
          }
        } else {
          // Se não for JSON, usar mensagem baseada no status
          if (response.status === 401 || response.status === 422) {
            errorMessage = 'Credenciais inválidas'
          } else {
            errorMessage = `Erro ${response.status}: ${response.statusText || 'Erro ao fazer login'}`
          }
        }
        
        const error = new Error(errorMessage)
        ;(error as any).errors = errors
        throw error
      }

      const result = await response.json()
      
      // Verificar estrutura padrão: { success: true, data: {...}, message: "..." }
      if (!result.success) {
        const errorMessage = result.message || 'Erro ao fazer login'
        const error = new Error(errorMessage)
        ;(error as any).errors = result.errors || {}
        throw error
      }

      const { admin: adminData, token: authToken } = result.data

      // Salva no localStorage
      localStorage.setItem('admin-token', authToken)
      localStorage.setItem('admin-user', JSON.stringify(adminData))

      // IMPORTANTE: Atualizar o adminApi com o token antes de fazer outras requisições
      adminApi.setToken(authToken)

      setToken(authToken)
      setAdmin(adminData)

      // Busca permissões
      await fetchAdminData(authToken)

      router.push('/admin/dashboard')
    } catch (error) {
      // Tratar erros de rede (Failed to fetch)
      if (error instanceof TypeError && error.message === 'Failed to fetch') {
        throw new Error('Não foi possível conectar ao servidor. Verifique sua conexão com a internet.')
      }
      
      // Re-lançar outros erros
      throw error
    }
  }

  // Busca dados completos do admin
  const fetchAdminData = async (authToken: string) => {
    try {
      const response = await fetch(buildApiUrl('/api/admin/auth/me'), {
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json',
        },
      })

      if (response.ok) {
        const data = await response.json()
        const adminData = data.data
        
        setAdmin(adminData)
        localStorage.setItem('admin-user', JSON.stringify(adminData))
      }
    } catch (error) {

    }
  }

  // Logout
  const logout = async () => {
    try {
      if (token) {
        await fetch(buildApiUrl('/api/admin/auth/logout'), {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        })
      }
    } catch (error) {

    } finally {
      // Limpa dados locais
      localStorage.removeItem('admin-token')
      localStorage.removeItem('admin-user')
      // IMPORTANTE: Limpar token do adminApi também
      adminApi.clearToken()
      setToken(null)
      setAdmin(null)
      router.push('/admin/login')
    }
  }

  // Refresh token
  const refreshToken = async () => {
    try {
      if (!token) return

      const response = await fetch(buildApiUrl('/api/admin/auth/refresh'), {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      })

      if (response.ok) {
        const data = await response.json()
        const newToken = data.data.token

        localStorage.setItem('admin-token', newToken)
        setToken(newToken)
      } else {
        // Token inválido, faz logout
        logout()
      }
    } catch (error) {

      logout()
    }
  }

  const updateAdmin = (data: Partial<AdminUser>) => {
    setAdmin(prev => {
      if (!prev) return prev
      const updated = { ...prev, ...data }
      localStorage.setItem('admin-user', JSON.stringify(updated))
      return updated
    })
  }

  // Helpers
  const isAuthenticated = !!admin && !!token
  const isSuperAdmin = admin?.role === 'super_admin'
  const isAdmin = admin?.role === 'admin'
  const isAnalyst = admin?.role === 'analyst'

  const hasPermission = (permission: string): boolean => {
    if (!admin) return false
    if (isSuperAdmin) return true // Super admin tem todas as permissões

    const [module, action] = permission.split('.')
    return admin.permissions[module]?.includes(action) || false
  }

  return (
    <AdminAuthContext.Provider
      value={{
        admin,
        token,
        isLoading,
        login,
        logout,
        refreshToken,
        updateAdmin,
        isAuthenticated,
        isSuperAdmin,
        isAdmin,
        isAnalyst,
        hasPermission,
      }}
    >
      {children}
    </AdminAuthContext.Provider>
  )
}

export function useAdminAuth() {
  const context = useContext(AdminAuthContext)
  if (context === undefined) {
    throw new Error('useAdminAuth deve ser usado dentro de AdminAuthProvider')
  }
  return context
}

