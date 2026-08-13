"use client"

import {
  ArrowDownLeft,
  ArrowUpRight,
  BarChart3,
  CheckCircle2,
  TrendingUp,
  Wallet,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { FlowStage, GlassCard } from './landing-animation-primitives'

export function FinanceFlowAnimation() {
  return (
    <FlowStage minHeight="min-h-[400px] sm:min-h-[480px]">
      <GlassCard className="hero-float absolute -right-1 top-8 z-20 max-w-[195px] p-3 sm:p-4">
        <div className="flex items-center gap-2">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100">
            <ArrowUpRight className="h-4 w-4 text-rose-700" />
          </div>
          <div>
            <p className="text-[10px] text-zinc-500">Conta paga</p>
            <p className="text-sm font-bold tabular-nums text-zinc-900">R$ 1.240</p>
          </div>
        </div>
      </GlassCard>

      <GlassCard className="hero-float-delayed absolute left-0 top-16 z-20 max-w-[200px] p-3 sm:p-4 [animation-delay:0.5s]">
        <div className="flex items-center gap-2">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
            <ArrowDownLeft className="h-4 w-4 text-emerald-700" />
          </div>
          <div>
            <p className="text-[10px] text-zinc-500">A receber</p>
            <p className="text-sm font-bold tabular-nums text-zinc-900">R$ 3.890</p>
          </div>
        </div>
      </GlassCard>

      <GlassCard className="hero-float-slow relative z-10 mx-auto mt-12 w-[90%] overflow-hidden border-zinc-200 bg-white">
        <div className="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
          <div className="flex items-center gap-2">
            <Wallet className="h-4 w-4 text-orange-600" />
            <span className="text-sm font-semibold text-zinc-900">Painel Financeiro</span>
          </div>
          <span className="text-[10px] text-emerald-700">Saldo positivo</span>
        </div>

        <div className="grid grid-cols-2 gap-2 p-3 sm:p-4">
          {[
            { label: 'Receita', value: 'R$ 18.2k', tone: 'emerald' },
            { label: 'Despesas', value: 'R$ 9.4k', tone: 'rose' },
            { label: 'A receber', value: 'R$ 3.9k', tone: 'violet' },
            { label: 'A pagar', value: 'R$ 2.1k', tone: 'amber' },
          ].map((item) => (
            <div
              key={item.label}
              className="rounded-xl border border-zinc-100 bg-stone-50 px-3 py-2.5"
            >
              <p className="text-[10px] text-zinc-500">{item.label}</p>
              <p
                className={cn(
                  'text-sm font-bold tabular-nums',
                  item.tone === 'emerald' && 'text-emerald-700',
                  item.tone === 'rose' && 'text-rose-700',
                  item.tone === 'violet' && 'text-violet-700',
                  item.tone === 'amber' && 'text-amber-700',
                )}
              >
                {item.value}
              </p>
            </div>
          ))}
        </div>

        <div className="border-t border-zinc-100 px-4 py-3">
          <div className="flex h-16 items-end gap-1">
            {[40, 65, 45, 80, 55, 90, 70].map((h, i) => (
              <div
                key={i}
                className="flow-bar-grow flex-1 rounded-t bg-gradient-to-t from-orange-500 to-orange-300"
                style={{ height: `${h}%`, animationDelay: `${i * 0.12}s` }}
              />
            ))}
          </div>
          <p className="mt-2 flex items-center gap-1 text-[10px] text-zinc-500">
            <BarChart3 className="h-3 w-3" />
            Fluxo de caixa — últimos 7 dias
          </p>
        </div>
      </GlassCard>

      <GlassCard className="hero-float-delayed absolute bottom-2 right-6 z-20 max-w-[220px] p-3 sm:p-4 [animation-delay:1s]">
        <div className="flex items-center gap-2">
          <TrendingUp className="h-4 w-4 text-emerald-600" />
          <div>
            <p className="text-xs font-semibold text-zinc-900">Saldo projetado</p>
            <p className="text-lg font-bold tabular-nums text-emerald-700">R$ 8.720</p>
          </div>
          <CheckCircle2 className="ml-auto h-4 w-4 text-emerald-600" />
        </div>
      </GlassCard>
    </FlowStage>
  )
}
