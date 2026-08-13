"use client"

import Image from "next/image"
import { Badge } from "@/components/ui/badge"
import { NotebookPen } from "lucide-react"
import { cn } from "@/lib/utils"
import { resolveImageUrl } from "@/lib/resolve-image-url"

type Product = {
  uuid?: string
  identify?: string
  name: string
  price?: number | string | null
  promotional_price?: number | string | null
  image?: string | null
  image_url?: string | null
  url?: string | null
  [key: string]: any
}

interface ProductCardProps {
  product: Product
  price: number
  formatCurrency: (value: number) => string
  getProductId: (product: Product) => string
  onSelect: (product: Product) => void
  className?: string
}

export function ProductCard({
  product,
  price,
  formatCurrency,
  getProductId,
  onSelect,
  className,
}: ProductCardProps) {
  const hasPromo =
    product.promotional_price &&
    parsePrice(product.promotional_price) < parsePrice(product.price || 0)
  const promoPrice = hasPromo
    ? parsePrice(product.promotional_price)
    : null
  const displayPrice = promoPrice || price

  const productImage =
    resolveImageUrl(product.image_url) ||
    resolveImageUrl(product.image) ||
    resolveImageUrl(product.url)

  return (
    <button
      key={getProductId(product)}
      data-testid={`touch-product-${getProductId(product)}`}
      onClick={() => onSelect(product)}
      className={cn(
        "group flex flex-col rounded-[14px] border bg-card text-left shadow-md transition-all overflow-hidden",
        "hover:shadow-lg hover:scale-[1.02]",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary",
        "active:scale-[0.98]",
        className
      )}
    >
      {/* Foto reduzida */}
      <div className="relative w-full h-32 overflow-hidden bg-muted">
        {productImage ? (
          <Image
            src={productImage}
            alt={product.name}
            fill
            className="object-cover transition-transform duration-300 ease-out group-hover:scale-110"
            unoptimized
            loading="lazy"
            sizes="(max-width: 640px) 50vw, (max-width: 1280px) 33vw, 25vw"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center text-muted-foreground">
            <NotebookPen className="h-6 w-6" />
          </div>
        )}
        {hasPromo && (
          <Badge
            variant="destructive"
            className="absolute top-1 right-1 text-[10px] px-1.5 py-0.5 font-bold bg-red-500"
          >
            PROMO
          </Badge>
        )}
      </div>
      
      {/* Conteúdo */}
      <div className="flex flex-1 flex-col gap-1.5 p-2.5">
        {/* Nome com tipografia reduzida */}
        <p className="text-sm font-semibold leading-tight line-clamp-2">
          {product.name}
        </p>
        
        {/* Preço destacado */}
        <div className="flex items-center gap-1.5">
          {hasPromo && (
            <p className="text-xs text-muted-foreground line-through">
              {formatCurrency(parsePrice(product.price || 0))}
            </p>
          )}
          <p className="text-lg font-bold text-primary">
            {formatCurrency(displayPrice)}
          </p>
        </div>
        
        {/* Ícone de observação integrado */}
        <div
          onClick={(e) => {
            e.stopPropagation()
            // TODO: Abrir dialog de observação
          }}
          onKeyDown={(e) => {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault()
              e.stopPropagation()
              // TODO: Abrir dialog de observação
            }
          }}
          role="button"
          tabIndex={0}
          className="text-[10px] text-gray-600 hover:text-primary flex items-center gap-1 mt-0.5 self-start cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded"
        >
          <NotebookPen className="w-3 h-3" />
          <span>Observação</span>
        </div>
      </div>
    </button>
  )
}

function parsePrice(value?: number | string | null): number {
  if (typeof value === "number") {
    return value
  }
  if (typeof value === "string") {
    const parsed = parseFloat(value)
    return Number.isNaN(parsed) ? 0 : parsed
  }
  return 0
}

