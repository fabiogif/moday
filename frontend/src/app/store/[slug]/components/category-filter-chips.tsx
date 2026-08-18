"use client"

import { useEffect, useRef, useState } from "react"
import { ChevronLeft, ChevronRight } from "lucide-react"
import { cn } from "@/lib/utils"

interface CategoryFilterChipsProps {
  categories: string[]
  selected: string
  onSelect: (category: string) => void
  allLabel?: string
}

function chipClass(active: boolean) {
  return cn(
    "shrink-0 max-w-[11rem] truncate rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors",
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

  useEffect(() => {
    const active = scrollerRef.current?.querySelector<HTMLElement>('[aria-pressed="true"]')
    active?.scrollIntoView?.({ inline: "center", block: "nearest", behavior: "smooth" })
  }, [selected])

  const scrollByDirection = (direction: -1 | 1) => {
    const el = scrollerRef.current
    if (!el) return
    el.scrollBy({ left: direction * Math.max(180, el.clientWidth * 0.65), behavior: "smooth" })
  }

  if (categories.length === 0) {
    return null
  }

  return (
    <div className="relative">
      {canScrollLeft && (
        <>
          <div className="pointer-events-none absolute inset-y-0 left-0 z-[1] w-10 bg-gradient-to-r from-background to-transparent" />
          <button
            type="button"
            aria-label="Categorias anteriores"
            onClick={() => scrollByDirection(-1)}
            className="absolute left-0 top-1/2 z-[2] flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border bg-background/95 text-foreground shadow-sm"
          >
            <ChevronLeft className="size-4" />
          </button>
        </>
      )}

      <div
        ref={scrollerRef}
        className={cn(
          "flex flex-nowrap gap-2 overflow-x-auto scroll-smooth",
          "[scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden",
        )}
        aria-label="Categorias do cardápio"
      >
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

      {canScrollRight && (
        <>
          <div className="pointer-events-none absolute inset-y-0 right-0 z-[1] w-10 bg-gradient-to-l from-background to-transparent" />
          <button
            type="button"
            aria-label="Próximas categorias"
            onClick={() => scrollByDirection(1)}
            className="absolute right-0 top-1/2 z-[2] flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border bg-background/95 text-foreground shadow-sm"
          >
            <ChevronRight className="size-4" />
          </button>
        </>
      )}
    </div>
  )
}
