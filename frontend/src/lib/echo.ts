import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { buildApiUrl } from './api-config'

declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo<any>
  }
}

// Make Pusher available globally (only on client side)
if (typeof window !== 'undefined') {
  window.Pusher = Pusher
}

// Configure Echo instance
export const createEchoInstance = (token: string) => {
  return new Echo({
    broadcaster: 'reverb',
    key: process.env.NEXT_PUBLIC_REVERB_APP_KEY as string,
    wsHost: process.env.NEXT_PUBLIC_REVERB_HOST || 'localhost',
    wsPort: process.env.NEXT_PUBLIC_REVERB_PORT ? parseInt(process.env.NEXT_PUBLIC_REVERB_PORT) : 8080,
    wssPort: process.env.NEXT_PUBLIC_REVERB_PORT ? parseInt(process.env.NEXT_PUBLIC_REVERB_PORT) : 8080,
    forceTLS: (process.env.NEXT_PUBLIC_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: buildApiUrl('/broadcasting/auth'),
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })
}

export const initializeEcho = () => {
  if (typeof window === 'undefined') {
    // Server-side rendering - isso é esperado e não é um erro
    return null
  }

  const token = localStorage.getItem('auth-token') || localStorage.getItem('token')
  const appKey = process.env.NEXT_PUBLIC_REVERB_APP_KEY
  
  if (!token) {
    // Usuário não está autenticado ainda - isso é normal durante o carregamento
    return null
  }

  if (!appKey) {
    return null
  }

  try {
    if (window.Echo) {
      window.Echo.disconnect()
    }

    window.Echo = createEchoInstance(token)

    return window.Echo
  } catch (error) {
    console.error('Erro ao inicializar Echo:', {
      error: error instanceof Error ? error.message : error,
      tip: 'Start Reverb server with: php artisan reverb:start'
    })
    return null
  }
}

export const disconnectEcho = () => {
  if (typeof window !== 'undefined' && window.Echo) {
    window.Echo.disconnect()
  }
}

export default typeof window !== 'undefined' ? window.Echo : null
