"use client"

import { memo } from "react"
import {
  ClipboardList,
  Users,
  LineChart,
  HardDrive,
  MessageCircle,
  RefreshCw,
  Headphones,
} from "lucide-react"

const benefits = [
  { icon: ClipboardList, label: "Continue emitindo pedidos" },
  { icon: Users, label: "Continue atendendo clientes" },
  { icon: LineChart, label: "Continue acessando relatórios" },
  { icon: HardDrive, label: "Backup automático diário" },
  { icon: MessageCircle, label: "Integração com WhatsApp" },
  { icon: RefreshCw, label: "Atualizações automáticas" },
  { icon: Headphones, label: "Suporte prioritário" },
]

function BenefitsSectionComponent() {
  return (
    <section aria-labelledby="trial-benefits-heading" className="space-y-3">
      <h2 id="trial-benefits-heading" className="font-semibold text-lg">
        O que você mantém ao assinar
      </h2>
      <ul className="grid gap-2 sm:grid-cols-2">
        {benefits.map(({ icon: Icon, label }) => (
          <li
            key={label}
            className="flex items-center gap-3 rounded-lg border bg-card px-3 py-2.5 text-sm"
          >
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
              <Icon className="h-4 w-4" aria-hidden />
            </span>
            <span className="font-medium">{label}</span>
          </li>
        ))}
      </ul>
    </section>
  )
}

export const BenefitsSection = memo(BenefitsSectionComponent)
