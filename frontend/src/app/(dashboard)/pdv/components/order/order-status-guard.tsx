"use client"

import { ReactNode } from "react"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Lock, Info } from "lucide-react"
import { cn } from "@/lib/utils"
import { isFinalStatus, getStatusDescription } from "@/lib/order-status"
import { OrderStatusBadge } from "./order-status-badge"

interface OrderStatusGuardProps {
  status: string | null | undefined
  children: ReactNode
  fallback?: ReactNode
  showAlert?: boolean
  allowViewOnly?: boolean
  className?: string
}

/**
 * Componente que protege ações baseado no status do pedido
 * Desabilita edição para pedidos com status final
 */
export function OrderStatusGuard({
  status,
  children,
  fallback,
  showAlert = true,
  allowViewOnly = true,
  className,
}: OrderStatusGuardProps) {
  const isFinal = isFinalStatus(status)
  const description = getStatusDescription(status)

  if (isFinal) {
    if (fallback) {
      return <>{fallback}</>
    }

    if (allowViewOnly) {
      return (
        <div className={cn("space-y-3", className)}>
          {showAlert && (
            <Alert className="border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30">
              <Lock className="h-4 w-4 text-amber-600 dark:text-amber-400" />
              <AlertTitle className="text-amber-900 dark:text-amber-100">
                Pedido Finalizado
              </AlertTitle>
              <AlertDescription className="text-amber-800 dark:text-amber-200">
                Este pedido possui status final e não pode ser editado. Apenas
                visualização, impressão e exportação são permitidas.
              </AlertDescription>
              <div className="mt-2">
                <OrderStatusBadge status={status} />
              </div>
            </Alert>
          )}
          <div className="opacity-60 pointer-events-none">{children}</div>
        </div>
      )
    }

    return null
  }

  return <>{children}</>
}

interface OrderStatusTooltipProps {
  status: string | null | undefined
  action: string
  children: ReactNode
}

/**
 * Componente que adiciona tooltip explicativo quando ação não é permitida
 */
export function OrderStatusTooltip({
  status,
  action,
  children,
}: OrderStatusTooltipProps) {
  const isFinal = isFinalStatus(status)

  if (isFinal) {
    return (
      <div className="relative group">
        {children}
        <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-50">
          <div className="bg-popover border rounded-lg p-2 shadow-lg text-xs max-w-xs">
            <div className="flex items-start gap-2">
              <Info className="h-3 w-3 text-muted-foreground mt-0.5 shrink-0" />
              <div>
                <p className="font-semibold">
                  {action} não permitida
                </p>
                <p className="text-muted-foreground">
                  Pedido com status final ({status}) não pode ser editado.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    )
  }

  return <>{children}</>
}

