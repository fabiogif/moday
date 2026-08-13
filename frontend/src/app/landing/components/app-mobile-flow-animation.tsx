"use client"

import { Bell, CheckCircle2, Clock3, Smartphone } from 'lucide-react'
import { FlowStage, GlassCard } from './landing-animation-primitives'

export function AppMobileFlowAnimation() {
  return (
    <FlowStage minHeight="min-h-[360px] sm:min-h-[400px]">
      <GlassCard className="hero-float absolute left-0 top-6 z-20 max-w-[200px] p-3">
        <div className="flex items-center gap-2">
          <Bell className="h-4 w-4 text-violet-600" />
          <p className="text-xs font-semibold text-zinc-900">Novo pedido</p>
        </div>
        <p className="mt-1 text-[10px] text-zinc-500">Alerta com som — app fechado</p>
      </GlassCard>

      <GlassCard className="hero-float-delayed absolute right-1 top-10 z-20 max-w-[180px] p-3 [animation-delay:0.5s]">
        <div className="flex items-center gap-2">
          <Clock3 className="h-4 w-4 text-orange-600" />
          <p className="text-xs font-semibold text-zinc-900">Horários</p>
        </div>
        <p className="mt-1 text-[10px] text-zinc-500">Abrir / fechar pelo celular</p>
      </GlassCard>

      <GlassCard className="hero-float-slow relative z-10 mx-auto mt-10 w-[min(100%,260px)] overflow-hidden border-zinc-200 bg-white shadow-lg">
        <div className="bg-violet-600 px-4 py-3 text-white">
          <div className="flex items-center gap-2">
            <Smartphone className="h-4 w-4 opacity-90" />
            <span className="text-sm font-semibold">Alba Tec Restaurante</span>
          </div>
          <p className="mt-1 text-[10px] text-violet-100">Pedido #A7K2 · R$ 89,90</p>
        </div>

        <div className="space-y-2 p-3">
          <div className="rounded-lg border border-violet-100 bg-violet-50 px-3 py-2">
            <p className="text-[10px] font-medium text-violet-700">Pedido Recebido</p>
            <p className="text-xs font-semibold text-zinc-900">Maria Silva · Delivery</p>
          </div>
          <button
            type="button"
            className="w-full rounded-lg bg-violet-600 py-2.5 text-center text-xs font-bold text-white"
          >
            Aceitar pedido
          </button>
          <div className="flex gap-1.5 pt-1">
            {['Confirmado', 'Preparação', 'Pronto', 'Entrega'].map((step, i) => (
              <div
                key={step}
                className={`h-1.5 flex-1 rounded-full ${i === 0 ? 'bg-violet-500' : 'bg-zinc-200'}`}
              />
            ))}
          </div>
          <p className="text-center text-[10px] text-zinc-400">Mesmo fluxo do painel</p>
        </div>
      </GlassCard>

      <GlassCard className="hero-float absolute bottom-3 left-6 z-20 max-w-[210px] p-3">
        <div className="flex items-center gap-2">
          <CheckCircle2 className="h-4 w-4 text-emerald-600" />
          <p className="text-xs font-semibold text-zinc-900">Aceito → Confirmado</p>
        </div>
      </GlassCard>
    </FlowStage>
  )
}
