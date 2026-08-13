"use client"

import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/button'
import { clearAuthSession, getAuthToken } from '@/lib/auth-storage'

export function ForceLogoutButton() {
  const [tokenInfo, setTokenInfo] = useState<string>('')

  useEffect(() => {
    const token = getAuthToken()
    if (token) {
      const isJWT = token.startsWith('eyJ')
      setTokenInfo(`Token: ${isJWT ? 'JWT válido' : 'INVÁLIDO (não é JWT)'}`)
    } else {
      setTokenInfo('Nenhum token encontrado')
    }
  }, [])

  const forceLogout = () => {
    // Limpar tudo
    clearAuthSession()
    
    alert('Autenticação limpa! Faça login novamente.')
    window.location.href = '/auth/login'
  }

  if (process.env.NODE_ENV !== 'development') {
    return null
  }

  return (
    <div className="fixed top-4 right-4 bg-red-600 text-white p-4 rounded-lg shadow-lg z-50">
      <div className="text-xs mb-2">{tokenInfo}</div>
      <Button 
        onClick={forceLogout}
        variant="secondary"
        size="sm"
      >
        Forçar Logout
      </Button>
    </div>
  )
}
