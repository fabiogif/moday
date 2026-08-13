"use client"

import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Crown, Zap } from "lucide-react"
import { cn } from "@/lib/utils"
import { PlanFeaturesList } from "@/components/plan-features-list"
import type { PublicPlanFeatures } from "@/lib/plan-features"

export interface Plan extends PublicPlanFeatures {
  id: number
  name: string
  url: string
  price: number
  description?: string
  details?: Array<{ name: string }>
}

interface PlanCardProps {
  plan: Plan
  isCurrentPlan: boolean
  onMigrate: (planId: number) => void
  isMigrating?: boolean
}

export function PlanCard({ plan, isCurrentPlan, onMigrate, isMigrating = false }: PlanCardProps) {
  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(price)
  }

  const isPremium = plan.max_users === null || (plan.max_users ?? 0) >= 999999

  return (
    <Card
      className={cn(
        "relative transition-all hover:shadow-lg",
        isCurrentPlan && "ring-2 ring-primary",
        isPremium && "border-primary/50"
      )}
    >
      {isCurrentPlan && (
        <Badge className="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary text-primary-foreground">
          Plano Atual
        </Badge>
      )}
      
      {isPremium && (
        <div className="absolute -top-2 -right-2">
          <Crown className="h-6 w-6 text-yellow-500 fill-yellow-500" />
        </div>
      )}

      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-2xl">{plan.name}</CardTitle>
          {isPremium && <Zap className="h-5 w-5 text-primary" />}
        </div>
        <div className="flex items-baseline gap-1">
          <span className="text-3xl font-bold">{formatPrice(plan.price)}</span>
          <span className="text-muted-foreground text-sm">/mês</span>
        </div>
        {plan.description && (
          <CardDescription>{plan.description}</CardDescription>
        )}
      </CardHeader>

      <CardContent>
        <PlanFeaturesList plan={plan} />
      </CardContent>

      <CardFooter>
        <Button
          className="w-full"
          variant={isCurrentPlan ? "outline" : "default"}
          onClick={() => onMigrate(plan.id)}
          disabled={isCurrentPlan || isMigrating}
        >
          {isCurrentPlan ? 'Plano Atual' : isMigrating ? 'Migrando...' : 'Migrar para este Plano'}
        </Button>
      </CardFooter>
    </Card>
  )
}

