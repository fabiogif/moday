"use client"

import { Check, X } from 'lucide-react'
import { cn } from '@/lib/utils'
import {
  buildPlanDetailItems,
  buildPlanLimitItems,
  buildPlanModuleGroups,
  type PublicPlanFeatures,
} from '@/lib/plan-features'

interface PlanFeaturesListProps {
  plan: PublicPlanFeatures
  highlight?: boolean
}

export function PlanFeaturesList({ plan, highlight = false }: PlanFeaturesListProps) {
  const limits = buildPlanLimitItems(plan)
  const moduleGroups = buildPlanModuleGroups(plan)
  const details = buildPlanDetailItems(plan)

  const checkClass = highlight ? 'text-primary' : 'text-emerald-500'
  const includedTextClass = 'text-foreground/80'
  const excludedTextClass = 'text-muted-foreground line-through decoration-muted-foreground/50'

  return (
    <div className="space-y-5 text-sm flex-1">
      <div>
        <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">
          Limites do plano
        </p>
        <ul className="space-y-2">
          {limits.map((limit) => (
            <li key={limit.label} className="flex items-start gap-3">
              <Check className={cn('size-4 flex-shrink-0 mt-0.5', checkClass)} strokeWidth={2.5} />
              <span className={includedTextClass}>
                <span className="font-medium text-foreground">{limit.label}:</span> {limit.value}
              </span>
            </li>
          ))}
        </ul>
      </div>

      {moduleGroups.map((module) => (
        <div key={module.id}>
          <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">
            Módulo {module.label}
          </p>
          <ul className="space-y-2">
            {module.options.map((option) => (
              <li key={option.key} className="flex items-start gap-3">
                {option.included ? (
                  <Check className={cn('size-4 flex-shrink-0 mt-0.5', checkClass)} strokeWidth={2.5} />
                ) : (
                  <X className="size-4 flex-shrink-0 mt-0.5 text-muted-foreground/70" strokeWidth={2.5} />
                )}
                <span className={option.included ? includedTextClass : excludedTextClass}>
                  {option.label}
                </span>
              </li>
            ))}
          </ul>
        </div>
      ))}

      {details.length > 0 && (
        <div>
          <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">
            Recursos adicionais
          </p>
          <ul className="space-y-2">
            {details.map((detail) => (
              <li key={detail} className="flex items-start gap-3">
                <Check className={cn('size-4 flex-shrink-0 mt-0.5', checkClass)} strokeWidth={2.5} />
                <span className={includedTextClass}>{detail}</span>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  )
}
