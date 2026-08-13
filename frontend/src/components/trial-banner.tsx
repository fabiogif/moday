"use client"

import { Alert, AlertDescription } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { AlertCircle, Clock, CreditCard } from "lucide-react"
import Link from "next/link"
import { useAuth } from "@/contexts/auth-context"
import { Progress } from "@/components/ui/progress"
import { TRIAL_DAYS } from "@/lib/subscription"

export function TrialBanner() {
  const { trialStatus } = useAuth()

  // Não mostrar se não tiver trial status ou se não for trial
  if (!trialStatus || !trialStatus.is_trial) {
    return null
  }

  // Não mostrar se trial expirado (será tratado pela página de upgrade)
  if (trialStatus.is_expired) {
    return null
  }

  const daysRemaining = trialStatus.days_remaining
  const percentage = ((TRIAL_DAYS - daysRemaining) / TRIAL_DAYS) * 100

  // Definir cor e intensidade baseado nos dias restantes
  const getVariant = () => {
    if (daysRemaining <= 1) return "destructive"
    if (daysRemaining <= 3) return "warning"
    return "default"
  }

  const getIcon = () => {
    if (daysRemaining <= 1) return AlertCircle
    if (daysRemaining <= 3) return Clock
    return Clock
  }

  const variant = getVariant()
  const Icon = getIcon()

  return (
    <Alert className={`mb-4 ${
      variant === "destructive" ? "border-red-500 bg-red-50 dark:bg-red-950" :
      variant === "warning" ? "border-yellow-500 bg-yellow-50 dark:bg-yellow-950" :
      "border-blue-500 bg-blue-50 dark:bg-blue-950"
    }`}>
      <Icon className={`h-4 w-4 ${
        variant === "destructive" ? "text-red-600" :
        variant === "warning" ? "text-yellow-600" :
        "text-blue-600"
      }`} />
      <AlertDescription className="flex items-center justify-between">
        <div className="flex-1">
          <div className="font-semibold mb-1">
            {daysRemaining === 0 && "⚠️ Seu período de teste expira hoje!"}
            {daysRemaining === 1 && "⚠️ Último dia do período de teste!"}
            {daysRemaining > 1 && `⏰ Período de teste: ${daysRemaining} dias restantes`}
          </div>
          
          <div className="text-sm opacity-90 mb-2">
            {daysRemaining === 0 && "Não perca o acesso ao Alba Tec. Faça upgrade agora!"}
            {daysRemaining === 1 && "Amanhã seu acesso será bloqueado. Escolha um plano hoje!"}
            {daysRemaining > 1 && "Continue aproveitando todos os recursos. Escolha um plano antes que expire."}
          </div>

          <div className="flex items-center gap-2 mb-2">
            <Progress value={percentage} className="w-full max-w-xs h-2" />
            <span className="text-xs font-medium">{Math.round(percentage)}%</span>
          </div>
        </div>

        <div className="flex gap-2 ml-4">
          <Link href="/subscribe">
            <Button 
              size="sm"
              className={
                variant === "destructive" ? "bg-red-600 hover:bg-red-700" :
                variant === "warning" ? "bg-yellow-600 hover:bg-yellow-700" :
                "bg-blue-600 hover:bg-blue-700"
              }
            >
              <CreditCard className="h-4 w-4 mr-2" />
              {daysRemaining <= 1 ? "Fazer Upgrade Agora" : "Ver Planos"}
            </Button>
          </Link>
        </div>
      </AlertDescription>
    </Alert>
  )
}

