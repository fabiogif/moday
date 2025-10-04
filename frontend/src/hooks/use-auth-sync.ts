/**
 * Hook para sincronizar autenticação entre AuthContext e ApiClient
 */

import { useEffect } from 'react'
import { useAuth } from '@/contexts/auth-context'
import { apiClient } from '@/lib/api-client'

export function useAuthSync() {
  const { token, isAuthenticated, user } = useAuth()

  useEffect(() => {
    if (process.env.NODE_ENV === 'development') {
      console.log('AuthSync: Sincronizando autenticação', { isAuthenticated, hasToken: !!token })
    }
    
    if (isAuthenticated && token) {
      // Sincronizar token do AuthContext para o ApiClient
      if (process.env.NODE_ENV === 'development') {
        console.log('AuthSync: Definindo token no ApiClient')
      }
      apiClient.setToken(token)
    } else {
      // Limpar token se não estiver autenticado
      if (process.env.NODE_ENV === 'development') {
        console.log('AuthSync: Limpando token do ApiClient')
      }
      apiClient.clearToken()
    }
  }, [isAuthenticated, token])

  // Forçar recarga do token a cada mudança
  useEffect(() => {
    apiClient.reloadToken()
  }, [])

  return { isAuthenticated, user, token }
}
