"use client"

import Link from 'next/link'
import { ArrowRight, CheckCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { TRIAL_CTA_LABEL, TRIAL_MICRO_COPY } from '@/lib/landing-copy'
import { TRIAL_DAYS } from '@/lib/subscription'
import { useLandingCTAClick } from '@/hooks/use-landing-cta-click'
import { OperationFlowAnimation } from './operation-flow-animation'

export function CTASection() {
  const trackCTA = useLandingCTAClick('cta_final_click')

  return (
    <section className="py-16 lg:py-24 bg-zinc-900 border-t border-zinc-800">
      <div className="container mx-auto px-4 lg:px-8">
        <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
          <div className="flow-reveal-up hidden lg:block">
            <OperationFlowAnimation />
          </div>

          <div className="flow-reveal-up [animation-delay:150ms]">
            <div className="mb-5 flex items-center gap-2.5">
              <span className="w-7 h-px bg-orange-500 flex-shrink-0" />
              <p className="text-[11px] uppercase tracking-[0.22em] text-zinc-400 font-medium">
                Sistema de Gestão Completo
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-4 text-sm text-zinc-400 mb-6">
              <span className="flex items-center gap-1.5">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0" />
                Teste grátis por 7 dias
              </span>
              <span className="text-zinc-700">·</span>
              <span>Sem cartão de crédito</span>
              <span className="text-zinc-700">·</span>
              <span>Suporte por e-mail e WhatsApp</span>
            </div>

            <h2 className="text-4xl font-bold tracking-tight text-balance text-zinc-50 sm:text-5xl mb-5">
              Revolucione a gestão do seu{' '}
              <span className="text-orange-400">restaurante</span>{' '}
              hoje
            </h2>

            <p className="max-w-2xl text-balance text-lg text-zinc-400 leading-relaxed mb-2">
              Pare de usar planilhas e cadernos. Tenha controle total do seu negócio com relatórios
              em tempo real, cardápio digital e gestão de pedidos profissional.
            </p>
            <p className="max-w-2xl font-medium text-zinc-300 mb-8">
              Teste os planos Básico e Premium por {TRIAL_DAYS} dias grátis — ou comece no plano Grátis para sempre.
            </p>

            <div className="flex flex-col gap-3 sm:flex-row">
              <Button
                size="lg"
                className="h-11 rounded-md bg-white px-7 text-sm font-semibold text-zinc-900 hover:bg-zinc-100 transition-colors"
                asChild
              >
                <Link href="/auth/register" onClick={() => trackCTA('/auth/register')}>
                  <CheckCircle className="mr-2 h-4 w-4" />
                  {TRIAL_CTA_LABEL}
                </Link>
              </Button>
              <Button
                variant="outline"
                size="lg"
                className="h-11 rounded-md border-zinc-700 bg-transparent px-7 text-sm text-zinc-200 hover:bg-zinc-800 hover:text-white transition-colors"
                asChild
              >
                <Link href="#pricing">
                  Ver Planos e Preços
                  <ArrowRight className="ml-2 h-4 w-4" />
                </Link>
              </Button>
            </div>

            <p className="mt-4 text-sm text-zinc-500">{TRIAL_MICRO_COPY}</p>

            <div className="mt-6 flex flex-wrap items-center gap-5 text-sm text-zinc-500">
              <span className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                Teste grátis por {TRIAL_DAYS} dias nos planos pagos
              </span>
              <span className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-blue-400" />
                Sem cartão de crédito
              </span>
              <span className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-orange-400" />
                Suporte especializado
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
