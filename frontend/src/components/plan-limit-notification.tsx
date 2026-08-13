"use client"

import { useEffect, useState } from "react"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { X, AlertTriangle } from "lucide-react"
import { usePlanLimits } from "@/hooks/use-plan-limits"
import { useRouter } from "next/navigation"
import { cn } from "@/lib/utils"

interface PlanLimitNotificationProps {
  onDismiss?: () => void
}

export function PlanLimitNotification({ onDismiss }: PlanLimitNotificationProps) {
  const { hasLimitReached, message, planName, reachedLimits, loading } = usePlanLimits()
  const router = useRouter()
  const [dismissed, setDismissed] = useState(false)

  // Verificar se foi fechada no localStorage
  useEffect(() => {
    if (typeof window !== 'undefined') {
      const dismissedKey = `plan_limit_dismissed_${Date.now().toString().slice(0, -6)}` // Por dia
      const wasDismissed = localStorage.getItem(dismissedKey)
      if (wasDismissed) {
        setDismissed(true)
      }
    }
  }, [])

  const handleDismiss = () => {
    if (typeof window !== 'undefined') {
      const dismissedKey = `plan_limit_dismissed_${Date.now().toString().slice(0, -6)}`
      localStorage.setItem(dismissedKey, 'true')
      setDismissed(true)
      onDismiss?.()
    }
  }

  const handleMigrate = () => {
    router.push('/settings/company#planos')
  }

  if (loading || !hasLimitReached || dismissed) {
    return null
  }

  return (
    <Alert className="border-yellow-500 bg-yellow-50 dark:bg-yellow-950/30 dark:border-yellow-700 mb-4">
      <AlertTriangle className="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1">
          <AlertTitle className="text-yellow-900 dark:text-yellow-100 font-semibold">
            Limite do Plano Atingido
          </AlertTitle>
          <AlertDescription className="text-yellow-800 dark:text-yellow-200 mt-1">
            {message || `Você atingiu o limite do plano ${planName}.`}
          </AlertDescription>
          {reachedLimits.length > 0 && (
            <div className="mt-2 flex flex-wrap gap-2">
              {reachedLimits.map((limit) => (
                <span
                  key={limit}
                  className="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-yellow-200 dark:bg-yellow-900 text-yellow-900 dark:text-yellow-100"
                >
                  {limit === 'users' && 'Usuários'}
                  {limit === 'products' && 'Produtos'}
                  {limit === 'orders' && 'Pedidos'}
                </span>
              ))}
            </div>
          )}
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={handleMigrate}
            className="bg-yellow-600 hover:bg-yellow-700 text-white border-yellow-600"
          >
            Migrar de Plano
          </Button>
          <Button
            variant="ghost"
            size="icon"
            onClick={handleDismiss}
            aria-label="close"
            className="h-8 w-8 text-yellow-600 hover:text-yellow-700 dark:text-yellow-400 dark:hover:text-yellow-300"
          >
            <X className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </Alert>
  )
}

