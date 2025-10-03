"use client"

import { createContext, useContext, useEffect, useState, ReactNode } from 'react'

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
  logout: () => void
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
    
    if (savedUser && savedToken) {
      try {
        const userData = JSON.parse(savedUser)
        setUser(userData)
        setToken(savedToken)
        setIsAuthenticated(true)
      } catch (error) {
        console.error('Erro ao recuperar dados de autenticação:', error)
        localStorage.removeItem('auth-user')
        localStorage.removeItem('auth-token')
      }
    }
    
    setIsLoading(false)
  }, [])

  const login = async (email: string, password: string) => {
    setIsLoading(true)
    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      })

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || 'Erro ao fazer login')
      }

      const data = await response.json()
      
      setUser(data.user)
      setToken(data.token)
      setIsAuthenticated(true)

      // Salvar no localStorage
      localStorage.setItem('auth-user', JSON.stringify(data.user))
      localStorage.setItem('auth-token', data.token)

      // Salvar token no cookie
      document.cookie = `auth-token=${data.token}; path=/; max-age=${7 * 24 * 60 * 60}` // 7 dias
    } catch (error) {
      throw error
    } finally {
      setIsLoading(false)
    }
  }

  const logout = () => {
    setUser(null)
    setToken(null)
    setIsAuthenticated(false)
    
    // Remover do localStorage
    localStorage.removeItem('auth-user')
    localStorage.removeItem('auth-token')
    
    // Remover token do cookie
    document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'
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
