"use client"

import { useEffect, useRef, useState } from "react"
import { Menu } from "lucide-react"
import { cn } from "@/lib/utils"
import { Sheet, SheetContent, SheetHeader, SheetTitle } from "@/components/ui/sheet"

export interface MenuSectionRef {
  id: string
  name: string
}

interface MenuCategoryTabsProps {
  sections: MenuSectionRef[]
  /** Altura do header fixo — a mesma usada no scroll-margin-top das seções */
  stickyOffset: number
}

/** Ignora o scroll-spy enquanto a rolagem disparada por clique acontece, para a aba não "piscar". */
const CLICK_LOCK_MS = 800

export function MenuCategoryTabs({ sections, stickyOffset }: MenuCategoryTabsProps) {
  const [activeId, setActiveId] = useState(sections[0]?.id)
  const [sheetOpen, setSheetOpen] = useState(false)
  const scrollerRef = useRef<HTMLDivElement>(null)
  const lockUntil = useRef(0)

  // Scroll-spy: ativa é a última seção cujo título já chegou ao header fixo; no fim da página, a última
  useEffect(() => {
    let frame = 0
    const update = () => {
      frame = 0
      if (Date.now() < lockUntil.current || sections.length === 0) return
      if (window.scrollY <= 0) {
        setActiveId(sections[0].id)
        return
      }
      const atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2
      let current = sections[0].id
      for (const section of sections) {
        const top = document.getElementById(section.id)?.getBoundingClientRect().top
        if (top !== undefined && top <= stickyOffset + 1) current = section.id
      }
      setActiveId(atBottom ? sections[sections.length - 1].id : current)
    }
    const onScroll = () => {
      if (!frame) frame = requestAnimationFrame(update)
    }
    update()
    window.addEventListener("scroll", onScroll, { passive: true })
    return () => {
      window.removeEventListener("scroll", onScroll)
      cancelAnimationFrame(frame)
    }
  }, [sections, stickyOffset])

  // Centraliza a aba ativa rolando só a faixa de abas (scrollIntoView aqui interromperia a rolagem da página)
  useEffect(() => {
    const scroller = scrollerRef.current
    const tab = scroller?.querySelector<HTMLElement>('[aria-current="true"]')
    if (!scroller || !tab || typeof scroller.scrollTo !== "function") return
    scroller.scrollTo({ left: tab.offsetLeft - (scroller.clientWidth - tab.offsetWidth) / 2, behavior: "smooth" })
  }, [activeId])

  const goTo = (id: string) => {
    lockUntil.current = Date.now() + CLICK_LOCK_MS
    setActiveId(id)
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" })
  }

  if (sections.length === 0) return null

  return (
    <nav aria-label="Categorias do cardápio" className="flex items-stretch border-b border-border/60">
      <button
        type="button"
        onClick={() => setSheetOpen(true)}
        aria-label="Ver todas as categorias"
        className="flex h-12 w-11 shrink-0 items-center justify-center text-foreground"
      >
        <Menu className="h-5 w-5" />
      </button>
      <div
        ref={scrollerRef}
        className="flex flex-1 gap-1 overflow-x-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
      >
        {sections.map((section) => {
          const active = section.id === activeId
          return (
            <button
              key={section.id}
              type="button"
              aria-current={active ? "true" : undefined}
              onClick={() => goTo(section.id)}
              className={cn(
                "relative h-12 shrink-0 whitespace-nowrap px-3 text-[15px] transition-colors",
                active ? "font-semibold text-foreground" : "text-muted-foreground hover:text-foreground",
              )}
            >
              {section.name}
              {active && <span aria-hidden className="absolute inset-x-2 bottom-0 h-[3px] rounded-full bg-foreground" />}
            </button>
          )
        })}
      </div>

      <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
        <SheetContent side="bottom" className="max-h-[80dvh] overflow-y-auto rounded-t-3xl px-0 pb-6">
          <SheetHeader className="px-5">
            <SheetTitle>Categorias</SheetTitle>
          </SheetHeader>
          <ul className="mt-2">
            {sections.map((section) => (
              <li key={section.id}>
                <button
                  type="button"
                  onClick={() => {
                    setSheetOpen(false)
                    // Espera o sheet fechar e liberar o scroll da página
                    setTimeout(() => goTo(section.id), 250)
                  }}
                  className={cn(
                    "w-full px-5 py-3.5 text-left text-base hover:bg-muted",
                    section.id === activeId && "font-semibold",
                  )}
                >
                  {section.name}
                </button>
              </li>
            ))}
          </ul>
        </SheetContent>
      </Sheet>
    </nav>
  )
}
