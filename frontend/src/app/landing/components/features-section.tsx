"use client"

import {
  ArrowRight,
  BarChart3,
  Calendar,
  CreditCard,
  DollarSign,
  Gift,
  Keyboard,
  Layers,
  MenuSquare,
  MessageSquare,
  Package,
  PlusCircle,
  ShoppingCart,
  Smartphone,
  Star,
  TrendingUp,
  Truck,
  Users,
  Utensils,
  Zap,
} from 'lucide-react'
import Link from 'next/link'
import { Button } from '@/components/ui/button'
import { DashboardFlowAnimation } from '@/app/landing/components/dashboard-flow-animation'
import { FinanceFlowAnimation } from '@/app/landing/components/finance-flow-animation'
import { PdvFlowAnimation } from '@/app/landing/components/pdv-flow-animation'
import { TRIAL_CTA_LABEL } from '@/lib/landing-copy'
import { useLandingCTAClick } from '@/hooks/use-landing-cta-click'

const mainFeatures = [
  { icon: Zap, title: 'PDV Touch-First', description: 'Layout otimizado para touch com atalhos que reduzem cliques em até 70%.', badge: 'NOVO' },
  { icon: Star, title: 'Avaliações Moderadas', description: 'Moderação inteligente para construir confiança com provas sociais.', badge: 'NOVO' },
  { icon: Utensils, title: 'Controle de Mesas', description: 'Status em tempo real, visualize ocupação e otimize o salão.' },
  { icon: MenuSquare, title: 'Cardápio com Variações', description: 'Tamanhos, sabores e opcionais com preços atualizando em tempo real.' },
]

const secondaryFeatures = [
  { icon: ShoppingCart, title: 'Pedidos Omnichannel', description: 'Centralize balcão, delivery e cardápio digital em um só painel.' },
  { icon: Package, title: 'Controle de Estoque', description: 'Alertas de baixo estoque e controle de custos. Reduza desperdícios em 30%.' },
  { icon: BarChart3, title: 'Relatórios em Tempo Real', description: 'Desempenho por produto, canal e operador com poucos cliques.' },
  {
    icon: Smartphone,
    title: 'App Alba Tec Restaurante',
    description:
      'Push com som, aceite e avanço de status no celular — o mesmo fluxo do painel, no bolso.',
    badge: 'NOVO',
  },
]

const advancedFeatures = [
  { icon: DollarSign, title: 'Gestão Financeira', description: 'Contas a pagar/receber, despesas e fluxo de caixa integrado.' },
  { icon: Gift, title: 'Programa de Fidelidade', description: 'Pontos personalizados com recompensas e cupons para clientes.' },
  { icon: Truck, title: 'Integração iFood', description: 'Pedidos do iFood centralizados no mesmo painel da operação.' },
  { icon: MessageSquare, title: 'Notificações WhatsApp', description: 'Confirmações e atualizações de pedidos via WhatsApp.' },
  { icon: Calendar, title: 'Gestão de Eventos', description: 'Happy hours e eventos especiais com calendário integrado.' },
  { icon: CreditCard, title: 'Múltiplos Pagamentos', description: 'PIX, cartão, dinheiro e formas de pagamento configuráveis.' },
  { icon: Users, title: 'Gestão de Equipe', description: 'Permissões granulares, perfis personalizados e auditoria.' },
]

const pdvHighlights = [
  {
    icon: Star,
    title: 'Avaliações que Convertem',
    bullets: [
      'Moderação com aprovação manual para controle total',
      'Destaque de avaliações positivas no cardápio',
      'Métricas de satisfação em tempo real',
    ],
  },
  {
    icon: Utensils,
    title: 'Kanban de Pedidos',
    bullets: [
      'Status por colunas: Em Preparo, Pronto, Entregue',
      'Arrastar e soltar para atualizar status',
      'Filtros por canal, status e período',
    ],
  },
  {
    icon: Keyboard,
    title: 'Atalhos e Touch',
    bullets: [
      'Teclas 1–9 selecionam categorias instantaneamente',
      'Escape fecha modais em qualquer etapa',
      'Botões grandes para fluxo 100% touch',
    ],
  },
]

export function FeaturesSection() {
  const trackCTA = useLandingCTAClick('cta_features_click')

  return (
    <div id="features">

      {/* Painel de Controle */}
      <section className="py-24 sm:py-32 bg-stone-50 border-t border-zinc-200">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid items-center gap-16 lg:grid-cols-2 lg:gap-12 xl:gap-20">
            <div className="flow-reveal-up">
              <DashboardFlowAnimation />
            </div>

            <div className="flow-reveal-up [animation-delay:150ms]">
              <p className="text-[11px] uppercase tracking-[0.22em] text-orange-600 font-medium mb-4">
                Painel de Controle
              </p>
              <h2 className="text-3xl sm:text-4xl font-bold tracking-[-0.02em] text-zinc-900 text-balance mb-4">
                Métricas e gráficos que mostram o pulso do negócio
              </h2>
              <p className="text-lg text-zinc-500 leading-relaxed mb-8">
                Receita, pedidos, clientes ativos e taxa de conversão em tempo real.
                Gráficos de volume e evolução para decisões rápidas com dados.
              </p>

              <div className="grid gap-3 sm:grid-cols-2 mb-8">
                {mainFeatures.map((f) => (
                  <div
                    key={f.title}
                    className="rounded-xl border border-zinc-200 bg-white p-4"
                  >
                    <div className="mb-2 flex items-center gap-2">
                      <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100">
                        <f.icon className="h-4 w-4 text-zinc-600" />
                      </div>
                      {f.badge && (
                        <span className="text-[10px] uppercase tracking-wider font-semibold text-orange-600 bg-orange-50 px-1.5 py-0.5 rounded">
                          {f.badge}
                        </span>
                      )}
                    </div>
                    <h3 className="text-sm font-semibold text-zinc-900 mb-1">{f.title}</h3>
                    <p className="text-xs text-zinc-500 leading-relaxed">{f.description}</p>
                  </div>
                ))}
              </div>

              <div className="flex flex-col gap-3 sm:flex-row">
                <Button
                  size="lg"
                  className="rounded-md bg-zinc-900 text-white hover:bg-zinc-700 h-11 px-7 text-sm font-semibold transition-colors"
                  asChild
                >
                  <a href="#pricing">Ver Planos</a>
                </Button>
                <Button
                  size="lg"
                  variant="outline"
                  className="rounded-md border-zinc-300 h-11 px-6 text-sm text-zinc-700 hover:bg-zinc-100 transition-colors"
                  asChild
                >
                  <a href="#contact">Falar com Vendas</a>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Recursos Avançados */}
      <section className="py-20 sm:py-24 bg-white border-t border-zinc-200">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="max-w-2xl mb-12">
            <p className="text-[11px] uppercase tracking-[0.22em] text-orange-600 font-medium mb-4">
              Recursos Avançados
            </p>
            <h2 className="text-3xl sm:text-4xl font-bold tracking-[-0.02em] text-zinc-900 mb-3">
              Funcionalidades que fazem a diferença
            </h2>
            <p className="text-zinc-500 text-lg">
              Além do essencial, recursos avançados para impulsionar seus resultados.
            </p>
          </div>

          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {advancedFeatures.map((f) => (
              <div
                key={f.title}
                className="rounded-xl border border-zinc-200 bg-zinc-50 p-5"
              >
                <div className="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100">
                  <f.icon className="h-4 w-4 text-zinc-600" />
                </div>
                <h3 className="text-sm font-semibold text-zinc-900 mb-1">{f.title}</h3>
                <p className="text-xs text-zinc-500 leading-relaxed">{f.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Gestão Financeira */}
      <section className="py-24 sm:py-32 bg-stone-50 border-t border-zinc-200">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid items-center gap-16 lg:grid-cols-2 lg:gap-12 xl:gap-20">
            <div className="flow-reveal-up order-2 lg:order-1">
              <p className="text-[11px] uppercase tracking-[0.22em] text-orange-600 font-medium mb-4">
                Gestão Financeira
              </p>
              <h2 className="text-3xl sm:text-4xl font-bold tracking-[-0.02em] text-zinc-900 text-balance mb-4">
                Controle financeiro completo em um só painel
              </h2>
              <p className="text-lg text-zinc-500 leading-relaxed mb-8">
                Contas a receber e a pagar, despesas do mês e saldo projetado.
                Fornecedores, categorias e dados bancários integrados à operação.
              </p>

              <div className="grid gap-3 sm:grid-cols-2 mb-8">
                {secondaryFeatures.map((f) => (
                  <div
                    key={f.title}
                    className="rounded-xl border border-zinc-200 bg-white p-4"
                  >
                    <div className="mb-2 flex items-center gap-2">
                      <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100">
                        <f.icon className="h-4 w-4 text-zinc-600" />
                      </div>
                      {'badge' in f && f.badge ? (
                        <span className="rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-orange-700">
                          {f.badge}
                        </span>
                      ) : null}
                    </div>
                    <h3 className="text-sm font-semibold text-zinc-900 mb-1">{f.title}</h3>
                    <p className="text-xs text-zinc-500 leading-relaxed">{f.description}</p>
                  </div>
                ))}
              </div>

              <div className="flex flex-col gap-3 sm:flex-row">
                <Button
                  size="lg"
                  className="rounded-md bg-zinc-900 text-white hover:bg-zinc-700 h-11 px-7 text-sm font-semibold transition-colors"
                  asChild
                >
                  <Link href="/auth/register" onClick={() => trackCTA('/auth/register')}>
                    {TRIAL_CTA_LABEL}
                    <ArrowRight className="ml-2 h-4 w-4" />
                  </Link>
                </Button>
                <Button
                  size="lg"
                  variant="outline"
                  className="rounded-md border-zinc-300 h-11 px-6 text-sm text-zinc-700 hover:bg-zinc-100 transition-colors"
                  asChild
                >
                  <a href="#faq">Perguntas Frequentes</a>
                </Button>
              </div>
            </div>

            <div className="flow-reveal-up order-1 lg:order-2 [animation-delay:150ms]">
              <FinanceFlowAnimation />
            </div>
          </div>
        </div>
      </section>

      {/* PDV */}
      <section className="py-24 sm:py-32 bg-white border-t border-zinc-200">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="max-w-2xl mb-12">
            <p className="text-[11px] uppercase tracking-[0.22em] text-orange-600 font-medium mb-4">
              PDV
            </p>
            <h2 className="text-3xl sm:text-4xl font-bold tracking-[-0.02em] text-zinc-900 mb-3">
              PDV mais rápido para salão e delivery
            </h2>
            <p className="text-zinc-500 text-lg leading-relaxed">
              Interface touch-first com categorias visuais e fluxo de pedido em poucos toques.
            </p>
          </div>

          <div className="flow-reveal-up mb-12">
            <PdvFlowAnimation />
          </div>

          <div className="grid gap-5 md:grid-cols-3">
            {pdvHighlights.map((h) => (
              <div
                key={h.title}
                className="rounded-xl border border-zinc-200 bg-zinc-50 p-6"
              >
                <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-100">
                  <h.icon className="h-5 w-5 text-zinc-600" />
                </div>
                <h3 className="text-base font-semibold text-zinc-900 mb-4">{h.title}</h3>
                <ul className="space-y-2.5">
                  {h.bullets.map((b, i) => (
                    <li key={i} className="flex items-start gap-2.5">
                      <span className="mt-1.5 h-1.5 w-1.5 rounded-full bg-orange-500 shrink-0" />
                      <span className="text-sm text-zinc-500 leading-relaxed">{b}</span>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Variações e Opcionais */}
      <section className="py-20 sm:py-24 bg-stone-50 border-t border-zinc-200">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="max-w-2xl mb-12">
            <p className="text-[11px] uppercase tracking-[0.22em] text-orange-600 font-medium mb-4">
              Cardápio Inteligente
            </p>
            <h2 className="text-3xl sm:text-4xl font-bold tracking-[-0.02em] text-zinc-900 mb-3">
              Variações e opcionais em tempo real
            </h2>
            <p className="text-zinc-500 text-lg leading-relaxed">
              Tamanhos, sabores, complementos — tudo com cálculo automático de preços.
            </p>
          </div>

          <div className="grid gap-5 md:grid-cols-3 mb-8">
            <div className="rounded-xl border border-zinc-200 bg-white p-6">
              <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-100">
                <Layers className="h-5 w-5 text-zinc-600" />
              </div>
              <h3 className="text-base font-semibold text-zinc-900 mb-2">Variações de Produto</h3>
              <p className="text-sm text-zinc-500 mb-4 leading-relaxed">
                Tamanhos (P/M/G), tipos de massa, sabores com preços positivos ou negativos.
              </p>
              <ul className="space-y-2">
                {['Escolha única (radio button)', 'Preços positivos ou negativos', 'Ex: Pizza G (+R$ 10)'].map((t) => (
                  <li key={t} className="flex items-start gap-2.5">
                    <span className="mt-1.5 h-1.5 w-1.5 rounded-full bg-zinc-300 shrink-0" />
                    <span className="text-sm text-zinc-500">{t}</span>
                  </li>
                ))}
              </ul>
            </div>

            <div className="rounded-xl border border-zinc-200 bg-white p-6">
              <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-100">
                <PlusCircle className="h-5 w-5 text-zinc-600" />
              </div>
              <h3 className="text-base font-semibold text-zinc-900 mb-2">Opcionais Personalizáveis</h3>
              <p className="text-sm text-zinc-500 mb-4 leading-relaxed">
                Bordas recheadas, molhos extras e complementos com escolha múltipla e quantidade.
              </p>
              <ul className="space-y-2">
                {['Escolha múltipla com quantidade', 'Cliente pode repetir o mesmo item', 'Ex: 2× Bacon, 1× Borda Catupiry'].map((t) => (
                  <li key={t} className="flex items-start gap-2.5">
                    <span className="mt-1.5 h-1.5 w-1.5 rounded-full bg-zinc-300 shrink-0" />
                    <span className="text-sm text-zinc-500">{t}</span>
                  </li>
                ))}
              </ul>
            </div>

            <div className="rounded-xl border border-zinc-200 bg-white p-6">
              <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-100">
                <TrendingUp className="h-5 w-5 text-zinc-600" />
              </div>
              <h3 className="text-base font-semibold text-zinc-900 mb-2">Cálculo em Tempo Real</h3>
              <p className="text-sm text-zinc-500 mb-4 leading-relaxed">
                Preço total atualiza instantaneamente conforme o cliente seleciona variações e opcionais.
              </p>
              <ul className="space-y-2">
                {['Atualização instantânea (0ms)', 'Fórmula: Base + Variação + Opcionais', 'Sem reload, sem espera'].map((t) => (
                  <li key={t} className="flex items-start gap-2.5">
                    <span className="mt-1.5 h-1.5 w-1.5 rounded-full bg-zinc-300 shrink-0" />
                    <span className="text-sm text-zinc-500">{t}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>

          <div className="rounded-xl border border-zinc-200 bg-white p-6 md:p-8">
            <h3 className="text-sm font-semibold text-zinc-500 uppercase tracking-wider mb-6">
              Exemplo prático — Pizza Margherita
            </h3>
            <div className="grid gap-4 sm:grid-cols-3 mb-6">
              <div className="rounded-lg border border-zinc-200 p-4">
                <p className="text-[11px] uppercase tracking-wider text-zinc-400 mb-1">Preço Base</p>
                <p className="text-2xl font-bold text-zinc-900">R$ 35,00</p>
              </div>
              <div className="rounded-lg border border-zinc-200 p-4">
                <p className="text-[11px] uppercase tracking-wider text-zinc-400 mb-1">Variação: Grande</p>
                <p className="text-2xl font-bold text-emerald-600">+R$ 10,00</p>
              </div>
              <div className="rounded-lg border border-zinc-200 p-4">
                <p className="text-[11px] uppercase tracking-wider text-zinc-400 mb-1">Opcionais</p>
                <p className="text-xs text-zinc-400 mb-1">2× Bacon: R$ 10 · 1× Borda: R$ 12</p>
                <p className="text-2xl font-bold text-zinc-900">+R$ 22,00</p>
              </div>
            </div>
            <div className="border-t border-zinc-200 pt-5 flex flex-wrap items-baseline gap-3">
              <p className="text-sm text-zinc-400">Total do pedido</p>
              <p className="text-4xl font-bold text-zinc-900">R$ 67,00</p>
              <p className="text-sm text-zinc-400">(R$ 35 + R$ 10 + R$ 22)</p>
            </div>
          </div>
        </div>
      </section>

    </div>
  )
}
