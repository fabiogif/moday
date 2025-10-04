"use client"

import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/button'
import { useRouter } from 'next/navigation'

export function ForceLogoutButton() {
  const [tokenInfo, setTokenInfo] = useState<string>('')
  const router = useRouter()

  useEffect(() => {
    const token = localStorage.getItem('auth-token')
    if (token) {
      const isJWT = token.startsWith('eyJ')
      setTokenInfo(`Token: ${isJWT ? 'JWT válido' : 'INVÁLIDO (não é JWT)'}`)
    } else {
      setTokenInfo('Nenhum token encontrado')
    }
  }, [])

  const forceLogout = () => {
    // Limpar tudo
    localStorage.removeItem('auth-user')
    localStorage.removeItem('auth-token')
    document.cookie = 'auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'
    
    alert('Autenticação limpa! Faça login novamente.')
    router.push('/login')
    router.refresh()
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
