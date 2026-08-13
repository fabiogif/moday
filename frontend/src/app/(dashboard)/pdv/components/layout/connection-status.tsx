"use client"

import { Badge } from "@/components/ui/badge"
import { Wifi, WifiOff, RefreshCw } from "lucide-react"
import { cn } from "@/lib/utils"
import { useEffect, useState } from "react"

interface ConnectionStatusProps {
  className?: string
}

export function ConnectionStatus({ className }: ConnectionStatusProps) {
  const [isOnline, setIsOnline] = useState(true)
  const [isSyncing, setIsSyncing] = useState(false)

  useEffect(() => {
    // Verificar status online/offline do navegador
    const handleOnline = () => setIsOnline(true)
    const handleOffline = () => setIsOnline(false)

    setIsOnline(navigator.onLine)

    window.addEventListener("online", handleOnline)
    window.addEventListener("offline", handleOffline)

    return () => {
      window.removeEventListener("online", handleOnline)
      window.removeEventListener("offline", handleOffline)
    }
  }, [])

  // Simular sincronização (pode ser integrado com real-time depois)
  const handleSync = () => {
    setIsSyncing(true)
    setTimeout(() => {
      setIsSyncing(false)
    }, 1000)
  }

  return (
    <div className={cn("flex items-center gap-2", className)}>
      <Badge
        variant={isOnline ? "default" : "secondary"}
        className={cn(
          "flex items-center gap-1.5 px-2.5 py-1 text-xs transition-all",
          isOnline && "bg-emerald-500 hover:bg-emerald-600 text-white"
        )}
      >
        {isOnline ? (
          <Wifi className="h-3.5 w-3.5" />
        ) : (
          <WifiOff className="h-3.5 w-3.5" />
        )}
        <span className="font-medium">{isOnline ? "Online" : "Offline"}</span>
      </Badge>

      {isOnline && (
        <button
          onClick={handleSync}
          disabled={isSyncing}
          className="p-1.5 rounded-md hover:bg-muted transition-colors disabled:opacity-50"
          title="Sincronizar"
        >
          <RefreshCw
            className={cn(
              "h-3.5 w-3.5 text-muted-foreground",
              isSyncing && "animate-spin"
            )}
          />
        </button>
      )}
    </div>
  )
}

