"use client"

import { CheckCircle2 } from 'lucide-react'

type UseCase = {
  title: string
  context: string
  outcome: string
  points: string[]
}

const useCases: UseCase[] = [
  {
    title: 'Restaurante a la carte',
    context: 'Salão com mesas e pedidos concorrentes',
    outcome: 'Menos erros entre salão e cozinha',
    points: [
      'PDV touch-first para anotar pedidos com variações',
      'Status em tempo real para a produção',
      'Relatórios do dia para fechar o caixa com clareza',
    ],
  },
  {
    title: 'Pizzaria e delivery',
    context: 'Alto volume de personalização e WhatsApp',
    outcome: 'Cardápio digital que o cliente entende',
    points: [
      'Tamanhos, sabores e adicionais com preço automático',
      'Link e QR Code para compartilhar no WhatsApp',
      'App mobile para aceitar pedidos fora do balcão',
    ],
  },
  {
    title: 'Rede com várias unidades',
    context: 'Mais de um ponto de venda na mesma operação',
    outcome: 'Visão consolidada sem planilhas',
    points: [
      'Cardápios e equipes por unidade nos planos avançados',
      'Indicadores de vendas e produtos por período',
      'Financeiro centralizado para acompanhar o negócio',
    ],
  },
  {
    title: 'Lanchonete e balcão',
    context: 'Fila rápida e ticket médio baixo',
    outcome: 'Atendimento mais ágil no PDV',
    points: [
      'Fluxo enxuto para pedidos de balcão',
      'Controle de estoque para itens de alta rotação',
      'Histórico de pedidos para repetir vendas frequentes',
    ],
  },
]

export function TestimonialsSection() {
  return (
    <section id="testimonials" className="py-24 sm:py-32 bg-stone-50 border-t border-zinc-200">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="max-w-2xl mb-16">
          <p className="text-[11px] uppercase tracking-[0.18em] text-orange-600 font-medium mb-4">
            Casos de uso
          </p>
          <h2 className="text-3xl sm:text-4xl font-bold tracking-[-0.02em] text-zinc-900 mb-4">
            Como restaurantes usam o Alba Tec no dia a dia
          </h2>
          <p className="text-lg text-zinc-500 leading-relaxed">
            Cenários reais de operação — do salão ao delivery — com o sistema de gestão para
            restaurantes centralizando pedidos, cardápio e indicadores.
          </p>
        </div>

        <div className="grid gap-5 sm:grid-cols-2 max-w-5xl">
          {useCases.map((item) => (
            <article
              key={item.title}
              className="rounded-xl border border-zinc-200 bg-white p-6 hover:border-zinc-300 transition-colors"
            >
              <h3 className="font-semibold text-zinc-900 text-base mb-1">{item.title}</h3>
              <p className="text-xs text-zinc-500 mb-3">{item.context}</p>
              <p className="text-sm font-medium text-orange-700 mb-4">{item.outcome}</p>
              <ul className="space-y-2">
                {item.points.map((point) => (
                  <li key={point} className="flex gap-2 text-sm text-zinc-600 leading-relaxed">
                    <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" aria-hidden />
                    <span>{point}</span>
                  </li>
                ))}
              </ul>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
