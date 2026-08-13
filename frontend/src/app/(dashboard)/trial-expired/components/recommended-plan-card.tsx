"use client"

import { useEffect, useState, memo } from "react"
import Link from "next/link"
import { ArrowRight, Loader2, Sparkles, Zap } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { apiClient, endpoints } from "@/lib/api-client"
import type { PublicPlanFeatures } from "@/lib/plan-features"

type Plan = PublicPlanFeatures & {
  id: number
  name: string
  price: string | number
  description: string | null
}

function formatPrice(price: string | number) {
  return Number(price).toFixed(2).replace(".", ",")
}

function pickRecommended(plans: Plan[]): Plan | null {
  const paid = plans.filter((p) => Number(p.price) > 0)
  if (paid.length === 0) return plans[0] ?? null
  // Preferência: plano do meio (mais popular) entre os pagos
  return paid[Math.floor(paid.length / 2)] ?? paid[0]
}

type RecommendedPlanCardProps = {
  subscribeBaseHref?: string
}

function RecommendedPlanCardComponent({
  subscribeBaseHref = "/subscribe",
}: RecommendedPlanCardProps) {
  const [plan, setPlan] = useState<Plan | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false

    async function load() {
      try {
        const res = await apiClient.get<Plan[]>(endpoints.subscription.plans)
        if (cancelled) return
        const list = Array.isArray(res.data) ? res.data : []
        setPlan(pickRecommended(list))
      } catch {
        if (!cancelled) setPlan(null)
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    void load()
    return () => {
      cancelled = true
    }
  }, [])

  const href = plan ? `${subscribeBaseHref}?plan_id=${plan.id}` : subscribeBaseHref

  return (
    <section
      aria-labelledby="recommended-plan-heading"
      className="relative overflow-hidden rounded-2xl border-2 border-primary bg-card p-5 sm:p-6 shadow-md"
    >
      <div className="absolute inset-x-0 top-0 h-1 bg-primary" aria-hidden />

      <div className="flex flex-wrap items-center gap-2 mb-4">
        <Badge className="bg-primary text-primary-foreground">
          <Sparkles className="h-3 w-3 mr-1" aria-hidden />
          Plano recomendado
        </Badge>
        <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
          <Zap className="h-3 w-3" aria-hidden />
          Ideal para continuar sem interrupções
        </span>
      </div>

      {loading ? (
        <div className="flex items-center justify-center gap-2 py-10 text-muted-foreground">
          <Loader2 className="h-5 w-5 animate-spin" aria-hidden />
          <span className="text-sm">Carregando plano…</span>
        </div>
      ) : plan ? (
        <div className="space-y-4">
          <div>
            <h2 id="recommended-plan-heading" className="text-2xl font-bold tracking-tight">
              {plan.name}
            </h2>
            {plan.description ? (
              <p className="text-sm text-muted-foreground mt-1">{plan.description}</p>
            ) : null}
          </div>

          <p className="flex items-baseline gap-1">
            <span className="text-3xl font-bold tracking-tight">R$ {formatPrice(plan.price)}</span>
            <span className="text-sm text-muted-foreground">/mês</span>
          </p>

          <Button
            asChild
            size="lg"
            className="w-full h-12 text-base font-semibold shadow-md hover:shadow-lg transition-all hover:scale-[1.01]"
          >
            <Link href={href} aria-label={`Assinar agora o plano ${plan.name}`}>
              Assinar Agora
              <ArrowRight className="ml-2 h-5 w-5" aria-hidden />
            </Link>
          </Button>

          <p className="text-center text-xs text-muted-foreground">
            Ou{" "}
            <Link href={subscribeBaseHref} className="underline underline-offset-4 hover:text-foreground">
              compare todos os planos
            </Link>
          </p>
        </div>
      ) : (
        <div className="space-y-4 py-2">
          <h2 id="recommended-plan-heading" className="text-xl font-bold">
            Escolha o plano ideal
          </h2>
          <p className="text-sm text-muted-foreground">
            Veja os planos disponíveis e reative seu acesso em poucos minutos.
          </p>
          <Button asChild size="lg" className="w-full h-12 font-semibold">
            <Link href={subscribeBaseHref}>
              Ver Planos e Assinar
              <ArrowRight className="ml-2 h-5 w-5" aria-hidden />
            </Link>
          </Button>
        </div>
      )}
    </section>
  )
}

export const RecommendedPlanCard = memo(RecommendedPlanCardComponent)
