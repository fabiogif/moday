"use client"

import { useEffect, useState } from "react"
import { Progress } from "@/components/ui/progress"
import { cn } from "@/lib/utils"

interface LoadingProgressProps {
  isLoading?: boolean
  message?: string
  className?: string
  showPercentage?: boolean
  indeterminate?: boolean
}

export function LoadingProgress({
  isLoading = true,
  message = "Carregando...",
  className,
  showPercentage = false,
  indeterminate = true,
}: LoadingProgressProps) {
  const [progress, setProgress] = useState(0)

  useEffect(() => {
    if (!isLoading) {
      setProgress(0)
      return
    }

    if (indeterminate) {
      // Simular progresso indeterminado
      const interval = setInterval(() => {
        setProgress((prev) => {
          if (prev >= 90) return 0
          return prev + Math.random() * 10
        })
      }, 200)

      return () => clearInterval(interval)
    } else {
      // Progresso determinado
      const interval = setInterval(() => {
        setProgress((prev) => {
          if (prev >= 100) return 100
          return prev + Math.random() * 15
        })
      }, 100)

      return () => clearInterval(interval)
    }
  }, [isLoading, indeterminate])

  if (!isLoading) return null

  return (
    <div className={cn("w-full space-y-2", className)}>
      <div className="flex items-center justify-between text-sm">
        <span className="text-muted-foreground">{message}</span>
        {showPercentage && (
          <span className="text-muted-foreground">
            {Math.round(progress)}%
          </span>
        )}
      </div>
      <Progress 
        value={indeterminate ? progress : progress} 
        className="h-2"
      />
    </div>
  )
}

// Componente de loading para páginas inteiras
interface PageLoadingProps {
  isLoading?: boolean
  message?: string
  className?: string
}

export function PageLoading({
  isLoading = true,
  message = "Carregando página...",
  className,
}: PageLoadingProps) {
  if (!isLoading) return null

  return (
    <div className={cn(
      "flex flex-col items-center justify-center min-h-[400px] space-y-4",
      className
    )}>
      <LoadingProgress 
        isLoading={isLoading}
        message={message}
        indeterminate={true}
        showPercentage={false}
        className="w-full max-w-md"
      />
    </div>
  )
}

// Componente de loading para cards/seções
interface CardLoadingProps {
  isLoading?: boolean
  message?: string
  className?: string
}

export function CardLoading({
  isLoading = true,
  message = "Carregando...",
  className,
}: CardLoadingProps) {
  if (!isLoading) return null

  return (
    <div className={cn(
      "flex flex-col items-center justify-center p-8 space-y-4",
      className
    )}>
      <LoadingProgress 
        isLoading={isLoading}
        message={message}
        indeterminate={true}
        showPercentage={false}
        className="w-full"
      />
    </div>
  )
}
