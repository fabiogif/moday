"use client"

import { createContext, useContext, useEffect, useState, ReactNode } from 'react'
import { useParams } from 'next/navigation'
import { buildApiUrl } from '@/lib/api-config'

export interface ClientUser {
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
  register: (data: RegisterData, slug: string) => Promise<ClientUser>
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

// A conta do cliente pertence a uma loja: a sessão fica guardada por slug
const userKey = (slug: string) => `client-auth-user:${slug}`
const tokenKey = (slug: string) => `client-auth-token:${slug}`
// Sessão antiga (sem loja) — descartada para não valer em outra loja
const LEGACY_KEYS = ['client-auth-user', 'client-auth-token']

function persistSession(slug: string, clientData: ClientUser, authToken: string) {
  localStorage.setItem(userKey(slug), JSON.stringify(clientData))
  localStorage.setItem(tokenKey(slug), authToken)
}

function clearSession(slug: string) {
  localStorage.removeItem(userKey(slug))
  localStorage.removeItem(tokenKey(slug))
}

export function ClientAuthProvider({ children }: ClientAuthProviderProps) {
  const [client, setClient] = useState<ClientUser | null>(null)
  const [token, setToken] = useState<string | null>(null)
  const [isAuthenticated, setIsAuthenticated] = useState(false)
  const [isLoading, setIsLoading] = useState(true)
  const params = useParams()
  const currentSlug = typeof params?.slug === 'string' ? params.slug : ''

  useEffect(() => {
    LEGACY_KEYS.forEach((key) => localStorage.removeItem(key))

    setClient(null)
    setToken(null)
    setIsAuthenticated(false)

    const savedClient = currentSlug ? localStorage.getItem(userKey(currentSlug)) : null
    const savedToken = currentSlug ? localStorage.getItem(tokenKey(currentSlug)) : null

    if (savedClient && savedToken) {
      try {
        setClient(JSON.parse(savedClient))
        setToken(savedToken)
        setIsAuthenticated(true)
      } catch {
        clearSession(currentSlug)
      }
    }

    setIsLoading(false)
  }, [currentSlug])

  const login = async (email: string, password: string, slug: string) => {
    const response = await fetch(buildApiUrl(`/api/store/${slug}/auth/login`), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ email, password }),
      // Deixa o navegador guardar o cookie httpOnly da sessão (usado pelo auth/me no checkout)
      credentials: 'include',
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Erro ao fazer login')
    }

    const { client: clientData, token: authToken } = data.data
    
    setClient(clientData)
    setToken(authToken)
    setIsAuthenticated(true)
    
    persistSession(slug, clientData, authToken)
  }

  const register = async (registerData: RegisterData, slug: string) => {
    const response = await fetch(buildApiUrl(`/api/store/${slug}/auth/register`), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(registerData),
      credentials: 'include',
    })

    const data = await response.json()

    if (!response.ok) {
      // 422 traz o motivo real por campo (ex.: "Email já cadastrado nesta loja"); a message é genérica
      const firstFieldError = data.errors ? Object.values(data.errors as Record<string, string[]>).flat()[0] : undefined
      throw new Error(firstFieldError || data.message || 'Erro ao registrar')
    }

    const { client: clientData, token: authToken } = data.data
    
    setClient(clientData)
    setToken(authToken)
    setIsAuthenticated(true)
    
    persistSession(slug, clientData, authToken)

    return clientData
  }

  const logout = () => {
    setClient(null)
    setToken(null)
    setIsAuthenticated(false)
    if (currentSlug) clearSession(currentSlug)
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
