"use client"

import { Badge } from "@/components/ui/badge"
import { Lock, CheckCircle2, XCircle } from "lucide-react"
import { cn } from "@/lib/utils"
import { isFinalStatus, getStatusColor, getStatusDescription } from "@/lib/order-status"

interface OrderStatusBadgeProps {
  status: string | null | undefined
  className?: string
  showIcon?: boolean
  showDescription?: boolean
}

export function OrderStatusBadge({
  status,
  className,
  showIcon = true,
  showDescription = false,
}: OrderStatusBadgeProps) {
  if (!status) {
    return (
      <Badge variant="outline" className={className}>
        Sem status
      </Badge>
    )
  }

  const isFinal = isFinalStatus(status)
  const color = getStatusColor(status)
  const description = getStatusDescription(status)

  const getStatusIcon = () => {
    if (!showIcon) return null

    switch (status) {
      case "Concluído":
        return <CheckCircle2 className="h-3 w-3" />
      case "Cancelado":
        return <XCircle className="h-3 w-3" />
      default:
        if (isFinal) {
          return <Lock className="h-3 w-3" />
        }
        return null
    }
  }

  const getVariant = () => {
    if (isFinal) {
      switch (status) {
        case "Concluído":
          return "default"
        case "Cancelado":
          return "destructive"
        default:
          return "secondary"
      }
    }

    switch (color) {
      case "yellow":
        return "outline"
      case "blue":
        return "default"
      case "indigo":
        return "default"
      default:
        return "outline"
    }
  }

  return (
    <div className="flex items-center gap-2">
      <Badge
        variant={getVariant()}
        className={cn(
          "flex items-center gap-1.5",
          isFinal && "border-2",
          className
        )}
        title={showDescription ? undefined : description}
      >
        {getStatusIcon()}
        <span>{status}</span>
      </Badge>
      {showDescription && (
        <span className="text-xs text-muted-foreground">{description}</span>
      )}
    </div>
  )
}

