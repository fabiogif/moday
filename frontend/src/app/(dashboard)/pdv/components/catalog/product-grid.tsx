"use client"

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ProductCard } from "./product-card"
import { cn } from "@/lib/utils"

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

interface ProductGridProps {
  products: Product[]
  onProductSelect: (product: Product) => void
  getProductPrice: (product: Product) => number
  getProductId: (product: Product) => string
  formatCurrency: (value: number) => string
  selectedCategory?: string | null
  emptyMessage?: string
  className?: string
}

export function ProductGrid({
  products,
  onProductSelect,
  getProductPrice,
  getProductId,
  formatCurrency,
  selectedCategory,
  emptyMessage,
  className,
}: ProductGridProps) {
  const defaultEmptyMessage = selectedCategory
    ? "Nenhum produto nesta categoria."
    : "Selecione uma categoria para ver os produtos."

  return (
    <Card
      id="products-section"
      className={cn(
        "flex h-full min-h-0 flex-1 flex-col gap-0 border-green-200 bg-green-50/30 py-0 dark:border-green-800 dark:bg-green-950/10",
        className
      )}
    >
      <CardHeader className="flex-shrink-0 gap-0 px-2 py-1.5 pb-1 sm:px-3">
        <CardTitle className="text-sm text-green-900 dark:text-green-100">
          Produtos
        </CardTitle>
      </CardHeader>
      <CardContent className="flex min-h-0 flex-1 flex-col overflow-hidden px-2 pb-1.5 pt-0 sm:px-3">
        {products.length === 0 ? (
          <div className="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
            {emptyMessage || defaultEmptyMessage}
          </div>
        ) : (
          <div className="flex-1 min-h-0 overflow-auto">
            <div
              className="grid grid-cols-2 gap-1.5 pb-2 sm:grid-cols-3 sm:gap-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6"
              data-testid="touch-grid-products"
            >
              {products.map((product) => {
                const price = getProductPrice(product)
                return (
                  <ProductCard
                    key={getProductId(product)}
                    product={product}
                    price={price}
                    formatCurrency={formatCurrency}
                    getProductId={getProductId}
                    onSelect={onProductSelect}
                  />
                )
              })}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  )
}

