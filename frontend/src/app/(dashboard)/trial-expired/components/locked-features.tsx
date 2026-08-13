"use client"

import { memo } from "react"
import {
  Lock,
  ShoppingCart,
  Wallet,
  Megaphone,
  LineChart,
  MonitorSmartphone,
} from "lucide-react"

const locked = [
  { icon: ShoppingCart, label: "Pedidos" },
  { icon: Wallet, label: "Financeiro" },
  { icon: Megaphone, label: "Marketing" },
  { icon: LineChart, label: "Relatórios" },
  { icon: MonitorSmartphone, label: "PDV" },
]

function LockedFeaturesComponent() {
  return (
    <section aria-labelledby="locked-features-heading" className="space-y-3">
      <div>
        <h2 id="locked-features-heading" className="font-semibold text-lg">
          Desbloqueie ao assinar
        </h2>
        <p className="text-sm text-muted-foreground">
          Estes módulos ficam disponíveis imediatamente após a ativação do plano.
        </p>
      </div>

      <ul className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
        {locked.map(({ icon: Icon, label }) => (
          <li
            key={label}
            className="relative flex flex-col items-center gap-2 rounded-xl border bg-muted/40 px-3 py-4 text-center"
          >
            <span className="absolute top-2 right-2 text-muted-foreground">
              <Lock className="h-3.5 w-3.5" aria-hidden />
            </span>
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-background border text-muted-foreground">
              <Icon className="h-5 w-5" aria-hidden />
            </span>
            <span className="text-xs font-medium text-muted-foreground">{label}</span>
          </li>
        ))}
      </ul>
    </section>
  )
}

export const LockedFeatures = memo(LockedFeaturesComponent)
