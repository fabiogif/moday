"use client"

import * as React from "react"
import {
  Users,
  ShoppingCart,
  Tag,
  Package,
  Table,
  UserCheck,
  BarChart3,
  Shield,
  UserCog,
  CreditCard,
  Building2,
  CalendarDays,
  Wallet,
  TrendingDown,
  TrendingUp,
  MessageSquare,
  FileText,
  Truck,
  Clock,
  Gift,
  Store,
  Receipt,
  LayoutDashboard,
  Utensils,
  ListOrdered,
  TicketPercent,
  LineChart,
  MonitorSmartphone,
  Handshake,
  Settings,
  Megaphone,
} from "lucide-react"
import { usePathname } from "next/navigation"
import { useAuth } from "@/contexts/auth-context"
import { useAuthenticatedOrderStats } from "@/hooks/use-authenticated-api"

import { NavMain } from "@/components/nav-main"
import { NavUser } from "@/components/nav-user"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
} from "@/components/ui/sidebar"
import { AlbaTecLogo, PANEL_BRAND_ICON_SIZE } from "@/components/albatec-logo"
import { cn } from "@/lib/utils"

function buildNavGroups(openOrdersCount?: number) {
  return [
  {
    label: "Principal",
    items: [
      {
        title: "Painel de Controle",
        url: "/dashboard",
        icon: LayoutDashboard,
      },
      {
        title: "Pedidos",
        url: "/orders",
        icon: ShoppingCart,
        badge: openOrdersCount && openOrdersCount > 0 ? openOrdersCount : undefined,
        items: [
          { title: "Todos os Pedidos", url: "/orders" },
          { title: "Quadro Kanban", url: "/orders/board" },
        ],
      },
      {
        title: "PDV",
        url: "/pdv",
        icon: MonitorSmartphone,
      },
      {
        title: "Clientes",
        url: "/clients",
        icon: UserCheck,
      },
      {
        title: "Avaliações",
        url: "/reviews",
        icon: MessageSquare,
      },
    ],
  },
  {
    label: "Operações",
    items: [
      {
        title: "Cardápio",
        url: "/products",
        icon: Utensils,
        items: [
          { title: "Produtos", url: "/products" },
          { title: "Categorias", url: "/categories" },
          { title: "Mesas", url: "/tables" },
          { title: "Tipos de Atendimento", url: "/service-types" },
        ],
      },
      {
        title: "Financeiro",
        url: "/financial/dashboard",
        icon: Wallet,
        items: [
          { title: "Resumo", url: "/financial/dashboard" },
          { title: "Contas a Receber", url: "/financial/accounts-receivable" },
          { title: "Contas a Pagar", url: "/financial/accounts-payable" },
          { title: "Despesas", url: "/financial/expenses" },
          { title: "Fornecedores", url: "/financial/suppliers" },
          { title: "Categorias", url: "/financial/categories" },
          { title: "Dados Bancários", url: "/contas-bancarias" },
        ],
      },
      {
        title: "Marketing",
        url: "/marketing/coupons",
        icon: Megaphone,
        items: [
          { title: "Programa de Fidelidade", url: "/loyalty/program" },
          { title: "Recompensas", url: "/loyalty/rewards" },
          { title: "Cupons", url: "/marketing/coupons" },
          { title: "Eventos", url: "/events" },
        ],
      },
    ],
  },
  {
    label: "Análise",
    items: [
      {
        title: "Relatórios",
        url: "/reports",
        icon: BarChart3,
      },
      {
        title: "Desempenho de Vendas",
        url: "/sales-performance",
        icon: LineChart,
      },
      {
        title: "Formas de Pagamento",
        url: "/payment-methods",
        icon: CreditCard,
      },
      {
        title: "iFood",
        url: "/integrations/ifood/catalogs",
        icon: Store,
        items: [
          { title: "Catálogo", url: "/integrations/ifood/catalogs" },
          { title: "Autorização", url: "/integrations/ifood/oauth" },
          { title: "Pedidos iFood", url: "/integrations/ifood/orders" },
        ],
      },
    ],
  },
  {
    label: "Sistema",
    items: [
      {
        title: "Usuários",
        url: "/users",
        icon: Users,
      },
      {
        title: "Perfis e Permissões",
        url: "/profiles",
        icon: UserCog,
        items: [
          { title: "Perfis", url: "/profiles" },
          { title: "Permissões", url: "/permissions" },
        ],
      },
      {
        title: "Configurações",
        url: "/settings/company",
        icon: Settings,
        items: [
          { title: "Dados da Empresa", url: "/settings/company" },
          { title: "Horários de Funcionamento", url: "/settings/store-hours" },
          { title: "Delivery e Retirada", url: "/settings/delivery" },
          { title: "Status de Pedidos", url: "/configuracoes/status-pedidos" },
        ],
      },
    ],
  },
  ]
}

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  const { user, isAuthenticated, trialStatus } = useAuth()
  const pathname = usePathname()
  const { data: orderStats } = useAuthenticatedOrderStats() as {
    data: { in_preparo_orders?: { current: number } } | null
  }

  const navGroups = buildNavGroups(orderStats?.in_preparo_orders?.current)
  const modulesLocked =
    pathname === "/trial-expired" ||
    Boolean(trialStatus?.is_expired) ||
    Boolean(trialStatus?.needs_payment && !trialStatus?.is_active)

  const userData = {
    name: user?.name || "Usuário",
    email: user?.email || "user@example.com",
    avatar: "",
  }

  const tenantData = (user as any)?.tenant
  const tenantName = tenantData?.name || "Meu Restaurante"

  return (
    <Sidebar {...props} className={cn(props.className, modulesLocked && "select-none")}>
      <SidebarHeader className="flex flex-row items-center gap-0 border-b p-0 px-2 py-2">
        <div
          className={cn(
            "flex flex-row items-center gap-3 min-w-0 w-full",
            modulesLocked && "opacity-60 pointer-events-none cursor-not-allowed"
          )}
        >
          <AlbaTecLogo variant="icon" width={PANEL_BRAND_ICON_SIZE} height={PANEL_BRAND_ICON_SIZE} className="shrink-0" />
          <div className="flex flex-col leading-tight min-w-0 flex-1">
            <span className="font-semibold text-sm truncate">Alba Tec</span>
            <span className="text-xs text-muted-foreground truncate">{tenantName}</span>
          </div>
        </div>
      </SidebarHeader>
      <SidebarContent className={cn(modulesLocked && "pointer-events-none")}>
        {navGroups.map((group) => (
          <NavMain
            key={group.label}
            label={group.label}
            items={group.items}
            locked={modulesLocked}
          />
        ))}
      </SidebarContent>
      <SidebarFooter>
        {isAuthenticated ? (
          <NavUser user={userData} />
        ) : (
          <div className="p-2 text-center text-sm text-muted-foreground">
            Faça login para continuar
          </div>
        )}
      </SidebarFooter>
    </Sidebar>
  )
}

