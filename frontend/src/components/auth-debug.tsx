/**
 * Componente de debug para verificar o status da autenticação
 */

"use client"

import { useAuth } from '@/contexts/auth-context'
import { apiClient } from '@/lib/api-client'

export function AuthDebug() {
  const { user, token, isAuthenticated } = useAuth()

  const checkToken = () => {
    const cookies = document.cookie.split(';')
    const authCookie = cookies.find(cookie => cookie.trim().startsWith('auth-token='))
    const localStorageToken = localStorage.getItem('auth_token')
    
    console.log('=== DEBUG AUTENTICAÇÃO ===')
    console.log('AuthStore - isAuthenticated:', isAuthenticated)
    console.log('AuthStore - user:', user)
    console.log('AuthStore - hasToken:', !!token)
    console.log('localStorage hasToken:', !!localStorageToken)
    console.log('Cookie hasToken:', !!authCookie)
    console.log('ApiClient hasToken:', !!(apiClient as any).token)
    console.log('========================')
  }

  if (process.env.NODE_ENV !== 'development') {
    return null
  }

  return (
    <div className="fixed bottom-4 right-4 bg-black/80 text-white p-4 rounded-lg text-xs max-w-sm">
      <div className="font-bold mb-2">Auth Debug</div>
      <div>Authenticated: {isAuthenticated ? '✅' : '❌'}</div>
      <div>User: {user ? user.name : 'None'}</div>
      <div>Token: {token ? '✅' : '❌'}</div>
      <button 
        onClick={checkToken}
        className="mt-2 bg-blue-600 px-2 py-1 rounded text-xs"
      >
        Log Debug
      </button>
    </div>
  )
}
