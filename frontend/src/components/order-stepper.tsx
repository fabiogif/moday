"use client"

import { cn } from "@/lib/utils"
import { Check, type LucideIcon } from "lucide-react"

interface Step {
  label: string
  icon: LucideIcon
}

interface OrderStepperProps {
  currentStep: number
  steps: Step[]
  onStepClick?: (step: number) => void
  completedSteps?: Set<number>
}

export function OrderStepper({
  currentStep,
  steps,
  onStepClick,
  completedSteps = new Set(),
}: OrderStepperProps) {
  return (
    <div className="flex w-full min-w-0 items-center justify-center gap-1 overflow-x-hidden sm:gap-2">
      {steps.map((step, index) => {
        const isCompleted = completedSteps.has(index)
        const isCurrent = index === currentStep
        const isClickable = onStepClick && (isCompleted || index <= currentStep)
        const Icon = step.icon

        return (
          <div key={index} className="flex min-w-0 flex-1 items-center last:flex-none last:flex-initial">
            <button
              type="button"
              onClick={() => isClickable && onStepClick?.(index)}
              disabled={!isClickable}
              className={cn(
                "flex min-w-0 max-w-full flex-col items-center gap-1 transition-colors sm:flex-row sm:gap-2",
                isClickable && "cursor-pointer",
                !isClickable && "cursor-default"
              )}
            >
              <div
                className={cn(
                  "flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-medium transition-colors sm:h-9 sm:w-9",
                  isCurrent && "bg-primary text-primary-foreground",
                  isCompleted && !isCurrent && "bg-green-600 text-white",
                  !isCurrent && !isCompleted && "bg-muted text-muted-foreground"
                )}
              >
                {isCompleted && !isCurrent ? (
                  <Check className="h-4 w-4" />
                ) : (
                  <Icon className="h-4 w-4" />
                )}
              </div>
              <span
                className={cn(
                  "max-w-[4.5rem] text-center text-[10px] leading-tight font-medium break-words sm:max-w-[7rem] sm:text-left sm:text-xs md:max-w-none md:text-sm",
                  isCurrent && "text-foreground",
                  !isCurrent && "text-muted-foreground"
                )}
              >
                {step.label}
              </span>
            </button>

            {index < steps.length - 1 && (
              <div
                className={cn(
                  "mx-1 h-0.5 min-w-2 flex-1 rounded-full transition-colors sm:mx-2",
                  isCompleted ? "bg-green-600" : "bg-muted"
                )}
              />
            )}
          </div>
        )
      })}
    </div>
  )
}
