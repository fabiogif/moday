"use client"

import type { ReactNode } from "react"
import Image from "next/image"
import Link from "next/link"
import { ChevronLeft, ChevronRight, Package, Search, Share2, Star, Store } from "lucide-react"
import { formatPrice, copyText } from "../menu-utils"

interface StoreHeroProps {
  name: string
  logoUrl: string | null
  /** Sem campo de capa na loja: usa a foto do produto mais vendido sobre a cor primária */
  coverImageUrl: string | null
  rating: { average: number; total: number } | null
  /** Status de horário (StoreHoursBanner) */
  hoursSlot: ReactNode
  deliveryEnabled: boolean
  freeDeliveryAbove?: number
  pickup: { enabled: boolean; minutes?: number; discountPercent?: number }
  /** Consulta de pedidos do cliente (acompanhamento pelo telefone, funciona sem login) */
  ordersHref: string
  onBack?: () => void
  onSearchClick: () => void
  onRatingClick: () => void
  onInfoClick: () => void
}

const heroButtonClass =
  "flex h-10 w-10 items-center justify-center rounded-xl bg-background/95 text-foreground shadow-md transition hover:bg-background active:scale-95"

export async function shareStore(name: string) {
  const url = window.location.href
  if (typeof navigator.share === "function") {
    try {
      await navigator.share({ title: name, url })
    } catch (error) {
      // Cliente fechou o menu de compartilhar: não é erro
      if ((error as Error)?.name !== "AbortError") await copyText(url, "Link da loja copiado!")
    }
    return
  }
  await copyText(url, "Link da loja copiado!")
}

export function StoreHero({
  name,
  logoUrl,
  coverImageUrl,
  rating,
  hoursSlot,
  deliveryEnabled,
  freeDeliveryAbove,
  pickup,
  ordersHref,
  onBack,
  onSearchClick,
  onRatingClick,
  onInfoClick,
}: StoreHeroProps) {
  const deliveryFee = !deliveryEnabled
    ? { text: "Só retirada", highlight: false }
    : freeDeliveryAbove
      ? { text: `Grátis acima de R$ ${formatPrice(freeDeliveryAbove)}`, highlight: true }
      : { text: "Calculada no endereço", highlight: false }

  return (
    <section aria-label="Informações da loja">
      <div className="relative h-44 w-full overflow-hidden bg-gradient-to-br from-primary to-primary/60 sm:h-56 lg:h-72">
        {coverImageUrl && (
          <Image src={coverImageUrl} alt="" fill priority className="object-cover" sizes="100vw" />
        )}
        <div aria-hidden className="absolute inset-0 bg-gradient-to-b from-black/40 via-black/10 to-black/20" />
        <div className="relative mx-auto flex max-w-6xl items-start justify-between px-4 pt-4 lg:pt-6">
          {onBack ? (
            <button type="button" onClick={onBack} aria-label="Voltar" className={heroButtonClass}>
              <ChevronLeft className="h-5 w-5" />
            </button>
          ) : (
            <span />
          )}
          <div className="flex gap-2">
            <Link
              href={ordersHref}
              className="flex h-10 items-center gap-1.5 rounded-xl bg-background/95 px-3 text-sm font-semibold text-foreground shadow-md transition hover:bg-background active:scale-95"
            >
              <Package aria-hidden className="h-4 w-4" />
              Meus pedidos
            </Link>
            <button type="button" onClick={onSearchClick} aria-label="Buscar no cardápio" className={heroButtonClass}>
              <Search className="h-5 w-5" />
            </button>
            <button type="button" onClick={() => shareStore(name)} aria-label="Compartilhar loja" className={heroButtonClass}>
              <Share2 className="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>

      {/* No mobile o painel encosta nas bordas; em telas grandes vira um card sobre a capa */}
      <div className="relative -mt-8 lg:mx-auto lg:-mt-24 lg:max-w-6xl lg:px-4">
        <div className="rounded-t-3xl bg-background lg:rounded-3xl lg:border lg:border-border/60 lg:shadow-lg">
        <div className="mx-auto max-w-6xl px-4 pt-5 lg:p-6">
          <div className="flex items-center gap-4">
            <div className="relative h-[72px] w-[72px] shrink-0 overflow-hidden rounded-2xl bg-muted shadow-sm ring-1 ring-border/60">
              {logoUrl ? (
                <Image src={logoUrl} alt={name} fill className="object-cover" sizes="72px" />
              ) : (
                <div className="flex h-full w-full items-center justify-center">
                  <Store className="h-8 w-8 text-muted-foreground" />
                </div>
              )}
            </div>
            <div className="min-w-0 space-y-1">
              <button
                type="button"
                onClick={onInfoClick}
                aria-label={`Ver informações de ${name}`}
                className="flex max-w-full items-center gap-1 text-left"
              >
                <h1 className="truncate text-xl font-bold leading-tight sm:text-2xl">{name}</h1>
                <ChevronRight className="h-5 w-5 shrink-0" />
              </button>
              {rating && rating.total > 0 && (
                <button
                  type="button"
                  onClick={onRatingClick}
                  aria-label={`Nota ${rating.average.toLocaleString("pt-BR", { maximumFractionDigits: 1 })} de 5, ${rating.total} avaliações. Ver avaliações`}
                  className="flex items-center gap-1 text-sm text-muted-foreground"
                >
                  <Star aria-hidden className="h-4 w-4 fill-foreground text-foreground" />
                  <span aria-hidden className="font-semibold text-foreground">
                    {rating.average.toLocaleString("pt-BR", { minimumFractionDigits: 1, maximumFractionDigits: 1 })}
                  </span>
                  <span aria-hidden>({rating.total})</span>
                  <ChevronRight aria-hidden className="h-4 w-4" />
                </button>
              )}
            </div>
          </div>

          <dl className="mt-5 grid auto-cols-fr grid-flow-col divide-x divide-border/70 text-sm lg:max-w-2xl">
            <div className="min-w-0 space-y-1 pr-3">
              <dt className="text-xs text-muted-foreground">Horário</dt>
              <dd className="min-w-0">{hoursSlot}</dd>
            </div>
            <div className="min-w-0 space-y-1 px-3">
              <dt className="text-xs text-muted-foreground">Taxa de entrega</dt>
              <dd className={deliveryFee.highlight ? "font-semibold text-emerald-600 dark:text-emerald-400" : "font-semibold"}>
                {deliveryFee.text}
              </dd>
            </div>
            {pickup.enabled && (
              <div className="min-w-0 space-y-1 pl-3">
                <dt className="text-xs text-muted-foreground">Retirada</dt>
                <dd className="font-semibold">
                  {pickup.minutes ? `~${pickup.minutes} min` : "Disponível"}
                  {!!pickup.discountPercent && (
                    <span className="ml-1 text-emerald-600 dark:text-emerald-400">-{pickup.discountPercent}%</span>
                  )}
                </dd>
              </div>
            )}
          </dl>
        </div>
        </div>
      </div>
    </section>
  )
}
