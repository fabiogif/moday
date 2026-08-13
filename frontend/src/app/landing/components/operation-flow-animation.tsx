"use client"

import { CheckCircle2, MousePointer2, ShoppingBag, UserPlus, UtensilsCrossed } from 'lucide-react'
import { FlowStage, GlassCard } from './landing-animation-primitives'

export function OperationFlowAnimation() {
  return (
    <FlowStage aria-hidden="true">
      <GlassCard className="hero-float-delayed absolute left-0 top-6 z-20 max-w-[210px] p-3 sm:p-4 [animation-delay:0.6s]">
        <div className="flex items-center gap-2.5">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-100">
            <UserPlus className="h-4 w-4 text-orange-700" />
          </div>
          <div>
            <p className="text-[10px] font-medium uppercase tracking-wide text-orange-700/80">
              Cliente capturado
            </p>
            <p className="text-xs font-semibold text-zinc-900">Adicionado à base</p>
          </div>
        </div>
      </GlassCard>

      <GlassCard className="hero-float absolute right-0 top-2 z-20 max-w-[200px] p-3 sm:p-4">
        <p className="text-[10px] font-medium text-zinc-500">Delivery integrado</p>
        <p className="mt-1 text-xs font-semibold text-zinc-900">iFood sincronizado</p>
        <div className="mt-2 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-800">
          Canais sincronizados
        </div>
      </GlassCard>

      <div className="flow-tilt-float relative z-10 mx-auto mt-14 w-[92%] max-w-md">
        <GlassCard className="overflow-hidden border-zinc-200 bg-white p-0">
          <div className="grid grid-cols-2">
            <div className="flex flex-col justify-between bg-stone-50 p-4 sm:p-5">
              <div>
                <p className="text-sm font-semibold leading-snug text-zinc-900">
                  Peça pelo cardápio digital do seu restaurante
                </p>
                <div className="mt-4 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 text-xs text-zinc-400">
                  seu@email.com
                </div>
              </div>
              <div className="relative mt-4">
                <div className="inline-flex w-full items-center justify-center rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-bold text-white">
                  Abrir cardápio
                </div>
                <MousePointer2 className="flow-cursor-drift absolute -bottom-2 right-3 h-5 w-5 text-orange-600 drop-shadow-sm" />
              </div>
            </div>

            <div className="relative min-h-[180px] bg-gradient-to-br from-orange-100 via-orange-50 to-amber-100 p-4">
              <div className="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.8),transparent_55%)]" />
              <div className="relative flex h-full flex-col items-center justify-center text-center">
                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-md">
                  <UtensilsCrossed className="h-7 w-7 text-orange-600" />
                </div>
                <p className="mt-3 text-sm font-bold text-zinc-900">Cardápio ao vivo</p>
                <p className="text-[10px] text-zinc-500">QR Code · Variações · Fotos</p>
              </div>
            </div>
          </div>
        </GlassCard>
      </div>

      <GlassCard className="hero-float-slow absolute bottom-4 left-8 z-20 max-w-[230px] p-3 sm:p-4">
        <div className="flex items-start gap-2">
          <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100">
            <CheckCircle2 className="h-4 w-4 text-emerald-700" />
          </div>
          <div>
            <p className="text-[10px] font-medium uppercase tracking-wide text-emerald-700/80">
              Pedido confirmado
            </p>
            <p className="text-sm font-bold text-zinc-900">Enviado para a cozinha</p>
            <p className="mt-0.5 flex items-center gap-1 text-[10px] text-zinc-500">
              <ShoppingBag className="h-3 w-3" />
              Mesa 12 · R$ 67,90
            </p>
          </div>
        </div>
      </GlassCard>
    </FlowStage>
  )
}
