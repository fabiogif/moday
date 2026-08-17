"use client"

import { useEffect } from 'react'
import Link from 'next/link'
import { useSearchParams } from 'next/navigation'
import { ArrowRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { TRIAL_CTA_LABEL, TRIAL_MICRO_COPY } from '@/lib/landing-copy'
import {
  getVariantFromQuery,
  persistLandingVariant,
  resolveLandingVariant,
} from '@/lib/landing-variants'
import { useLandingCTAClick } from '@/hooks/use-landing-cta-click'
import { HeroProductAnimation } from './hero-product-animation'

export function HeroSection() {
  const searchParams = useSearchParams()
  const variant = resolveLandingVariant(searchParams)
  const trackCTA = useLandingCTAClick('cta_hero_click', variant.id)

  useEffect(() => {
    const queryVariant = getVariantFromQuery(searchParams)
    if (queryVariant) persistLandingVariant(queryVariant)
  }, [searchParams])

  return (
    <section
      id="inicio"
      aria-label="Apresentação do Alba Tec, sistema de gestão para restaurantes"
      className="bg-gradient-to-b from-white to-[#F9F5FF] pt-20 sm:pt-28 pb-16 sm:pb-24 border-b border-gray-200"
    >
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16 xl:gap-20">

          {/* Left column */}
          <div>
            <div className="flex items-center gap-2.5 mb-6">
              <span className="w-7 h-px bg-apricot-600 flex-shrink-0" />
              <p className="text-[11px] uppercase tracking-[0.22em] text-zinc-500 font-medium">
                Alba Tec · Sistema para Restaurantes
              </p>
            </div>

            <h1 className="mb-5 text-4xl sm:text-5xl lg:text-5xl xl:text-[3.4rem] font-bold tracking-tight leading-[1.08] text-zinc-900 text-balance">
              {variant.title}
            </h1>

            <p className="mb-7 max-w-lg text-lg text-zinc-500 leading-relaxed">
              {variant.subtitle}
            </p>

            <div className="flex flex-wrap gap-2 mb-8">
              {['PDV touch-first', 'App mobile de pedidos', 'Cardápio digital', 'Relatórios em tempo real'].map((tag) => (
                <span
                  key={tag}
                  className="text-xs font-medium px-2.5 py-1 rounded-md bg-apricot-100 text-primary-700 border border-apricot-200"
                >
                  {tag}
                </span>
              ))}
            </div>

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
              <Button
                size="lg"
                className="bg-primary text-white hover:bg-primary-600 rounded-md h-11 px-7 text-sm font-semibold transition-colors"
                asChild
              >
                <Link href="/auth/register" className="flex items-center gap-2" onClick={() => trackCTA('/auth/register')}>
                  {TRIAL_CTA_LABEL}
                  <ArrowRight className="h-4 w-4" />
                </Link>
              </Button>
              <Link href="/auth/login">
                <Button
                  variant="outline"
                  size="lg"
                  className="w-full sm:w-auto rounded-md h-11 px-6 text-sm border-zinc-300 text-zinc-700 hover:bg-zinc-100 transition-colors"
                >
                  Fazer Login
                </Button>
              </Link>
            </div>

            <p className="mt-3.5 text-xs text-zinc-400">{TRIAL_MICRO_COPY}</p>

            <div className="mt-8 flex flex-wrap items-center gap-5 text-sm text-zinc-500 pt-6 border-t border-zinc-200">
              <span className="font-medium text-zinc-700">7 dias de teste grátis</span>
              <span className="w-px h-4 bg-zinc-300 hidden sm:block" aria-hidden />
              <span>Sem cartão de crédito</span>
              <span className="w-px h-4 bg-zinc-300 hidden sm:block" aria-hidden />
              <span>PDV, cardápio digital e app</span>
            </div>
          </div>

          {/* Right column — product animation */}
          <div className="flow-reveal-up [animation-delay:200ms]">
            <HeroProductAnimation />
          </div>

        </div>
      </div>
    </section>
  )
}
