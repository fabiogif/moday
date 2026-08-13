"use client"

import {
  BarChart3,
  CheckCircle2,
  Clock,
  ShoppingBag,
  TrendingUp,
  UtensilsCrossed,
  Wallet,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { FlowStage, GlassCard } from './landing-animation-primitives'

export function HeroProductAnimation({ className }: { className?: string }) {
  return (
    <FlowStage className={cn('max-w-lg lg:max-w-none', className)} aria-hidden="true">
        <GlassCard className="hero-float-delayed absolute -left-2 top-8 z-20 w-[46%] max-w-[200px] p-3 sm:left-0 sm:p-4 [animation-delay:0.8s]">
          <div className="flex items-center gap-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
              <Wallet className="h-4 w-4 text-emerald-700" />
            </div>
            <div className="min-w-0">
              <p className="text-[10px] font-medium text-zinc-500 sm:text-xs">Saldo do mês</p>
              <p className="text-sm font-bold tabular-nums text-zinc-900 sm:text-base">R$ 12.450</p>
            </div>
          </div>
          <div className="mt-2 flex items-center gap-1 text-[10px] text-emerald-700">
            <TrendingUp className="h-3 w-3" />
            <span>+18% vs. mês anterior</span>
          </div>
        </GlassCard>

        <GlassCard className="hero-float absolute -right-1 top-4 z-20 w-[44%] max-w-[190px] p-3 sm:right-2 sm:p-4 [animation-delay:0.3s]">
          <div className="flex items-center gap-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100">
              <UtensilsCrossed className="h-4 w-4 text-orange-700" />
            </div>
            <div>
              <p className="text-[10px] font-medium text-zinc-500 sm:text-xs">Cardápio digital</p>
              <p className="text-xs font-semibold text-zinc-900 sm:text-sm">48 itens ativos</p>
            </div>
          </div>
          <div className="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-800">
            <CheckCircle2 className="h-3 w-3 text-emerald-600" />
            QR Code publicado
          </div>
        </GlassCard>

        <GlassCard className="hero-float-slow relative z-10 mx-auto mt-16 w-[88%] overflow-hidden border-zinc-200/90 bg-white">
          <div className="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
            <div className="flex items-center gap-2">
              <ShoppingBag className="h-4 w-4 text-orange-600" />
              <span className="text-sm font-semibold text-zinc-900">Pedidos em tempo real</span>
            </div>
            <span className="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700">
              <span className="relative flex h-1.5 w-1.5">
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75" />
                <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500" />
              </span>
              Ao vivo
            </span>
          </div>

          <div className="space-y-2 p-3 sm:p-4">
            {[
              { id: '#2847', table: 'Mesa 08', total: 'R$ 89,90', status: 'Em preparo', tone: 'amber' },
              { id: '#2846', table: 'Delivery', total: 'R$ 54,00', status: 'Pronto', tone: 'emerald' },
              { id: '#2845', table: 'Balcão', total: 'R$ 32,50', status: 'Entregue', tone: 'violet' },
            ].map((order) => (
              <div
                key={order.id}
                className="flex items-center justify-between rounded-xl border border-zinc-100 bg-stone-50 px-3 py-2.5"
              >
                <div>
                  <p className="text-xs font-semibold text-zinc-900">{order.id}</p>
                  <p className="text-[10px] text-zinc-500">{order.table}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-medium tabular-nums text-zinc-900">{order.total}</p>
                  <p
                    className={cn(
                      'text-[10px] font-medium',
                      order.tone === 'amber' && 'text-amber-700',
                      order.tone === 'emerald' && 'text-emerald-700',
                      order.tone === 'violet' && 'text-violet-700',
                    )}
                  >
                    {order.status}
                  </p>
                </div>
              </div>
            ))}
          </div>

          <div className="border-t border-zinc-100 bg-stone-50/80 px-4 py-2.5">
            <div className="flex items-center justify-between text-[10px] text-zinc-500">
              <span className="flex items-center gap-1">
                <Clock className="h-3 w-3" />
                Atualizado agora
              </span>
              <span className="flex items-center gap-1 text-orange-700">
                <BarChart3 className="h-3 w-3" />
                23 pedidos hoje
              </span>
            </div>
          </div>
        </GlassCard>

        <GlassCard className="hero-float-delayed absolute -bottom-2 right-4 z-20 w-[52%] max-w-[220px] p-3 sm:right-8 sm:p-4 [animation-delay:1.2s]">
          <div className="flex items-start gap-2">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-orange-100 ring-2 ring-orange-200">
              <CheckCircle2 className="h-4 w-4 text-orange-700" />
            </div>
            <div>
              <p className="text-[10px] font-medium uppercase tracking-wide text-orange-700/80">
                Novo pedido
              </p>
              <p className="text-sm font-bold text-zinc-900">#2848 confirmado</p>
              <p className="mt-0.5 text-[10px] text-zinc-500">2 itens · Mesa 12</p>
            </div>
          </div>
        </GlassCard>
    </FlowStage>
  )
}
