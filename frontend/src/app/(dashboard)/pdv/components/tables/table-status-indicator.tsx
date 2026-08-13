"use client"

import { Badge } from "@/components/ui/badge"
import { Utensils, Circle } from "lucide-react"
import { cn } from "@/lib/utils"

interface TableStatusIndicatorProps {
  isOccupied: boolean
  orderCount?: number
  className?: string
  size?: "sm" | "md" | "lg"
}

export function TableStatusIndicator({
  isOccupied,
  orderCount = 0,
  className,
  size = "md",
}: TableStatusIndicatorProps) {
  const sizeClasses = {
    sm: "h-2 w-2",
    md: "h-2.5 w-2.5",
    lg: "h-3 w-3",
  }

  if (isOccupied) {
    return (
      <div className={cn("flex items-center gap-1.5", className)}>
        <Circle
          className={cn(
            "fill-red-500 text-red-500",
            sizeClasses[size]
          )}
        />
        {orderCount > 0 && (
          <Badge variant="destructive" className="text-[10px] px-1.5 py-0.5">
            {orderCount} pedido{orderCount !== 1 ? "s" : ""}
          </Badge>
        )}
      </div>
    )
  }

  return (
    <div className={cn("flex items-center gap-1.5", className)}>
      <Circle
        className={cn(
          "fill-green-500 text-green-500",
          sizeClasses[size]
        )}
      />
      <span className="text-xs text-muted-foreground">Disponível</span>
    </div>
  )
}

