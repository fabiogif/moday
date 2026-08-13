"use client"

import type { LucideIcon } from "lucide-react"
import { cn } from "@/lib/utils"

interface EmptyStateAction {
  label: string
  onClick: () => void
}

interface EmptyStateProps {
  icon?: LucideIcon
  title: string
  description?: string
  action?: EmptyStateAction
  variant?: "default" | "error"
  size?: "sm" | "default"
  className?: string
}

export function EmptyState({
  icon: Icon,
  title,
  description,
  action,
  variant = "default",
  size = "default",
  className,
}: EmptyStateProps) {
  const isError = variant === "error"

  return (
    <div
      className={cn(
        "flex flex-col items-center justify-center text-center",
        size === "sm" ? "gap-1.5 py-4" : "gap-2 py-8",
        className
      )}
    >
      {Icon && (
        <Icon
          className={cn(
            size === "sm" ? "h-6 w-6" : "h-10 w-10",
            "mb-1",
            isError ? "text-destructive/60" : "text-muted-foreground/50"
          )}
        />
      )}
      <p className={cn("font-medium", size === "sm" ? "text-sm" : "text-base", isError && "text-destructive")}>
        {title}
      </p>
      {description && (
        <p className="max-w-xs text-sm text-muted-foreground">{description}</p>
      )}
      {action && (
        <button
          type="button"
          onClick={action.onClick}
          className="mt-1 text-sm font-medium text-primary hover:underline"
        >
          {action.label}
        </button>
      )}
    </div>
  )
}
