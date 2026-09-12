"use client"

import { useEffect, useRef, useState } from "react"
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
  const scrollerRef = useRef<HTMLDivElement>(null)
  const [canScrollLeft, setCanScrollLeft] = useState(false)
  const [canScrollRight, setCanScrollRight] = useState(false)

  const updateScrollState = () => {
    const el = scrollerRef.current
    if (!el) return
    setCanScrollLeft(el.scrollLeft > 4)
    setCanScrollRight(el.scrollLeft + el.clientWidth < el.scrollWidth - 4)
  }

  useEffect(() => {
    const el = scrollerRef.current
    if (!el) return
    updateScrollState()
    el.addEventListener("scroll", updateScrollState, { passive: true })
    const observer = new ResizeObserver(updateScrollState)
    observer.observe(el)
    return () => {
      el.removeEventListener("scroll", updateScrollState)
      observer.disconnect()
    }
  }, [categories])

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
      <div className="relative">
        {canScrollLeft && (
          <div className="pointer-events-none absolute inset-y-0 left-0 z-[1] w-8 bg-gradient-to-r from-background to-transparent" />
        )}

        <div
          ref={scrollerRef}
          className="flex flex-nowrap items-center gap-2 overflow-x-auto scroll-smooth pb-0.5 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
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
                  "inline-flex h-11 max-w-[13rem] shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-left text-sm font-medium transition-colors",
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
                <span className="min-w-0 truncate leading-tight">{category.name}</span>
              </button>
            )
          })}
        </div>

        {canScrollRight && (
          <div className="pointer-events-none absolute inset-y-0 right-0 z-[1] w-8 bg-gradient-to-l from-background to-transparent" />
        )}
      </div>
    </div>
  )
}
