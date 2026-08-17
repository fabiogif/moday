"use client"

import { memo } from "react"
import { Package, ShoppingCart, UserCheck, Users } from "lucide-react"

export type TrialUserStats = {
  clients?: number | null
  products?: number | null
  orders?: number | null
  users?: number | null
}

const items = [
  { key: "clients" as const, label: "Clientes", icon: UserCheck },
  { key: "products" as const, label: "Produtos", icon: Package },
  { key: "orders" as const, label: "Pedidos", icon: ShoppingCart },
  { key: "users" as const, label: "Usuários", icon: Users },
]

type UserStatisticsProps = {
  stats?: TrialUserStats
  loading?: boolean
}

function formatCount(value: number | null | undefined) {
  if (value == null || Number.isNaN(value)) return "—"
  return new Intl.NumberFormat("pt-BR").format(value)
}

function UserStatisticsComponent({ stats, loading }: UserStatisticsProps) {
  return (
    <section aria-labelledby="trial-stats-heading" className="space-y-3">
      <div>
        <h2 id="trial-stats-heading" className="font-semibold text-lg">
          Você já cadastrou
        </h2>
        <p className="text-sm text-muted-foreground">
          Tudo isso permanece no Alba Tec — basta escolher um plano para continuar.
        </p>
      </div>

      <ul className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {items.map(({ key, label, icon: Icon }) => (
          <li
            key={key}
            className="rounded-xl border bg-card px-3 py-3.5 text-center shadow-xs"
          >
            <div className="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-muted text-foreground">
              <Icon className="h-4 w-4" aria-hidden />
            </div>
            <p className="text-xl font-bold tabular-nums tracking-tight">
              {loading ? "…" : formatCount(stats?.[key])}
            </p>
            <p className="text-xs text-muted-foreground mt-0.5">{label}</p>
          </li>
        ))}
      </ul>
    </section>
  )
}

export const UserStatistics = memo(UserStatisticsComponent)
