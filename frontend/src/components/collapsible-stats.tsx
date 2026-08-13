"use client"

import { useEffect, useState } from "react"
import { Eye, EyeOff } from "lucide-react"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

interface CollapsibleStatsProps {
  storageKey: string
  children: React.ReactNode
  className?: string
  label?: string
}

/**
 * Permite ocultar/exibir o box de estatísticas em qualquer resolução
 * (preferência persistida no localStorage).
 */
export function CollapsibleStats({
  storageKey,
  children,
  className,
  label = "estatísticas",
}: CollapsibleStatsProps) {
  const [visible, setVisible] = useState(true)

  useEffect(() => {
    try {
      const saved = localStorage.getItem(storageKey)
      if (saved !== null) {
        setVisible(saved === "true")
      }
    } catch {
      // ignore
    }
  }, [storageKey])

  const toggle = () => {
    setVisible((prev) => {
      const next = !prev
      try {
        localStorage.setItem(storageKey, String(next))
      } catch {
        // ignore
      }
      return next
    })
  }

  return (
    <div className={cn("space-y-3", className)}>
      <div className="flex justify-end">
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="cursor-pointer"
          onClick={toggle}
          aria-pressed={visible}
          aria-label={visible ? `Ocultar ${label}` : `Exibir ${label}`}
        >
          {visible ? (
            <>
              <EyeOff className="mr-2 h-4 w-4" />
              Ocultar {label}
            </>
          ) : (
            <>
              <Eye className="mr-2 h-4 w-4" />
              Exibir {label}
            </>
          )}
        </Button>
      </div>

      {visible ? children : null}
    </div>
  )
}
