"use client"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { cn } from "@/lib/utils"

type Category = {
  uuid?: string
  identify?: string
  name: string
  color?: string
}

interface CategoryTabsProps {
  categories: Category[]
  selectedCategory: string | null
  onCategorySelect: (categoryKey: string) => void
}

export function CategoryTabs({ categories, selectedCategory, onCategorySelect }: CategoryTabsProps) {
  return (
    <Card id="categories-section" className="flex-shrink-0 border-blue-200 bg-blue-50/50 dark:border-blue-800 dark:bg-blue-950/20">
      <CardHeader className="pb-2">
        <CardTitle className="text-base text-blue-900 dark:text-blue-100">Categorias</CardTitle>
      </CardHeader>
      <CardContent className="pt-0">
        <div
          className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
          data-testid="touch-grid-categories"
        >
          {categories.map((category) => {
            const key = category.uuid || category.identify || category.name
            const active = selectedCategory === key
            return (
              <Button
                key={key}
                data-testid={`touch-category-${key}`}
                onClick={() => onCategorySelect(key)}
                className={cn(
                  "h-14 rounded-xl text-sm font-medium",
                  active
                    ? "bg-primary text-primary-foreground shadow-md"
                    : "bg-muted text-foreground hover:bg-primary/10"
                )}
              >
                {category.name}
              </Button>
            )
          })}
        </div>
      </CardContent>
    </Card>
  )
}

