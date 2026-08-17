"use client"

import { cn } from "@/lib/utils"

interface CategoryFilterChipsProps {
  categories: string[]
  selected: string
  onSelect: (category: string) => void
  allLabel?: string
}

function chipClass(active: boolean) {
  return cn(
    "max-w-full rounded-full px-3.5 py-1.5 text-left text-sm font-medium leading-snug break-words transition-colors",
    active
      ? "bg-primary text-primary-foreground shadow-sm"
      : "bg-muted text-muted-foreground hover:bg-muted/80",
  )
}

export function CategoryFilterChips({
  categories,
  selected,
  onSelect,
  allLabel = "Todos",
}: CategoryFilterChipsProps) {
  if (categories.length === 0) {
    return null
  }

  return (
    <div className="flex flex-wrap gap-2" aria-label="Categorias do cardápio">
      <button
        type="button"
        aria-pressed={selected === "all"}
        onClick={() => onSelect("all")}
        className={chipClass(selected === "all")}
      >
        {allLabel}
      </button>
      {categories.map((category) => (
        <button
          key={category}
          type="button"
          aria-pressed={selected === category}
          title={category}
          onClick={() => onSelect(category)}
          className={chipClass(selected === category)}
        >
          {category}
        </button>
      ))}
    </div>
  )
}
