"use client"

import type { ReactNode } from "react"
import { Eye } from "lucide-react"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import type { KpiCardOption } from "../lib/kpi-card-visibility"

interface KpiSectionHeaderProps {
  label: ReactNode
  cards: readonly KpiCardOption[]
  hidden: Set<string>
  onSetVisible: (id: string, visible: boolean) => void
  onShowAll: (cards: readonly KpiCardOption[]) => void
}

export function KpiSectionHeader({
  label,
  cards,
  hidden,
  onSetVisible,
  onShowAll,
}: KpiSectionHeaderProps) {
  const hiddenCount = cards.filter((card) => hidden.has(card.id)).length

  return (
    <div className="flex items-center justify-between gap-2">
      <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        {label}
      </h3>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            className="h-7 gap-1.5 px-2 text-xs text-muted-foreground"
            aria-label="Exibir ou ocultar cards"
          >
            <Eye className="size-3.5" />
            Cards
            {hiddenCount > 0 && (
              <span className="tabular-nums text-muted-foreground/80">
                ({cards.length - hiddenCount}/{cards.length})
              </span>
            )}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          <DropdownMenuLabel>Exibir cards</DropdownMenuLabel>
          {cards.map((card) => (
            <DropdownMenuCheckboxItem
              key={card.id}
              checked={!hidden.has(card.id)}
              onCheckedChange={(checked) => onSetVisible(card.id, !!checked)}
            >
              {card.label}
            </DropdownMenuCheckboxItem>
          ))}
          {hiddenCount > 0 && (
            <>
              <DropdownMenuSeparator />
              <DropdownMenuItem onSelect={() => onShowAll(cards)}>
                Exibir todos
              </DropdownMenuItem>
            </>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  )
}
