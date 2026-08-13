"use client"

import { CheckCircle2, Keyboard, ShoppingBag, Zap } from 'lucide-react'
import { FlowStage, GlassCard } from './landing-animation-primitives'

export function PdvFlowAnimation() {
  return (
    <FlowStage minHeight="min-h-[360px] sm:min-h-[400px]">
      <GlassCard className="hero-float absolute left-0 top-8 z-20 max-w-[195px] p-3">
        <div className="flex items-center gap-2">
          <Keyboard className="h-4 w-4 text-violet-600" />
          <p className="text-xs font-semibold text-zinc-900">Atalho: tecla 3</p>
        </div>
        <p className="mt-1 text-[10px] text-zinc-500">Categoria Bebidas</p>
      </GlassCard>

      <GlassCard className="hero-float-delayed absolute right-2 top-4 z-20 max-w-[185px] p-3 [animation-delay:0.6s]">
        <div className="flex items-center gap-2">
          <Zap className="h-4 w-4 text-amber-600" />
          <p className="text-xs font-semibold text-zinc-900">Touch-first</p>
        </div>
        <p className="mt-1 text-[10px] text-zinc-500">70% menos cliques</p>
      </GlassCard>

      <GlassCard className="hero-float-slow relative z-10 mx-auto mt-12 w-[92%] overflow-hidden border-zinc-200 bg-white">
        <div className="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
          <div className="flex items-center gap-2">
            <ShoppingBag className="h-4 w-4 text-orange-600" />
            <span className="text-sm font-semibold text-zinc-900">PDV — Pedido #2847</span>
          </div>
          <span className="text-[10px] text-amber-700">Em preparo</span>
        </div>
        <div className="space-y-2 p-3">
          {[
            { name: 'X-Burger Especial', qty: '2×', price: 'R$ 45,80' },
            { name: 'Batata crocante G', qty: '1×', price: 'R$ 18,00' },
            { name: 'Refrigerante 2L', qty: '1×', price: 'R$ 12,00' },
          ].map((item) => (
            <div
              key={item.name}
              className="flex items-center justify-between rounded-lg border border-zinc-100 bg-stone-50 px-3 py-2"
            >
              <div>
                <p className="text-xs font-medium text-zinc-900">{item.name}</p>
                <p className="text-[10px] text-zinc-500">{item.qty}</p>
              </div>
              <p className="text-xs font-semibold tabular-nums text-zinc-900">{item.price}</p>
            </div>
          ))}
        </div>
        <div className="flex justify-between border-t border-zinc-100 bg-stone-50/80 px-4 py-2.5">
          <span className="text-xs text-zinc-500">Total</span>
          <span className="text-sm font-bold text-emerald-700">R$ 75,80</span>
        </div>
      </GlassCard>

      <GlassCard className="hero-float absolute bottom-4 left-8 z-20 max-w-[210px] p-3">
        <div className="flex items-center gap-2">
          <CheckCircle2 className="h-4 w-4 text-emerald-600" />
          <p className="text-xs font-semibold text-zinc-900">Status: Pronto</p>
        </div>
      </GlassCard>
    </FlowStage>
  )
}
