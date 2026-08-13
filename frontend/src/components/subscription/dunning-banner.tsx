"use client"

import { AlertTriangle, XCircle } from "lucide-react"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { useRouter } from "next/navigation"
import type { SubscriptionStatus2 } from "@/types/subscription"

interface DunningBannerProps {
  status: SubscriptionStatus2
}

export function DunningBanner({ status }: DunningBannerProps) {
  const router = useRouter()

  if (!status.is_in_dunning && !status.is_delinquent && !status.is_suspended) {
    return null
  }

  const daysLeft = status.is_in_dunning ? Math.max(0, 7 - status.dunning_day) : 0

  const config = status.is_suspended
    ? {
        variant: "destructive" as const,
        icon: <XCircle className="h-4 w-4" />,
        title: "Assinatura suspensa",
        message: "Sua assinatura está suspensa por inadimplência. Regularize o pagamento para recuperar o acesso.",
      }
    : status.is_delinquent
    ? {
        variant: "destructive" as const,
        icon: <XCircle className="h-4 w-4" />,
        title: "Acesso bloqueado",
        message: "Seu acesso foi bloqueado por falta de pagamento. Atualize seu método de pagamento agora.",
      }
    : {
        variant: "default" as const,
        icon: <AlertTriangle className="h-4 w-4" />,
        title: `Pagamento pendente — ${daysLeft} dia${daysLeft !== 1 ? "s" : ""} para bloqueio`,
        message: `Há um pagamento pendente na sua assinatura. Regularize para evitar o bloqueio do acesso.`,
      }

  return (
    <Alert variant={config.variant} className="mb-4">
      {config.icon}
      <AlertTitle>{config.title}</AlertTitle>
      <AlertDescription className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <span>{config.message}</span>
        <Button
          size="sm"
          variant={config.variant === "destructive" ? "outline" : "default"}
          onClick={() => router.push("/subscribe")}
          className="shrink-0"
        >
          Regularizar pagamento
        </Button>
      </AlertDescription>
    </Alert>
  )
}
