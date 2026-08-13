"use client"

import { BarChart3, TrendingUp, Users, ShoppingBag } from 'lucide-react'
import { FlowStage, GlassCard } from './landing-animation-primitives'

export function DashboardFlowAnimation() {
  return (
    <FlowStage>
      <GlassCard className="hero-float absolute left-0 top-4 z-20 max-w-[190px] p-3 sm:p-4">
        <div className="flex items-center gap-2">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
            <TrendingUp className="h-4 w-4 text-emerald-700" />
          </div>
          <div>
            <p className="text-[10px] text-zinc-500">Receita do mês</p>
            <p className="text-sm font-bold tabular-nums text-zinc-900">R$ 42.8k</p>
          </div>
        </div>
      </GlassCard>

      <GlassCard className="hero-float-delayed absolute right-0 top-10 z-20 max-w-[185px] p-3 sm:p-4 [animation-delay:0.4s]">
        <div className="flex items-center gap-2">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100">
            <Users className="h-4 w-4 text-orange-700" />
          </div>
          <div>
            <p className="text-[10px] text-zinc-500">Clientes ativos</p>
            <p className="text-sm font-bold text-zinc-900">1.284</p>
          </div>
        </div>
      </GlassCard>

      <div className="flow-tilt-float relative z-10 mx-auto mt-12 w-[92%]">
        <GlassCard className="overflow-hidden border-zinc-200 bg-white">
          <div className="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
            <div className="flex items-center gap-2">
              <BarChart3 className="h-4 w-4 text-orange-600" />
              <span className="text-sm font-semibold text-zinc-900">Painel de Controle</span>
            </div>
            <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-700">
              Ao vivo
            </span>
          </div>

          <div className="grid grid-cols-2 gap-2 p-3 sm:p-4">
            {[
              { label: 'Pedidos hoje', value: '127', icon: ShoppingBag },
              { label: 'Ticket médio', value: 'R$ 68', icon: TrendingUp },
              { label: 'Conversão', value: '34%', icon: BarChart3 },
              { label: 'Novos clientes', value: '+48', icon: Users },
            ].map((item) => (
              <div
                key={item.label}
                className="rounded-xl border border-zinc-100 bg-stone-50 px-3 py-2.5"
              >
                <p className="text-[10px] text-zinc-500">{item.label}</p>
                <p className="text-sm font-bold text-zinc-900">{item.value}</p>
              </div>
            ))}
          </div>

          <div className="border-t border-zinc-100 px-4 py-3">
            <div className="flex h-14 items-end gap-1">
              {[35, 55, 40, 70, 50, 85, 60, 75].map((h, i) => (
                <div
                  key={i}
                  className="flow-bar-grow flex-1 rounded-t bg-gradient-to-t from-orange-500 to-orange-300"
                  style={{ height: `${h}%`, animationDelay: `${i * 0.1}s` }}
                />
              ))}
            </div>
            <p className="mt-2 text-[10px] text-zinc-500">Volume de pedidos — últimos 7 dias</p>
          </div>
        </GlassCard>
      </div>

      <GlassCard className="hero-float-slow absolute bottom-4 left-6 z-20 max-w-[210px] p-3 sm:p-4">
        <p className="text-[10px] font-medium uppercase tracking-wide text-emerald-700/80">
          Insight automático
        </p>
        <p className="text-xs font-semibold text-zinc-900">Pico de vendas às 19h</p>
      </GlassCard>
    </FlowStage>
  )
}
