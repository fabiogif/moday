"use client"

import { createContext, useContext, useEffect, useState, ReactNode } from 'react'

interface ClientUser {
  uuid: string
  name: string
  email: string
  phone: string
  cpf?: string
  address?: string
  city?: string
  state?: string
  zip_code?: string
  neighborhood?: string
  number?: string
  complement?: string
}

interface ClientAuthContextType {
  client: ClientUser | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (email: string, password: string, slug: string) => Promise<void>
  register: (data: RegisterData, slug: string) => Promise<void>
  logout: () => void
  setClient: (client: ClientUser | null) => void
}

interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
  phone: string
  cpf?: string
}

const ClientAuthContext = createContext<ClientAuthContextType | undefined>(undefined)

export function useClientAuth() {
  const context = useContext(ClientAuthContext)
  if (context === undefined) {
    throw new Error('useClientAuth must be used within a ClientAuthProvider')
  }
  return context
}

interface ClientAuthProviderProps {
  children: ReactNode
}

export function ClientAuthProvider({ children }: ClientAuthProviderProps) {
  const [client, setClient] = useState<ClientUser | null>(null)
  const [token, setToken] = useState<string | null>(null)
  const [isAuthenticated, setIsAuthenticated] = useState(false)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    // Load from localStorage
    const savedClient = localStorage.getItem('client-auth-user')
    const savedToken = localStorage.getItem('client-auth-token')

    if (savedClient && savedToken) {
      try {
        const clientData = JSON.parse(savedClient)
        setClient(clientData)
        setToken(savedToken)
        setIsAuthenticated(true)
      } catch (error) {
        console.error('Error loading client auth:', error)
        localStorage.removeItem('client-auth-user')
        localStorage.removeItem('client-auth-token')
      }
    }
    
    setIsLoading(false)
  }, [])

  const login = async (email: string, password: string, slug: string) => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/store/${slug}/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Erro ao fazer login')
    }

    const { client: clientData, token: authToken } = data.data
    
    setClient(clientData)
    setToken(authToken)
    setIsAuthenticated(true)
    
    localStorage.setItem('client-auth-user', JSON.stringify(clientData))
    localStorage.setItem('client-auth-token', authToken)
  }

  const register = async (registerData: RegisterData, slug: string) => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/store/${slug}/auth/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(registerData),
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Erro ao registrar')
    }

    const { client: clientData, token: authToken } = data.data
    
    setClient(clientData)
    setToken(authToken)
    setIsAuthenticated(true)
    
    localStorage.setItem('client-auth-user', JSON.stringify(clientData))
    localStorage.setItem('client-auth-token', authToken)
  }

  const logout = () => {
    setClient(null)
    setToken(null)
    setIsAuthenticated(false)
    localStorage.removeItem('client-auth-user')
    localStorage.removeItem('client-auth-token')
  }

  return (
    <ClientAuthContext.Provider
      value={{
        client,
        token,
        isAuthenticated,
        isLoading,
        login,
        register,
        logout,
        setClient,
      }}
    >
      {children}
    </ClientAuthContext.Provider>
  )
}
