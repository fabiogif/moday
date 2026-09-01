"use client"

import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"
import Image from "next/image"
import { resolveImageUrl } from "@/lib/resolve-image-url"
import { X } from "lucide-react"

type Category = {
  uuid?: string
  identify?: string
  name: string
  image?: string | null
  image_url?: string | null
  [key: string]: any
}

interface ProductFiltersProps {
  categories: Category[]
  selectedCategory: string | null
  onCategorySelect: (categoryKey: string | null) => void
  className?: string
}

export function ProductFilters({
  categories,
  selectedCategory,
  onCategorySelect,
  className,
}: ProductFiltersProps) {
  return (
    <div className={cn("space-y-2", className)}>
      <div className="flex items-center justify-between gap-2">
        <p className="text-sm font-semibold text-foreground">Categorias</p>
        {selectedCategory && (
          <Button
            variant="ghost"
            size="sm"
            onClick={() => onCategorySelect(null)}
            className="h-8 px-2 text-xs"
          >
            <X className="mr-1 h-3.5 w-3.5" />
            Limpar
          </Button>
        )}
      </div>
      <div
        className="flex max-h-[8.5rem] flex-wrap content-start gap-2 overflow-y-auto pr-0.5"
        data-testid="touch-grid-categories"
      >
        {categories.map((category) => {
          const key = category.uuid || category.identify || category.name
          const active = selectedCategory === key
          const categoryImage = resolveImageUrl(category.image_url || category.image || "")

          return (
            <button
              key={key}
              type="button"
              data-testid={`touch-category-${key}`}
              aria-pressed={active}
              onClick={() => onCategorySelect(active ? null : key)}
              title={category.name}
              className={cn(
                "inline-flex min-h-11 max-w-full shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-left text-sm font-medium transition-colors",
                active
                  ? "border-primary bg-primary text-primary-foreground shadow-sm"
                  : "border-border bg-background text-foreground hover:border-primary/40 hover:bg-muted"
              )}
            >
              {categoryImage ? (
                <Image
                  src={categoryImage}
                  alt=""
                  width={28}
                  height={28}
                  className="h-7 w-7 shrink-0 rounded-full object-cover"
                  unoptimized
                />
              ) : (
                <span
                  className={cn(
                    "flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold",
                    active
                      ? "bg-primary-foreground/20 text-primary-foreground"
                      : "bg-muted text-muted-foreground"
                  )}
                >
                  {category.name.charAt(0).toUpperCase()}
                </span>
              )}
              <span className="max-w-[12rem] leading-tight line-clamp-2 sm:max-w-[16rem]">
                {category.name}
              </span>
            </button>
          )
        })}
      </div>
    </div>
  )
}
