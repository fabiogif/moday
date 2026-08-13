'use client'

import { useEffect, useState } from 'react'
import { Bell } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { useOrderNotifications } from '@/contexts/order-notifications-context'

interface NotificationsButtonProps {
  onClick: () => void
}

export function NotificationsButton({ onClick }: NotificationsButtonProps) {
  const { notifications } = useOrderNotifications()
  const [readIds, setReadIds] = useState<Set<string>>(new Set())

  // Carregar IDs lidos do localStorage
  useEffect(() => {
    const stored = localStorage.getItem('readNotifications')
    if (stored) {
      try {
        setReadIds(new Set(JSON.parse(stored)))
      } catch {
        setReadIds(new Set())
      }
    }
  }, [])

  // Atualizar quando localStorage mudar (sincronizar entre componentes)
  useEffect(() => {
    const handleStorageChange = () => {
      const stored = localStorage.getItem('readNotifications')
      if (stored) {
        try {
          setReadIds(new Set(JSON.parse(stored)))
        } catch {
          setReadIds(new Set())
        }
      }
    }

    window.addEventListener('storage', handleStorageChange)
    
    // Também escutar eventos customizados para mudanças na mesma aba
    const handleCustomChange = () => handleStorageChange()
    window.addEventListener('readNotificationsChanged', handleCustomChange)

    return () => {
      window.removeEventListener('storage', handleStorageChange)
      window.removeEventListener('readNotificationsChanged', handleCustomChange)
    }
  }, [])
  
  // Contar apenas notificações não lidas
  const unreadCount = notifications.filter(n => !readIds.has(n.id)).length

  return (
    <Button
      variant="ghost"
      size="icon"
      className="relative"
      onClick={onClick}
      aria-label="Notificações"
    >
      <Bell className="h-5 w-5" />
      {unreadCount > 0 && (
        <Badge
          variant="destructive"
          className="absolute -top-1 -right-1 h-5 min-w-[20px] flex items-center justify-center p-0 text-xs animate-in zoom-in-50 duration-200"
        >
          {unreadCount > 9 ? '9+' : unreadCount}
        </Badge>
      )}
    </Button>
  )
}

