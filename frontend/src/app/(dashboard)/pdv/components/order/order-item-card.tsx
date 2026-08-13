"use client"

import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Input } from "@/components/ui/input"
import { Plus, Minus, Trash2, Package } from "lucide-react"
import { cn } from "@/lib/utils"
import Image from "next/image"
import { resolveImageUrl } from "@/lib/resolve-image-url"

type Product = {
  uuid?: string
  identify?: string
  name: string
  price?: number | string | null
  promotional_price?: number | string | null
  observation_templates?: string[]
  observation_suggestions?: string[]
  [key: string]: any
}

type ProductVariation = {
  id?: string
  identify?: string
  name: string
  price?: number | string | null
  [key: string]: any
}

type ProductOptional = {
  id?: string
  identify?: string
  name: string
  price?: number | string | null
  quantity: number
  [key: string]: any
}

export interface CartItem {
  signature: string
  product: Product
  quantity: number
  observation: string
  selectedVariation?: ProductVariation | null
  selectedOptionals?: Array<ProductOptional>
  [key: string]: any
}

interface OrderItemCardProps {
  item: CartItem
  unitPrice: number
  totalPrice: number
  isAdding?: boolean
  isRemoving?: boolean
  onIncrease: () => void
  onDecrease: () => void
  onRemove: () => void
  onObservationChange: (observation: string) => void
  formatCurrency: (value: number) => string
}

export function OrderItemCard({
  item,
  unitPrice,
  totalPrice,
  isAdding = false,
  isRemoving = false,
  onIncrease,
  onDecrease,
  onRemove,
  onObservationChange,
  formatCurrency,
}: OrderItemCardProps) {
  const productImage =
    resolveImageUrl(item.product.image_url) ||
    resolveImageUrl(item.product.image) ||
    resolveImageUrl(item.product.url)

  const templates =
    item.product.observation_templates?.length
      ? item.product.observation_templates
      : item.product.observation_suggestions ?? []

  return (
    <div
      data-testid={`cart-item-${item.signature}`}
      className={cn(
        "rounded-md border bg-card p-2 transition-all duration-150",
        isAdding && "ring-2 ring-emerald-400 ring-offset-1",
        isRemoving && "scale-[0.98] opacity-40",
        !isAdding && !isRemoving && "hover:border-primary/40"
      )}
    >
      <div className="flex items-start gap-2">
        <div className="relative h-10 w-10 flex-shrink-0 overflow-hidden rounded border bg-muted sm:h-11 sm:w-11">
          {productImage ? (
            <Image
              src={productImage}
              alt={item.product.name}
              fill
              className="object-cover"
              sizes="44px"
              loading="lazy"
              unoptimized
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center">
              <Package className="h-4 w-4 text-muted-foreground/50" />
            </div>
          )}
        </div>

        <div className="min-w-0 flex-1">
          <div className="flex items-start justify-between gap-2">
            <div className="min-w-0 flex-1">
              <p className="text-xs font-semibold leading-tight text-foreground line-clamp-2 sm:line-clamp-1">
                {item.product.name}
              </p>
              <p className="mt-0.5 text-[11px] tabular-nums text-muted-foreground">
                {formatCurrency(unitPrice)} cada
              </p>
            </div>
            <p className="shrink-0 text-sm font-bold tabular-nums text-foreground">
              {formatCurrency(totalPrice)}
            </p>
          </div>

          {item.selectedVariation && (
            <Badge
              variant="secondary"
              className="mt-1 h-4 px-1.5 py-0 text-[10px] font-normal"
            >
              {item.selectedVariation.name}
            </Badge>
          )}

          {item.selectedOptionals && item.selectedOptionals.length > 0 && (
            <div className="mt-0.5 space-y-0.5">
              {item.selectedOptionals.map((optional, idx) => {
                const key = optional.id || optional.identify || optional.name || `opt-${idx}`
                return (
                  <div
                    key={`${item.signature}-${key}-${idx}`}
                    className="flex items-center justify-between gap-2 text-[10px] text-muted-foreground"
                  >
                    <span className="min-w-0 break-words">
                      + {optional.name} × {optional.quantity}
                    </span>
                    <span className="shrink-0 tabular-nums">
                      {formatCurrency(
                        (typeof optional.price === "number"
                          ? optional.price
                          : parseFloat(String(optional.price || 0))) * optional.quantity
                      )}
                    </span>
                  </div>
                )
              })}
            </div>
          )}

          {item.observation && (
            <p className="mt-0.5 text-[10px] italic text-muted-foreground break-words">
              {item.observation}
            </p>
          )}
        </div>
      </div>

      <div className="mt-1.5 flex items-center justify-between gap-2 border-t border-border/60 pt-1.5">
        <div className="flex items-center gap-1">
          <Button
            variant="outline"
            size="icon"
            className="h-7 w-7 rounded-md"
            aria-label={`Diminuir ${item.product.name}`}
            onClick={onDecrease}
          >
            <Minus className="h-3 w-3" />
          </Button>
          <span
            data-testid={`cart-item-qty-${item.signature}`}
            className="flex h-7 min-w-[28px] items-center justify-center rounded-md bg-primary px-1.5 text-xs font-bold tabular-nums text-primary-foreground"
          >
            {item.quantity}
          </span>
          <Button
            variant="outline"
            size="icon"
            className="h-7 w-7 rounded-md"
            aria-label={`Aumentar ${item.product.name}`}
            onClick={onIncrease}
          >
            <Plus className="h-3 w-3" />
          </Button>
        </div>

        <Button
          variant="outline"
          size="icon"
          className="h-7 w-7 rounded-md text-destructive hover:bg-destructive/10 hover:text-destructive"
          aria-label={`Remover ${item.product.name}`}
          onClick={onRemove}
        >
          <Trash2 className="h-3 w-3" />
        </Button>
      </div>

      <div className="mt-1.5">
        <Input
          value={item.observation}
          onChange={(e) => onObservationChange(e.target.value)}
          placeholder="Observação (ex: sem cebola)"
          className="h-7 rounded-md px-2 text-[11px]"
        />
        {templates.length > 0 && (
          <div className="mt-1 flex flex-wrap gap-1">
            {templates.map((template: string) => (
              <button
                key={template}
                type="button"
                className="rounded-full border border-border/70 bg-muted/50 px-1.5 py-0 text-[10px] text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                onClick={() => {
                  const current = item.observation
                  onObservationChange(current ? `${current}, ${template}` : template)
                }}
              >
                {template}
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}