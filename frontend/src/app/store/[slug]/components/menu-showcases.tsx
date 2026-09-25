"use client"

import type { MouseEvent } from "react"
import Image from "next/image"
import { Plus } from "lucide-react"
import { cn } from "@/lib/utils"
import { resolveImageUrl } from "@/lib/resolve-image-url"
import { formatPrice, getDisplayPrice, type Product } from "../menu-utils"
import { ProductImagePlaceholder } from "./product-image-placeholder"

interface ShowcaseProps {
  products: Product[]
  onOpen: (product: Product) => void
  onAdd: (product: Product, event: MouseEvent) => void
}

const carouselClass =
  "-mx-4 flex gap-3 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"

function ShowcaseImage({ product, children }: { product: Product; children?: React.ReactNode }) {
  const imageUrl = product.image ? resolveImageUrl(product.image) : null
  return (
    <div className="relative aspect-square w-full overflow-hidden rounded-2xl bg-muted">
      {imageUrl ? (
        <Image src={imageUrl} alt="" fill className="object-cover" sizes="160px" />
      ) : (
        <ProductImagePlaceholder />
      )}
      {children}
    </div>
  )
}

/** "Ofertas": produtos com desconto, maior desconto primeiro (a ordenação vem da página). */
export function MenuOffers({ products, onOpen, onAdd }: ShowcaseProps) {
  if (products.length === 0) return null

  return (
    <section aria-labelledby="vitrine-ofertas" className="space-y-3">
      <h2 id="vitrine-ofertas" className="text-xl font-bold">Ofertas</h2>
      <div className={carouselClass}>
        {products.map((product, index) => {
          const { price, originalPrice, discountPercent, isFrom } = getDisplayPrice(product)
          return (
            <article
              key={product.uuid}
              className={cn(
                "w-[9.5rem] shrink-0 rounded-2xl p-1.5 sm:w-40",
                index === 0 && "bg-amber-50 dark:bg-amber-950/20",
              )}
            >
              <button
                type="button"
                onClick={() => onOpen(product)}
                aria-label={`Ver detalhes de ${product.name}`}
                className="block w-full text-left"
              >
                <ShowcaseImage product={product}>
                  <span className="absolute bottom-1.5 right-1.5 rounded-full bg-emerald-600 px-2 py-0.5 text-sm font-semibold text-white">
                    -{discountPercent}%
                  </span>
                </ShowcaseImage>
              </button>
              <div className="mt-2 flex items-start justify-between gap-1">
                <p className="min-w-0 leading-tight">
                  <span className="sr-only">
                    {isFrom ? "A partir de " : ""}de R$ {formatPrice(originalPrice ?? price)} por R$ {formatPrice(price)}
                  </span>
                  <span aria-hidden>
                    {isFrom && <span className="block text-xs text-emerald-700 dark:text-emerald-400">a partir de</span>}
                    <span className="block text-base font-bold text-emerald-700 dark:text-emerald-400">R$ {formatPrice(price)}</span>
                    {originalPrice && (
                      <span className="block text-xs text-muted-foreground line-through">R$ {formatPrice(originalPrice)}</span>
                    )}
                  </span>
                </p>
                {product.qtd_stock !== 0 && (
                  <button
                    type="button"
                    onClick={(e) => onAdd(product, e)}
                    aria-label={`Adicionar ${product.name} ao carrinho`}
                    className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm transition hover:bg-primary/90 active:scale-95"
                  >
                    <Plus className="h-5 w-5" strokeWidth={2.5} />
                  </button>
                )}
              </div>
              <p className="mt-1 line-clamp-2 text-sm font-medium leading-snug">{product.name}</p>
            </article>
          )
        })}
      </div>
    </section>
  )
}

/** "Preferidos": ranking dos mais vendidos, com o número da posição sobre a foto. */
export function MenuTopSellers({ products, onOpen }: Omit<ShowcaseProps, "onAdd">) {
  if (products.length === 0) return null

  return (
    <section aria-labelledby="vitrine-preferidos" className="space-y-3">
      <h2 id="vitrine-preferidos" className="text-xl font-bold">Preferidos</h2>
      <div className={cn(carouselClass, "pt-2")}>
        {products.map((product, index) => {
          const rank = index + 1
          const { price, isFrom } = getDisplayPrice(product)
          return (
            <button
              key={product.uuid}
              type="button"
              onClick={() => onOpen(product)}
              aria-label={`${rank}º mais pedido: ${product.name}, ${isFrom ? "a partir de " : ""}R$ ${formatPrice(price)}`}
              className="relative w-36 shrink-0 text-left"
            >
              <ShowcaseImage product={product} />
              <span
                aria-hidden
                className="absolute -left-1 -top-3 text-5xl font-black leading-none text-primary [text-shadow:0_2px_0_var(--background),0_-2px_0_var(--background),2px_0_0_var(--background),-2px_0_0_var(--background)]"
              >
                {rank}
              </span>
              <span aria-hidden className="mt-2 block text-base font-semibold">
                {isFrom && <span className="mr-1 text-xs font-normal text-muted-foreground">a partir de</span>}
                R$ {formatPrice(price)}
              </span>
              <span aria-hidden className="mt-0.5 line-clamp-2 block text-sm leading-snug">{product.name}</span>
            </button>
          )
        })}
      </div>
    </section>
  )
}
