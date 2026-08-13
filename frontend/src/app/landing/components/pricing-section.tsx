"use client"

import { Zap } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import ApiClient from '@/lib/api-client'
import { cn } from '@/lib/utils'
import { TRIAL_DAYS } from '@/lib/subscription'
import { trackLandingCTAClick } from '@/lib/landing-analytics'
import { PlanFeaturesList } from '@/components/plan-features-list'
import type { PublicPlanFeatures } from '@/lib/plan-features'

interface Plan extends PublicPlanFeatures {
  id: number
  name: string
  url: string
  price: number | string
  description: string | null
}

const STATIC_PLANS: Plan[] = [
  {
    id: 1,
    name: 'Grátis',
    url: 'gratis',
    price: 0,
    description: 'Perfeito para testar o sistema',
    max_users: 1,
    max_products: 50,
    max_orders_per_month: 30,
    has_marketing: false,
    has_order_completion_email: false,
    has_reports: false,
    details: [
      { id: 1, name: 'Painel administrativo básico', plan_id: 1 },
      { id: 2, name: 'Cardápio digital', plan_id: 1 },
    ],
  },
  {
    id: 2,
    name: 'Básico',
    url: 'basico',
    price: 49.9,
    description: 'Perfeito para pequenos negócios começando',
    max_users: 5,
    max_products: 100,
    max_orders_per_month: 100,
    has_marketing: true,
    has_order_completion_email: true,
    has_reports: true,
    details: [
      { id: 3, name: 'Painel administrativo completo', plan_id: 2 },
      { id: 4, name: 'Cardápio digital personalizado', plan_id: 2 },
      { id: 5, name: 'Suporte por e-mail', plan_id: 2 },
    ],
  },
  {
    id: 3,
    name: 'Premium',
    url: 'premium',
    price: 99.9,
    description: 'Solução completa para grandes operações',
    max_users: null,
    max_products: null,
    max_orders_per_month: null,
    has_marketing: true,
    has_order_completion_email: true,
    has_reports: true,
    details: [
      { id: 6, name: 'Suporte prioritário via WhatsApp', plan_id: 3 },
      { id: 7, name: 'Múltiplos usuários e permissões', plan_id: 3 },
      { id: 8, name: 'Integrações com delivery', plan_id: 3 },
      { id: 9, name: 'Personalização completa da marca', plan_id: 3 },
      { id: 10, name: 'Relatórios avançados e exportação', plan_id: 3 },
      { id: 11, name: 'Controle de estoque inteligente', plan_id: 3 },
    ],
  },
]

function normalizePlan(plan: Plan): Plan {
  return {
    ...plan,
    price: typeof plan.price === 'string' ? parseFloat(plan.price) : plan.price,
    max_users: plan.max_users ?? null,
    max_products: plan.max_products ?? null,
    max_orders_per_month: plan.max_orders_per_month ?? null,
    has_marketing: Boolean(plan.has_marketing),
    has_order_completion_email: Boolean(plan.has_order_completion_email),
    has_reports: Boolean(plan.has_reports),
    details: plan.details ?? [],
  }
}

export function PricingSection() {
  const router = useRouter()
  const [isYearly, setIsYearly] = useState(false)
  const [plans, setPlans] = useState<Plan[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const fetchPlans = async () => {
      try {
        const response = await ApiClient.get<Plan[]>('/api/public/plans')
        if (response?.data) {
          const rawPlans = Array.isArray(response.data) ? response.data : []
          setPlans(rawPlans.map(normalizePlan))
        }
      } catch {
        setPlans(STATIC_PLANS)
      } finally {
        setLoading(false)
      }
    }

    fetchPlans()
  }, [])

  const handleSelectPlan = (planId: number) => {
    trackLandingCTAClick('cta_pricing_click', `/auth/register?plan=${planId}`)
    router.push(`/auth/register?plan=${planId}`)
  }

  const isPopular = (index: number) => index === 1

  if (loading) {
    return (
      <section id="pricing" className="py-24 sm:py-32 bg-stone-50 border-t border-zinc-200">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-zinc-400 mx-auto"></div>
            <p className="text-zinc-500 mt-2">Carregando planos...</p>
          </div>
        </div>
      </section>
    )
  }

  return (
    <section id="pricing" className="py-24 sm:py-32 bg-stone-50 border-t border-zinc-200">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mx-auto max-w-2xl text-center mb-12">
          <p className="text-[11px] uppercase tracking-[0.22em] text-orange-600 font-medium mb-4">
            Planos e Preços
          </p>
          <h2 className="text-3xl font-bold tracking-tight sm:text-5xl mb-4">
            Simples, transparente e sem surpresas
          </h2>
          <p className="text-lg text-muted-foreground mb-3">
            Escolha o plano ideal para o seu negócio. Comece grátis e escale quando precisar.
          </p>
          <p className="text-sm font-medium text-emerald-600 mb-8">
            Planos Básico e Premium incluem {TRIAL_DAYS} dias de teste grátis com acesso completo. Sem cartão de crédito.
          </p>

          <div className="flex items-center justify-center mb-2">
            <ToggleGroup
              type="single"
              value={isYearly ? 'yearly' : 'monthly'}
              onValueChange={(value) => setIsYearly(value === 'yearly')}
              className="bg-secondary text-secondary-foreground border-none rounded-full p-1 cursor-pointer shadow-none"
            >
              <ToggleGroupItem
                value="monthly"
                className="data-[state=on]:bg-background data-[state=on]:border-border border-transparent border px-6 !rounded-full data-[state=on]:text-foreground hover:bg-transparent cursor-pointer transition-colors"
              >
                Mensal
              </ToggleGroupItem>
              <ToggleGroupItem
                value="yearly"
                className="data-[state=on]:bg-background data-[state=on]:border-border border-transparent border px-6 !rounded-full data-[state=on]:text-foreground hover:bg-transparent cursor-pointer transition-colors"
              >
                Anual
              </ToggleGroupItem>
            </ToggleGroup>
          </div>

          {isYearly ? (
            <p className="text-sm font-medium text-emerald-600">
              Economize 20% com o plano anual
            </p>
          ) : (
            <p className="text-sm text-zinc-500">
              Troque para anual e <span className="text-orange-600 font-semibold">economize 20%</span>
            </p>
          )}
        </div>

        <div className="mx-auto max-w-7xl">
          <div className="flex flex-col lg:flex-row lg:flex-nowrap gap-4 lg:gap-3 items-stretch">
            {plans.map((plan, index) => {
              const popular = isPopular(index)
              const isFree = Number(plan.price) === 0

              return (
                <div
                  key={plan.url || `plan-${index}`}
                  className={cn(
                    'relative flex flex-1 min-w-0 flex-col rounded-2xl border p-5 xl:p-6 transition-colors flow-reveal-up',
                    popular
                      ? 'border-zinc-900 bg-white ring-1 ring-zinc-900 shadow-md'
                      : 'border-zinc-200 bg-white'
                  )}
                  style={{ animationDelay: `${index * 100}ms` }}
                >
                  {popular && (
                    <div className="absolute -top-3.5 left-1/2 -translate-x-1/2">
                      <span className="inline-flex items-center gap-1 rounded-full bg-white border border-zinc-200 px-4 py-1 text-xs font-semibold text-zinc-900 shadow-sm">
                        <Zap className="h-3 w-3" />
                        Mais popular
                      </span>
                    </div>
                  )}

                  <div className="mb-6">
                    <div className="text-lg font-bold tracking-tight mb-1 text-zinc-900">
                      {plan.name}
                    </div>
                    <div className="text-sm text-zinc-500">
                      {plan.description || 'Plano completo para seu negócio'}
                    </div>
                  </div>

                  <div className="mb-5">
                    <div className="flex items-end gap-1">
                      {isFree ? (
                        <span className="text-3xl xl:text-4xl font-bold text-zinc-900">Grátis</span>
                      ) : (
                        <>
                          <span className="text-sm self-start mt-2 text-zinc-500">R$</span>
                          <span className="text-3xl xl:text-4xl font-bold tabular-nums text-zinc-900">
                            {isYearly
                              ? (Number(plan.price) * 0.8).toFixed(2)
                              : Number(plan.price).toFixed(2)}
                          </span>
                          <span className="text-sm mb-1 text-zinc-500">/mês</span>
                        </>
                      )}
                    </div>
                    {!isFree && isYearly && (
                      <p className="text-xs mt-1 text-zinc-500">
                        Cobrado anualmente · R$ {(Number(plan.price) * 12 * 0.8).toFixed(2)}/ano
                      </p>
                    )}
                    {isFree && (
                      <p className="text-xs mt-1 text-zinc-500">Para sempre, sem cartão</p>
                    )}
                    {!isFree && (
                      <p className="text-xs font-medium text-emerald-500 mt-1">
                        {TRIAL_DAYS} dias de teste grátis
                      </p>
                    )}
                  </div>

                  <Button
                    onClick={() => handleSelectPlan(plan.id)}
                    className={cn(
                      'w-full cursor-pointer mb-6 rounded-md h-11 text-sm font-semibold transition-colors',
                      popular
                        ? 'bg-zinc-900 text-white hover:bg-zinc-700'
                        : 'border-zinc-300 text-zinc-700 hover:bg-zinc-100'
                    )}
                    variant={popular ? 'default' : 'outline'}
                    size="lg"
                  >
                    {isFree ? 'Começar de graça' : 'Iniciar teste grátis'}
                  </Button>

                  <PlanFeaturesList plan={plan} highlight={popular} />
                </div>
              )
            })}
          </div>
        </div>

        <div className="mt-16 text-center">
          <p className="text-zinc-500">
            Precisa de recursos personalizados?{' '}
            <a href="#contact" className="text-zinc-900 underline decoration-zinc-300 hover:decoration-zinc-600 transition-colors">
              Entre em contato com nossa equipe
            </a>
          </p>
        </div>
      </div>
    </section>
  )
}
