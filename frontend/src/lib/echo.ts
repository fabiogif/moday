import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

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
    authEndpoint: `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost'}/broadcasting/auth`,
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

  const token = localStorage.getItem('token')
  const appKey = process.env.NEXT_PUBLIC_REVERB_APP_KEY
  
  if (!token) {
    // Usuário não está autenticado ainda - isso é normal durante o carregamento
    console.info('Echo: Waiting for authentication...')
    return null
  }

  if (!appKey) {
    console.warn('Echo: NEXT_PUBLIC_REVERB_APP_KEY is not set; realtime disabled')
    return null
  }

  try {
    if (window.Echo) {
      window.Echo.disconnect()
    }

    window.Echo = createEchoInstance(token)
    
    console.log('Echo: Initialized successfully', {
      host: process.env.NEXT_PUBLIC_REVERB_HOST,
      port: process.env.NEXT_PUBLIC_REVERB_PORT,
    })
    
    return window.Echo
  } catch (error) {
    console.warn('Echo: Could not initialize WebSocket (optional feature)', {
      error: error instanceof Error ? error.message : error,
      tip: 'Start Reverb server with: php artisan reverb:start'
    })
    return null
  }
}

export const disconnectEcho = () => {
  if (typeof window !== 'undefined' && window.Echo) {
    window.Echo.disconnect()
    console.log('Echo: Disconnected')
  }
}

export default typeof window !== 'undefined' ? window.Echo : null
