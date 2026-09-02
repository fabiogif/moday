"use client"

import { Check, X } from 'lucide-react'

const withoutSystem = [
  'Pedidos anotados no papel ou em planilhas separadas do financeiro',
  'Estoque controlado de cabeça, sem alerta de falta ou validade',
  'Fechamento de caixa manual, conferido item por item',
  'Cardápio desatualizado, enviado por foto no WhatsApp',
  'Cada unidade com seus próprios números, sem visão consolidada',
]

const withSystem = [
  'PDV, cardápio digital e financeiro centralizados no mesmo painel',
  'Alertas de estoque baixo e histórico de custo por produto',
  'Relatórios de vendas e fechamento de caixa em tempo real',
  'Cardápio digital com link e QR Code sempre atualizado',
  'Painel consolidado de vendas e financeiro por unidade',
]

export function ComparisonSection() {
  return (
    <section
      id="comparacao"
      aria-labelledby="comparison-heading"
      className="py-10 sm:py-12 bg-primary-50 border-t border-zinc-200"
    >
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="max-w-2xl mb-6">
          <p className="text-[11px] uppercase tracking-[0.22em] text-primary-700 font-medium mb-4">
            Antes e depois
          </p>
          <h2
            id="comparison-heading"
            className="text-3xl sm:text-4xl font-bold tracking-[-0.02em] text-zinc-900 text-balance mb-4"
          >
            Menos planilhas, mais controle sobre o restaurante
          </h2>
          <p className="text-lg text-zinc-500 leading-relaxed">
            A rotina muda pouco a pouco: pedidos, estoque e financeiro saem de várias
            ferramentas soltas e passam a viver em um único painel.
          </p>
        </div>

        <div className="grid gap-5 lg:grid-cols-2 max-w-5xl">
          <div className="rounded-2xl border border-zinc-200 bg-stone-50 p-6 sm:p-8">
            <h3 className="text-sm font-semibold uppercase tracking-wide text-zinc-500 mb-5">
              Gestão manual e fragmentada
            </h3>
            <ul className="space-y-4">
              {withoutSystem.map((item) => (
                <li key={item} className="flex items-start gap-3">
                  <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-zinc-200">
                    <X className="h-3 w-3 text-zinc-500" aria-hidden />
                  </span>
                  <span className="text-sm text-zinc-600 leading-relaxed">{item}</span>
                </li>
              ))}
            </ul>
          </div>

          <div className="rounded-2xl border border-primary-200 bg-primary-50/60 p-6 sm:p-8">
            <h3 className="text-sm font-semibold uppercase tracking-wide text-primary-800 mb-5">
              Gestão centralizada com o Alba Tec
            </h3>
            <ul className="space-y-4">
              {withSystem.map((item) => (
                <li key={item} className="flex items-start gap-3">
                  <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-100">
                    <Check className="h-3 w-3 text-primary-700" aria-hidden />
                  </span>
                  <span className="text-sm text-zinc-700 leading-relaxed font-medium">{item}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>
    </section>
  )
}
