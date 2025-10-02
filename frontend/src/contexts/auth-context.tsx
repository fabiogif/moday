"use client"

import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface User {
  id: string
  name: string
  email: string
  tenant_id?: string
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => void
  setUser: (user: User) => void
  setToken: (token: string) => void
  setLoading: (loading: boolean) => void
}

interface AuthError {
  message: string
  errors?: Record<string, string[]>
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,

      login: async (email: string, password: string) => {
        set({ isLoading: true })
        try {
          const response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password }),
          })
          if (!response.ok) {
            const error: AuthError = await response.json()
            console.log('AuthStore: Erro recebido:', error)
            
            // Se há erros de validação específicos, criar mensagem detalhada
            if (error.errors) {
              const fieldErrors = Object.entries(error.errors)
                .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                .join('; ')
              console.log('AuthStore: Erros de campo:', fieldErrors)
              throw new Error(`${error.message}. ${fieldErrors}`)
            }
            
            console.log('AuthStore: Erro simples:', error.message)
            throw new Error(error.message || 'Erro ao fazer login')
          }

          const data = await response.json()
          
          set({
            user: data.user,
            token: data.token,
            isAuthenticated: true,
            isLoading: false,
          })

          // Salvar token no cookie
          document.cookie = `auth-token=${data.token}; path=/; max-age=${7 * 24 * 60 * 60}` // 7 dias
        } catch (error) {
          set({ isLoading: false })
          throw error
        }
      },

      logout: () => {
        set({
          user: null,
          token: null,
          isAuthenticated: false,
          isLoading: false,
        })
        
        // Remover token do cookie
        document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'
      },

      setUser: (user: User) => {
        set({ user, isAuthenticated: true })
      },

      setToken: (token: string) => {
        set({ token, isAuthenticated: true })
      },

      setLoading: (loading: boolean) => {
        set({ isLoading: loading })
      },
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
)
