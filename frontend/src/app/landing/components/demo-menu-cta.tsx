"use client"

import { Button } from '@/components/ui/button'
import { ArrowRight, MenuSquare, Layers, PlusCircle, ShoppingCart } from 'lucide-react'
import Link from 'next/link'
import { MenuFlowAnimation } from './menu-flow-animation'

const features = [
  { icon: MenuSquare, title: 'Cardápio Digital', desc: 'Interface moderna' },
  { icon: Layers, title: 'Variações', desc: 'Tamanhos e sabores' },
  { icon: PlusCircle, title: 'Opcionais', desc: 'Personalização total' },
]

export function DemoMenuCTA() {
  return (
    <section className="py-24 sm:py-32 bg-zinc-900 border-t border-zinc-800">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">

          <div className="flow-reveal-up order-2 lg:order-1">
            <p className="text-[11px] uppercase tracking-[0.22em] text-orange-400 font-medium mb-4">
              Experimente Agora
            </p>
            <h2 className="text-3xl sm:text-4xl font-bold tracking-[-0.02em] text-zinc-50 text-balance mb-4">
              Veja o Cardápio em Ação
            </h2>
            <p className="max-w-lg text-lg leading-relaxed text-zinc-400 mb-8">
              Explore nosso cardápio interativo de demonstração e descubra como seus clientes podem
              personalizar pedidos com variações, opcionais e cálculo de preços em tempo real.
            </p>

            <div className="grid gap-3 sm:grid-cols-3 mb-8">
              {features.map((item) => (
                <div
                  key={item.title}
                  className="rounded-xl border border-zinc-700 bg-zinc-800/50 p-4"
                >
                  <div className="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-700">
                    <item.icon className="h-4 w-4 text-zinc-300" />
                  </div>
                  <h3 className="text-sm font-semibold text-zinc-100">{item.title}</h3>
                  <p className="text-xs text-zinc-400 mt-0.5">{item.desc}</p>
                </div>
              ))}
            </div>

            <div className="flex flex-col gap-3 sm:flex-row">
              <Button
                size="lg"
                className="rounded-md bg-white text-zinc-900 hover:bg-zinc-100 h-11 px-7 text-sm font-semibold transition-colors"
                asChild
              >
                <Link href="/demo/menu">
                  <ShoppingCart className="mr-2 h-4 w-4" />
                  Ver Cardápio de Demonstração
                  <ArrowRight className="ml-2 h-4 w-4" />
                </Link>
              </Button>
              <Button
                size="lg"
                variant="outline"
                className="rounded-md border-zinc-700 bg-transparent text-zinc-200 hover:bg-zinc-800 hover:text-white h-11 px-6 text-sm transition-colors"
                asChild
              >
                <Link href="#pricing">Ver Planos e Preços</Link>
              </Button>
            </div>

            <p className="mt-4 text-xs text-zinc-500">
              Experimente gratuitamente sem necessidade de cadastro
            </p>
          </div>

          <div className="flow-reveal-up order-1 lg:order-2 [animation-delay:150ms]">
            <MenuFlowAnimation />
          </div>

        </div>
      </div>
    </section>
  )
}
