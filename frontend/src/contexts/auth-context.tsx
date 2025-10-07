"use client"

import { createContext, useContext, useEffect, useState, ReactNode } from 'react'
import { apiClient } from '@/lib/api-client'

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

interface AuthContextType {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  setUser: (user: User) => void
  setToken: (token: string) => void
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
  const [isAuthenticated, setIsAuthenticated] = useState(false)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    // Verificar se há dados no localStorage ao inicializar
    const savedUser = localStorage.getItem('auth-user')
    const savedToken = localStorage.getItem('auth-token')
    
    if (process.env.NODE_ENV === 'development') {
      console.log('AuthContext: Inicializando autenticação')
      console.log('AuthContext: Token presente?', !!savedToken)
      console.log('AuthContext: Token é JWT?', savedToken?.startsWith('eyJ'))
    }
    
    if (savedUser && savedToken) {
      // Validar se o token parece ser um JWT válido
      if (!savedToken.startsWith('eyJ')) {
        console.error('AuthContext: Token inválido encontrado (não é JWT). Limpando...')
        localStorage.removeItem('auth-user')
        localStorage.removeItem('auth-token')
        document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'
      } else {
        try {
          const userData = JSON.parse(savedUser)
          setUser(userData)
          setToken(savedToken)
          setIsAuthenticated(true)
          
          // Also set the token in apiClient
          apiClient.setToken(savedToken)
          
          if (process.env.NODE_ENV === 'development') {
            console.log('AuthContext: Autenticação restaurada com sucesso')
          }
        } catch (error) {
          console.error('Erro ao recuperar dados de autenticação:', error)
          localStorage.removeItem('auth-user')
          localStorage.removeItem('auth-token')
        }
      }
    }
    
    setIsLoading(false)
  }, [])

  const login = async (email: string, password: string) => {
    setIsLoading(true)
    try {
      const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
      const response = await fetch(`${apiUrl}/api/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include', // Importante para cookies
        body: JSON.stringify({ email, password }),
      })

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || 'Erro ao fazer login')
      }

      const result = await response.json()
      const data = result.data // Extract the data object from the response
      
      if (process.env.NODE_ENV === 'development') {
        console.log('AuthContext: Login bem-sucedido')
        console.log('AuthContext: Token recebido?', !!data.token)
        console.log('AuthContext: Token é JWT?', data.token?.startsWith('eyJ'))
      }
      
      setUser(data.user)
      setToken(data.token) // Armazenar o token JWT recebido
      setIsAuthenticated(true)

      // Salvar dados do usuário e token
      localStorage.setItem('auth-user', JSON.stringify(data.user))
      localStorage.setItem('auth-token', data.token)
      
      // Also set the token in apiClient
      apiClient.setToken(data.token)
      
      // Também salvar no cookie para compatibilidade
      document.cookie = `auth-token=${data.token}; path=/; max-age=${7 * 24 * 60 * 60}`
    } catch (error) {
      throw error
    } finally {
      setIsLoading(false)
    }
  }

  const logout = async () => {
    try {
      const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
      // Chamar logout no backend
      await fetch(`${apiUrl}/api/auth/logout`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
    } catch (error) {
      console.error('Erro ao fazer logout no backend:', error)
    } finally {
      setUser(null)
      setToken(null)
      setIsAuthenticated(false)
      
      // Clear token from apiClient
      apiClient.clearToken()
      
      // Remover dados do localStorage
      localStorage.removeItem('auth-user')
      localStorage.removeItem('auth-token')
      
      // Limpar cookie
      document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'
    }
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
    isAuthenticated,
    isLoading,
    login,
    logout,
    setUser: updateUser,
    setToken: updateToken,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
