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
    <section className="py-8 lg:py-10 bg-primary-900 border-t border-primary-800">
      <div className="container mx-auto px-4 lg:px-8">
        <div className="grid items-center gap-6 lg:grid-cols-2 lg:gap-6">
          <div className="flow-reveal-up hidden lg:block">
            <OperationFlowAnimation />
          </div>

          <div className="flow-reveal-up [animation-delay:150ms]">
            <div className="mb-5 flex items-center gap-2.5">
              <span className="w-7 h-px bg-primary-500 flex-shrink-0" />
              <p className="text-[11px] uppercase tracking-[0.22em] text-stone-400 font-medium">
                Sistema de Gestão Completo
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-4 text-sm text-stone-400 mb-6">
              <span className="flex items-center gap-1.5">
                <span className="w-1.5 h-1.5 rounded-full bg-primary-400 flex-shrink-0" />
                Teste grátis por 7 dias
              </span>
              <span className="text-stone-700">·</span>
              <span>Sem cartão de crédito</span>
              <span className="text-stone-700">·</span>
              <span>Suporte por e-mail e WhatsApp</span>
            </div>

            <h2 className="text-4xl font-bold tracking-tight text-balance text-stone-50 sm:text-5xl mb-5">
              Revolucione a gestão do seu{' '}
              <span className="text-primary-300">restaurante</span>{' '}
              hoje
            </h2>

            <p className="max-w-2xl text-balance text-lg text-stone-400 leading-relaxed mb-2">
              Pare de usar planilhas e cadernos. Tenha controle total do seu negócio com relatórios
              em tempo real, cardápio digital e gestão de pedidos profissional.
            </p>
            <p className="max-w-2xl font-medium text-stone-300 mb-6">
              Teste os planos Básico e Premium por {TRIAL_DAYS} dias grátis — ou comece no plano Grátis para sempre.
            </p>

            <div className="flex flex-col gap-3 sm:flex-row">
              <Button
                size="lg"
                className="h-11 rounded-md bg-primary-500 px-7 text-sm font-semibold text-stone-900 hover:bg-primary-300 transition-colors"
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
                className="h-11 rounded-md border-primary-700 bg-transparent px-7 text-sm text-stone-200 hover:bg-primary-800 hover:text-white transition-colors"
                asChild
              >
                <Link href="#pricing">
                  Ver Planos e Preços
                  <ArrowRight className="ml-2 h-4 w-4" />
                </Link>
              </Button>
            </div>

            <p className="mt-4 text-sm text-stone-500">{TRIAL_MICRO_COPY}</p>

            <div className="mt-6 flex flex-wrap items-center gap-5 text-sm text-stone-500">
              <span className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-primary-400" />
                Teste grátis por {TRIAL_DAYS} dias nos planos pagos
              </span>
              <span className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-primary-300" />
                Sem cartão de crédito
              </span>
              <span className="flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-primary-500" />
                Suporte especializado
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
