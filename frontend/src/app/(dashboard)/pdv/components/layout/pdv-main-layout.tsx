"use client"

import { ReactNode } from "react"
import { cn } from "@/lib/utils"

interface PDVMainLayoutProps {
  children: ReactNode
  className?: string
}

/**
 * Layout principal do PDV.
 * Reserva espaço para a barra de navegação mobile inferior (lg:hidden).
 */
export function PDVMainLayout({ children, className }: PDVMainLayoutProps) {
  return (
    <div
      className={cn(
        "flex min-h-0 flex-col overflow-hidden",
        "h-[calc(100dvh-10rem)] lg:h-[calc(100dvh-5rem)]",
        className
      )}
    >
      {children}
    </div>
  )
}

interface PDVTwoColumnLayoutProps {
  leftColumn: ReactNode
  rightColumn: ReactNode
  mobileActiveView?: "catalog" | "cart"
  className?: string
}

/**
 * Layout de duas colunas para o PDV.
 * Mobile: exibe uma coluna por vez, controlada por mobileActiveView.
 * Desktop (lg+): exibe ambas as colunas lado a lado.
 */
export function PDVTwoColumnLayout({
  leftColumn,
  rightColumn,
  mobileActiveView = "catalog",
  className,
}: PDVTwoColumnLayoutProps) {
  return (
    <div
      className={cn(
        "flex min-h-0 flex-1 gap-3 overflow-hidden p-2 sm:p-3",
        className
      )}
    >
      {/* Catálogo: full-width on mobile, 60% on desktop */}
      <section className={cn(
        "flex-col min-w-0 overflow-hidden min-h-0",
        "lg:flex lg:flex-[3]",
        mobileActiveView === "catalog" ? "flex flex-1" : "hidden"
      )}>
        {leftColumn}
      </section>

      {/* Carrinho: hidden on mobile (via nav tab), 40% on desktop */}
      <aside className={cn(
        "flex-col min-w-0 overflow-hidden min-h-0 flex-shrink-0",
        "lg:flex lg:flex-[2] lg:border-l lg:border-border/50 lg:pl-3",
        mobileActiveView === "cart" ? "flex flex-1" : "hidden"
      )}>
        {rightColumn}
      </aside>
    </div>
  )
}

