"use client"

import { ShieldCheck } from "lucide-react"

export function DataProtectionCard() {
  return (
    <aside
      className="rounded-xl border border-primary/20 bg-primary/5 px-4 py-3 sm:px-5 sm:py-3.5"
      aria-label="Proteção dos dados"
    >
      <div className="flex items-start gap-3">
        <ShieldCheck className="h-5 w-5 text-primary shrink-0 mt-0.5" aria-hidden />
        <div className="min-w-0">
          <h2 className="font-semibold text-sm sm:text-base">Seus dados estão seguros</h2>
          <p className="text-sm text-muted-foreground mt-0.5 leading-relaxed">
            Nenhuma informação foi perdida. Ao ativar um plano você continua exatamente de onde
            parou.
          </p>
        </div>
      </div>
    </aside>
  )
}
