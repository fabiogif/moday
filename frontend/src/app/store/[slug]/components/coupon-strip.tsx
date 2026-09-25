"use client"

import { TicketPercent } from "lucide-react"
import { copyText } from "../menu-utils"

export interface CouponSlide {
  title: string
  highlight: string
  code: string
}

/** Cupons em destaque da loja. Aplicar cupom ainda não existe no checkout, então tocar copia o código. */
export function CouponStrip({ coupons }: { coupons: CouponSlide[] }) {
  if (coupons.length === 0) return null

  return (
    <div
      aria-label="Cupons da loja"
      role="region"
      className="-mx-4 flex gap-3 overflow-x-auto px-4 py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
    >
      {coupons.map((coupon) => (
        <button
          key={coupon.code}
          type="button"
          onClick={() => copyText(coupon.code, `Cupom ${coupon.code} copiado!`)}
          aria-label={`Copiar cupom ${coupon.code}: ${coupon.highlight}, ${coupon.title}`}
          className="relative flex min-w-[13rem] shrink-0 items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-left transition active:scale-[0.98] before:absolute before:-left-2 before:top-1/2 before:h-4 before:w-4 before:-translate-y-1/2 before:rounded-full before:border before:border-emerald-200 before:bg-background after:absolute after:-right-2 after:top-1/2 after:h-4 after:w-4 after:-translate-y-1/2 after:rounded-full after:border after:border-emerald-200 after:bg-background dark:border-emerald-900 dark:bg-emerald-950/30 dark:before:border-emerald-900 dark:after:border-emerald-900"
        >
          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600/10 text-emerald-700 dark:text-emerald-400">
            <TicketPercent className="h-5 w-5" />
          </span>
          <span aria-hidden className="min-w-0">
            <span className="block text-base font-bold leading-tight text-emerald-700 dark:text-emerald-400">
              {coupon.highlight}
            </span>
            <span className="block truncate text-xs text-foreground/80">{coupon.title}</span>
            <span className="block text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
              Cupom: {coupon.code}
            </span>
          </span>
        </button>
      ))}
    </div>
  )
}
