"use client"

import * as React from "react"
import { usePathname } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import { SidebarTrigger } from "@/components/ui/sidebar"
import { CommandSearch, SearchTrigger } from "@/components/command-search"
import { ModeToggle } from "@/components/mode-toggle"
import { NotificationsButton } from "@/components/notifications/notifications-button"
import { Clock } from "lucide-react"
import { usePOSHeader } from "@/contexts/pos-header-context"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"

interface SiteHeaderProps {
  onNotificationsClick?: () => void
}

const routeLabels: Record<string, string> = {
  dashboard: "Painel de Controle",
  orders: "Pedidos",
  board: "Quadro Kanban",
  new: "Novo",
  pdv: "PDV",
  clients: "Clientes",
  reviews: "Avaliações",
  products: "Produtos",
  categories: "Categorias",
  tables: "Mesas",
  "service-types": "Tipos de Atendimento",
  financial: "Financeiro",
  "accounts-receivable": "Contas a Receber",
  "accounts-payable": "Contas a Pagar",
  expenses: "Despesas",
  suppliers: "Fornecedores",
  loyalty: "Fidelidade",
  program: "Programa",
  rewards: "Recompensas",
  marketing: "Marketing",
  coupons: "Cupons",
  events: "Eventos",
  reports: "Relatórios",
  "sales-performance": "Desempenho de Vendas",
  "payment-methods": "Formas de Pagamento",
  integrations: "Integrações",
  ifood: "iFood",
  catalogs: "Catálogo",
  oauth: "Autorização",
  users: "Usuários",
  profiles: "Perfis",
  permissions: "Permissões",
  settings: "Configurações",
  company: "Dados da Empresa",
  "store-hours": "Horários",
  delivery: "Delivery e Retirada",
  configuracoes: "Configurações",
  "status-pedidos": "Status de Pedidos",
  "contas-bancarias": "Dados Bancários",
  admin: "Admin",
  plans: "Planos",
  store: "Loja",
  chat: "Chat",
  tasks: "Tarefas",
  calendar: "Calendário",
  mail: "E-mail",
  faqs: "Perguntas Frequentes",
  "trial-expired": "Período de teste encerrado",
}

function useBreadcrumbs() {
  const pathname = usePathname()
  const segments = pathname?.split("/").filter(Boolean) ?? []

  const crumbs: { label: string; href: string; isLast: boolean }[] = []
  let accumulated = ""

  for (let i = 0; i < segments.length; i++) {
    const segment = segments[i]
    accumulated += `/${segment}`
    const label = routeLabels[segment] ?? segment
    crumbs.push({ label, href: accumulated, isLast: i === segments.length - 1 })
  }

  return crumbs
}

export function SiteHeader({ onNotificationsClick }: SiteHeaderProps = {}) {
  const [searchOpen, setSearchOpen] = React.useState(false)
  const pathname = usePathname()
  const isPOSPage = pathname?.includes("/pdv")
  const crumbs = useBreadcrumbs()

  const { onTodayOrdersClick, todayOrdersCount } = usePOSHeader()

  React.useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if (e.key === "k" && (e.metaKey || e.ctrlKey)) {
        e.preventDefault()
        setSearchOpen((open) => !open)
      }
    }
    document.addEventListener("keydown", down)
    return () => document.removeEventListener("keydown", down)
  }, [])

  return (
    <>
      <header className="flex h-(--header-height) shrink-0 items-center gap-2 border-b bg-background/95 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-(--header-height)">
        <div className="flex w-full items-center gap-1 px-4 py-3 lg:gap-2 lg:px-6">
          <SidebarTrigger className="-ml-1" />
          <Separator orientation="vertical" className="mx-2 data-[orientation=vertical]:h-4" />

          {/* Breadcrumbs */}
          {crumbs.length > 0 && (
            <Breadcrumb className="hidden sm:flex">
              <BreadcrumbList>
                {crumbs.map((crumb, index) => (
                  <React.Fragment key={crumb.href}>
                    <BreadcrumbItem>
                      {crumb.isLast ? (
                        <BreadcrumbPage className="font-medium">{crumb.label}</BreadcrumbPage>
                      ) : (
                        <BreadcrumbLink href={crumb.href} className="text-muted-foreground hover:text-foreground">
                          {crumb.label}
                        </BreadcrumbLink>
                      )}
                    </BreadcrumbItem>
                    {!crumb.isLast && <BreadcrumbSeparator />}
                  </React.Fragment>
                ))}
              </BreadcrumbList>
            </Breadcrumb>
          )}

          <div className="ml-auto flex items-center gap-2">
            <div className="hidden md:flex">
              <SearchTrigger onClick={() => setSearchOpen(true)} />
            </div>
            {onNotificationsClick && (
              <NotificationsButton onClick={onNotificationsClick} />
            )}
            {isPOSPage && onTodayOrdersClick && (
              <Button
                variant="ghost"
                size="icon"
                className="relative"
                onClick={onTodayOrdersClick}
                aria-label="Pedidos de Hoje"
              >
                <Clock className="h-5 w-5" />
                {todayOrdersCount > 0 && (
                  <span className="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-primary text-primary-foreground text-[10px] flex items-center justify-center font-bold">
                    {todayOrdersCount > 99 ? "99+" : todayOrdersCount}
                  </span>
                )}
              </Button>
            )}
            <ModeToggle />
          </div>
        </div>
      </header>
      <CommandSearch open={searchOpen} onOpenChange={setSearchOpen} />
    </>
  )
}
