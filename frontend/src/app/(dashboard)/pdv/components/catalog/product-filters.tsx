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
      <div className="flex items-center justify-between">
        <p className="text-xs font-medium">Categorias</p>
        {selectedCategory && (
          <Button
            variant="ghost"
            size="sm"
            onClick={() => onCategorySelect(null)}
            className="h-5 text-[10px] px-2 ml-auto"
          >
            <X className="h-3 w-3 mr-1" />
            Limpar
          </Button>
        )}
      </div>
      {/* Rolagem horizontal suave */}
      <div
        className="flex gap-4 overflow-x-auto pb-2 scrollbar-hide scroll-smooth"
        data-testid="touch-grid-categories"
        style={{
          scrollbarWidth: 'none',
          msOverflowStyle: 'none',
        }}
      >
        {categories.map((category) => {
          const key = category.uuid || category.identify || category.name
          const active = selectedCategory === key
          const categoryImage = resolveImageUrl(category.image_url || category.image || "")

          return (
            <button
              key={key}
              data-testid={`touch-category-${key}`}
              onClick={() => onCategorySelect(active ? null : key)}
              className={cn(
                "flex flex-col items-center gap-1 p-1 rounded-full transition-all min-w-[80px] h-[80px] flex-shrink-0",
                active
                  ? "border-2 border-primary bg-primary/10 shadow-lg scale-105"
                  : "border-1 border-gray-200 hover:border-gray-300 hover:scale-102"
              )}
              title={category.name}
            >
              {categoryImage ? (
                <Image
                  src={categoryImage}
                  alt={category.name}
                  width={48}
                  height={48}
                  className="rounded-full object-cover"
                  unoptimized
                />
              ) : (
                <div className="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                  <span className="text-xs font-medium text-gray-600 dark:text-gray-300">
                    {category.name.charAt(0).toUpperCase()}
                  </span>
                </div>
              )}
              <span className="text-xs font-medium truncate max-w-[80px] text-center">
                {category.name}
              </span>
            </button>
          )
        })}
      </div>
      <style jsx>{`
        .scrollbar-hide::-webkit-scrollbar {
          display: none;
        }
      `}</style>
    </div>
  )
}

