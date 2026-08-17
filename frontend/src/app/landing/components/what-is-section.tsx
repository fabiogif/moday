'use client'

import Link from 'next/link'
import {
  ClipboardList,
  MonitorSmartphone,
  QrCode,
  LineChart,
  ArrowRight,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { TRIAL_CTA_LABEL } from '@/lib/landing-copy'
import { useLandingCTAClick } from '@/hooks/use-landing-cta-click'

const pillars = [
  {
    icon: MonitorSmartphone,
    title: 'PDV touch-first',
    description:
      'Registre pedidos no balcão ou no salão com fluxo rápido, variações e opcionais sem erros de digitação.',
  },
  {
    icon: QrCode,
    title: 'Cardápio digital',
    description:
      'Publique o cardápio online com link e QR Code para WhatsApp e redes sociais — o cliente pede com clareza.',
  },
  {
    icon: ClipboardList,
    title: 'Pedidos em tempo real',
    description:
      'Acompanhe status da cozinha ao delivery, com app mobile para aceitar e avançar pedidos onde estiver.',
  },
  {
    icon: LineChart,
    title: 'Financeiro e relatórios',
    description:
      'Veja vendas, produtos mais vendidos e indicadores do dia — decisões com dados, não com planilha.',
  },
]

export function WhatIsSection() {
  const trackCTA = useLandingCTAClick('cta_what_is_click')

  return (
    <section
      id="o-que-e"
      aria-labelledby="what-is-heading"
      className="py-20 sm:py-24 bg-white border-b border-zinc-200"
    >
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="max-w-2xl mb-14">
          <p className="text-[11px] uppercase tracking-[0.18em] text-orange-600 font-medium mb-4">
            O que é o Alba Tec
          </p>
          <h2
            id="what-is-heading"
            className="text-3xl font-bold tracking-[-0.02em] sm:text-4xl mb-4 text-zinc-900 text-balance"
          >
            Sistema de gestão para restaurantes em um só lugar
          </h2>
          <p className="text-lg text-zinc-500 leading-relaxed">
            O Alba Tec é um sistema de gestão para restaurantes pensado para quem vende no salão,
            no balcão e no delivery. Você centraliza PDV, cardápio digital, mesas, estoque e
            relatórios na nuvem — sem depender de planilhas ou de vários aplicativos desconectados.
          </p>
        </div>

        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-12">
          {pillars.map((item) => (
            <div key={item.title} className="rounded-xl border border-zinc-200 bg-stone-50 p-5">
              <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                <item.icon className="h-5 w-5" aria-hidden />
              </div>
              <h3 className="text-sm font-semibold text-zinc-900 mb-1.5">{item.title}</h3>
              <p className="text-sm text-zinc-500 leading-relaxed">{item.description}</p>
            </div>
          ))}
        </div>

        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <Button
            className="bg-zinc-900 text-white hover:bg-zinc-700 rounded-md h-10 px-6 text-sm"
            asChild
          >
            <Link href="/auth/register" onClick={() => trackCTA('/auth/register')}>
              {TRIAL_CTA_LABEL}
              <ArrowRight className="ml-2 h-4 w-4" />
            </Link>
          </Button>
          <Button
            variant="outline"
            className="rounded-md h-10 px-6 text-sm border-zinc-300 text-zinc-700"
            asChild
          >
            <Link href="/demo/menu">Ver demonstração do cardápio</Link>
          </Button>
          <Link
            href="#pricing"
            className="text-sm font-medium text-zinc-600 hover:text-zinc-900 underline-offset-4 hover:underline sm:ml-2"
          >
            Ver planos e preços
          </Link>
        </div>
      </div>
    </section>
  )
}
