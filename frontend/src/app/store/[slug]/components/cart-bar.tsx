"use client"

import Image from "next/image"
import { BadgePercent, Store } from "lucide-react"
import { Button } from "@/components/ui/button"
import { formatPrice } from "../menu-utils"

interface CartBarProps {
  logoUrl: string | null
  total: number
  itemCount: number
  /** Valor para entrega grátis — só quando a loja entrega e configurou o valor */
  freeDeliveryAbove?: number
  savings: number
  onOpenCart: () => void
}

/** Mensagem informativa única, por prioridade: entrega grátis → economia. Nunca bloqueia nada. */
export function getCartBarMessage(total: number, savings: number, freeDeliveryAbove?: number): string | null {
  if (freeDeliveryAbove && total < freeDeliveryAbove) {
    return `Faltam R$ ${formatPrice(freeDeliveryAbove - total)} para entrega grátis`
  }
  if (savings > 0) {
    return `Você economiza R$ ${formatPrice(savings)}`
  }
  return null
}

export function CartBar({ logoUrl, total, itemCount, freeDeliveryAbove, savings, onOpenCart }: CartBarProps) {
  const message = getCartBarMessage(total, savings, freeDeliveryAbove)

  return (
    <div className="fixed inset-x-0 bottom-0 z-40 border-t bg-background px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] lg:hidden">
      {message && (
        <p
          role="status"
          className="mb-3 flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white"
        >
          <BadgePercent aria-hidden className="h-4 w-4 shrink-0" />
          {message}
        </p>
      )}
      <div className="flex items-center gap-3">
        <div className="relative h-10 w-10 shrink-0 overflow-hidden rounded-full bg-muted ring-1 ring-border/60">
          {logoUrl ? (
            <Image src={logoUrl} alt="" fill className="object-cover" sizes="40px" />
          ) : (
            <Store aria-hidden className="m-auto mt-2.5 h-5 w-5 text-muted-foreground" />
          )}
        </div>
        <div className="min-w-0 flex-1 leading-tight">
          <p className="text-xs text-muted-foreground">Total sem a entrega</p>
          <p className="truncate">
            <span className="text-base font-bold">R$ {formatPrice(total)}</span>
            <span className="text-sm text-muted-foreground"> / {itemCount} {itemCount === 1 ? "item" : "itens"}</span>
          </p>
        </div>
        <Button type="button" onClick={onOpenCart} className="h-12 shrink-0 px-6 text-base font-semibold">
          Ver carrinho
        </Button>
      </div>
    </div>
  )
}
