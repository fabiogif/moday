"use client"

import { useEffect, useState } from 'react'
import { Check } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { apiClient, endpoints } from '@/lib/api-client'
import type { PlanWithDetails } from '@/types/plan'
import Link from 'next/link'

export default function PlansPage() {
  const [plans, setPlans] = useState<PlanWithDetails[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    async function loadPlans() {
      try {
        setLoading(true)
        const response = await apiClient.get<PlanWithDetails[]>(endpoints.plans.list)
        
        if (response.success && response.data) {
          setPlans(Array.isArray(response.data) ? response.data : [])
        }
      } catch (err: any) {

        setError(err.message || 'Erro ao carregar planos')
      } finally {
        setLoading(false)
      }
    }

    loadPlans()
  }, [])

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4" />
          <p className="text-muted-foreground">Carregando planos...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-destructive mb-4">{error}</p>
          <Button onClick={() => window.location.reload()}>Tentar novamente</Button>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="border-b">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <h1 className="text-2xl font-bold">Alba Tec</h1>
            <div className="flex gap-4">
              <Button variant="outline" asChild>
                <Link href="/login">Entrar</Link>
              </Button>
              <Button asChild>
                <Link href="/register">Começar</Link>
              </Button>
            </div>
          </div>
        </div>
      </header>

      {/* Pricing Section */}
      <section className="py-24 sm:py-32">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          {/* Section Header */}
          <div className="mx-auto max-w-2xl text-center mb-16">
            <Badge variant="outline" className="mb-4">Planos e Preços</Badge>
            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl mb-4">
              Escolha o plano ideal para você
            </h2>
            <p className="text-lg text-muted-foreground">
              Gerencie seu negócio com facilidade. Escolha o plano que melhor se adapta às suas necessidades.
            </p>
          </div>

          {/* Pricing Cards */}
          {plans.length === 0 ? (
            <div className="text-center text-muted-foreground">
              Nenhum plano disponível no momento.
            </div>
          ) : (
            <div className="mx-auto max-w-6xl">
              <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                {plans.map((plan) => (
                  <div
                    key={plan.id}
                    className="relative rounded-xl border bg-card p-8 shadow-sm hover:shadow-lg transition-shadow"
                  >
                    {/* Plan Header */}
                    <div className="mb-6">
                      <h3 className="text-2xl font-bold mb-2">{plan.name}</h3>
                      {plan.description && (
                        <p className="text-muted-foreground text-sm">{plan.description}</p>
                      )}
                    </div>

                    {/* Pricing */}
                    <div className="mb-6">
                      <div className="flex items-baseline gap-1">
                        <span className="text-4xl font-bold">
                          R$ {plan.price.toFixed(2).replace('.', ',')}
                        </span>
                        <span className="text-muted-foreground">/mês</span>
                      </div>
                    </div>

                    {/* CTA Button */}
                    <Button className="w-full mb-6" size="lg" asChild>
                      <Link href="/register">
                        Começar Agora
                      </Link>
                    </Button>

                    {/* Features */}
                    {plan.details && plan.details.length > 0 && (
                      <div>
                        <h4 className="font-semibold mb-4">O que está incluído:</h4>
                        <ul className="space-y-3">
                          {plan.details.map((detail) => (
                            <li key={detail.id} className="flex items-start gap-3">
                              <Check className="text-primary size-5 flex-shrink-0 mt-0.5" strokeWidth={2.5} />
                              <span className="text-sm">{detail.name}</span>
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Enterprise Note */}
          <div className="mt-16 text-center">
            <p className="text-muted-foreground">
              Precisa de um plano personalizado?{' '}
              <Button variant="link" className="p-0 h-auto" asChild>
                <Link href="/contact">
                  Entre em contato
                </Link>
              </Button>
            </p>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t py-8">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <p className="text-center text-muted-foreground text-sm">
            © {new Date().getFullYear()} Alba Tec. Todos os direitos reservados.
          </p>
        </div>
      </footer>
    </div>
  )
}
