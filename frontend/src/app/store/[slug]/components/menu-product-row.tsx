"use client"

import type { MouseEvent } from "react"
import Image from "next/image"
import { Plus } from "lucide-react"
import { cn } from "@/lib/utils"
import { resolveImageUrl } from "@/lib/resolve-image-url"
import { formatPrice, getDisplayPrice, type Product } from "../menu-utils"
import { ProductImagePlaceholder } from "./product-image-placeholder"

interface MenuProductRowProps {
  product: Product
  onOpen: (product: Product) => void
  onAdd: (product: Product, event: MouseEvent) => void
}

export function MenuPrice({ product, className }: { product: Product; className?: string }) {
  const { price, originalPrice, discountPercent, isFrom } = getDisplayPrice(product)
  const label = originalPrice
    ? `${isFrom ? "A partir de " : ""}de R$ ${formatPrice(originalPrice)} por R$ ${formatPrice(price)}`
    : `${isFrom ? "A partir de " : ""}R$ ${formatPrice(price)}`

  return (
    <p className={cn("flex flex-wrap items-center gap-x-1.5 gap-y-1", className)}>
      <span className="sr-only">{label}</span>
      <span aria-hidden className="contents">
        {isFrom && <span className="text-xs text-muted-foreground">a partir de</span>}
        <span className="text-[15px] text-foreground">R$ {formatPrice(price)}</span>
        {originalPrice && (
          <span className="text-xs text-muted-foreground line-through">R$ {formatPrice(originalPrice)}</span>
        )}
      </span>
      {discountPercent > 0 && (
        <span className="rounded-full bg-emerald-600 px-1.5 py-0.5 text-[11px] font-semibold leading-none text-white">
          -{discountPercent}%
        </span>
      )}
    </p>
  )
}

export function MenuProductRow({ product, onOpen, onAdd }: MenuProductRowProps) {
  const soldOut = product.qtd_stock === 0
  const imageUrl = product.image ? resolveImageUrl(product.image) : null

  return (
    <article className={cn("flex items-start gap-3 border-b border-border/60 py-4", soldOut && "opacity-60")}>
      <button
        type="button"
        onClick={() => onOpen(product)}
        aria-label={`Ver detalhes de ${product.name}`}
        className="min-w-0 flex-1 space-y-1 rounded-md text-left outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
        <h3 className="line-clamp-2 text-[15px] font-semibold leading-snug text-foreground">{product.name}</h3>
        {product.description && (
          <p className="line-clamp-2 text-sm leading-snug text-muted-foreground">{product.description}</p>
        )}
        <MenuPrice product={product} className="pt-1.5" />
        {soldOut && <span className="inline-block pt-1 text-xs font-semibold text-muted-foreground">Esgotado</span>}
      </button>

      <div className="relative h-[104px] w-[104px] shrink-0">
        <button
          type="button"
          tabIndex={-1}
          aria-hidden
          onClick={() => onOpen(product)}
          className="relative block h-full w-full overflow-hidden rounded-xl bg-muted"
        >
          {imageUrl ? (
            <Image src={imageUrl} alt="" fill className="object-cover" sizes="104px" />
          ) : (
            <ProductImagePlaceholder />
          )}
        </button>
        {!soldOut && (
          <button
            type="button"
            onClick={(e) => onAdd(product, e)}
            aria-label={`Adicionar ${product.name} ao carrinho`}
            className="group absolute -bottom-2.5 -right-2.5 flex h-11 w-11 items-center justify-center rounded-full outline-none"
          >
            <span className="flex h-9 w-9 items-center justify-center rounded-full bg-background text-primary shadow-md ring-1 ring-border/60 transition group-hover:bg-muted group-active:scale-95 group-focus-visible:ring-2 group-focus-visible:ring-ring">
              <Plus className="h-5 w-5" strokeWidth={2.5} />
            </span>
          </button>
        )}
      </div>
    </article>
  )
}
