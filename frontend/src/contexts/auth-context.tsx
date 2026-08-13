"use client"

import { createContext, useContext, useEffect, useState, ReactNode } from 'react'
import { apiClient } from '@/lib/api-client'
import { buildApiUrl } from '@/lib/api-config'

interface User {
  id: string
  name: string
  email: string
  tenant_id?: string
  tenant?: {
    uuid: string
    name: string
  }
}

export interface TrialStatus {
  is_trial: boolean
  is_active: boolean
  is_expired?: boolean
  days_remaining: number
  expires_at: string | null
  is_expiring_soon?: boolean
  needs_payment: boolean
  account_status?: string
}

interface AuthContextType {
  user: User | null
  token: string | null
  trialStatus: TrialStatus | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  setUser: (user: User) => void
  setToken: (token: string) => void
  setTrialStatus: (status: TrialStatus | null) => void
  refreshTrialStatus: () => Promise<void>
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

export function useAuth() {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}

interface AuthProviderProps {
  children: ReactNode
}

export function AuthProvider({ children }: AuthProviderProps) {
  const [user, setUser] = useState<User | null>(null)
  const [token, setToken] = useState<string | null>(null)
  const [trialStatus, setTrialStatus] = useState<TrialStatus | null>(null)
  const [isAuthenticated, setIsAuthenticated] = useState(false)
  const [isLoading, setIsLoading] = useState(true)
  const [hasMounted, setHasMounted] = useState(false)

  useEffect(() => {
    setHasMounted(true)
    
    // Verificar se há dados no localStorage ao inicializar
    const savedUser = localStorage.getItem('auth-user')
    const savedToken = localStorage.getItem('auth-token')
    const savedTrialStatus = localStorage.getItem('trial-status')
    
    if (savedUser && savedToken) {
      try {
        const userData = JSON.parse(savedUser)
        setUser(userData)
        setToken(savedToken)
        setIsAuthenticated(true)

        // Cookie para o middleware enxergar a sessão após refresh
        document.cookie = `auth-token=${savedToken}; path=/; max-age=${2 * 60 * 60}; SameSite=Lax`
          
        // Recuperar trial status se existir
        if (savedTrialStatus) {
          try {
            const trialData = JSON.parse(savedTrialStatus)
            setTrialStatus(trialData)
          } catch (error) {
            // Ignorar erro ao parsear trial status
          }
        }
        
        // Also set the token in apiClient
        apiClient.setToken(savedToken)
      } catch (error) {
        // Limpar dados inválidos
        localStorage.removeItem('auth-user')
        localStorage.removeItem('auth-token')
        localStorage.removeItem('trial-status')
      }
    }
    
    setIsLoading(false)

    // Ouvir eventos de autenticação não autorizada
    const handleUnauthorized = () => {
      setUser(null)
      setToken(null)
      setIsAuthenticated(false)
      setTrialStatus(null)
      localStorage.removeItem('auth-user')
      localStorage.removeItem('auth-token')
      localStorage.removeItem('trial-status')
      document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'

      const path = window.location.pathname + window.location.search
      const loginPath = path && !path.startsWith('/auth/login') && !path.startsWith('/login')
        ? `/auth/login?redirect=${encodeURIComponent(path)}`
        : '/auth/login'
      window.location.href = loginPath
    }

    window.addEventListener('auth:unauthorized', handleUnauthorized)
    
    return () => {
      window.removeEventListener('auth:unauthorized', handleUnauthorized)
    }
  }, [])

  const persistTrialStatus = (status: TrialStatus | null | undefined) => {
    if (!status) return
    setTrialStatus(status)
    localStorage.setItem('trial-status', JSON.stringify(status))
  }

  const login = async (email: string, password: string) => {
    setIsLoading(true)
    try {
      const response = await fetch(buildApiUrl('/api/auth/login'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include', // Importante para cookies
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
      
      const data = result.data // Extract the data object from the response

      // Salvar dados do usuário e token
      localStorage.setItem('auth-user', JSON.stringify(data.user))
      localStorage.setItem('auth-token', data.token)
      
      // IMPORTANTE: Atualizar o estado do contexto após login bem-sucedido
      setUser(data.user)
      setToken(data.token)
      setIsAuthenticated(true)
      
      // Also set the token in apiClient
      apiClient.setToken(data.token)
      apiClient.reloadToken() // Forçar recarga para garantir sincronização
      
      // Também salvar no cookie para compatibilidade
      document.cookie = `auth-token=${data.token}; path=/; max-age=${2 * 60 * 60}`

      if (data.trial_status) {
        persistTrialStatus(data.trial_status)
      } else {
        await refreshTrialStatusWithToken(data.token)
      }
    } catch (error) {
      // Tratar erros de rede (Failed to fetch)
      if (error instanceof TypeError && error.message === 'Failed to fetch') {
        throw new Error('Não foi possível conectar ao servidor. Verifique sua conexão com a internet.')
      }
      
      // Re-lançar outros erros
      throw error
    } finally {
      setIsLoading(false)
    }
  }

  const logout = async () => {
    try {
      // Chamar logout no backend
      await fetch(buildApiUrl('/api/auth/logout'), {
        method: 'POST',
        credentials: 'include',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
    } catch (error) {

    } finally {
      setUser(null)
      setToken(null)
      setTrialStatus(null)
      setIsAuthenticated(false)
      
      // Clear token from apiClient
      apiClient.clearToken()
      
      // Remover dados do localStorage
      localStorage.removeItem('auth-user')
      localStorage.removeItem('auth-token')
      localStorage.removeItem('trial-status')
      
      // Limpar cookie
      document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'

      window.location.href = '/auth/login'
    }
  }

  const refreshTrialStatusWithToken = async (authToken: string) => {
    try {
      const response = await fetch(buildApiUrl('/api/subscription/trial-status'), {
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include',
      })

      if (response.ok) {
        const result = await response.json()
        if (result.data) {
          persistTrialStatus(result.data)
        }
      }
    } catch (error) {
      // Ignorar falha silenciosa
    }
  }

  const refreshTrialStatus = async () => {
    if (!token) return
    await refreshTrialStatusWithToken(token)
  }

  const updateTrialStatus = (status: TrialStatus | null) => {
    if (!status) {
      setTrialStatus(null)
      localStorage.removeItem('trial-status')
      return
    }
    persistTrialStatus(status)
  }

  const updateUser = (userData: User) => {
    setUser(userData)
    setIsAuthenticated(true)
    localStorage.setItem('auth-user', JSON.stringify(userData))
  }

  const updateToken = (tokenValue: string) => {
    setToken(tokenValue)
    setIsAuthenticated(true)
    localStorage.setItem('auth-token', tokenValue)
    // Also set in apiClient
    apiClient.setToken(tokenValue)
  }

  const value: AuthContextType = {
    user,
    token,
    trialStatus,
    isAuthenticated,
    isLoading,
    login,
    logout,
    setUser: updateUser,
    setToken: updateToken,
    setTrialStatus: updateTrialStatus,
    refreshTrialStatus,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
