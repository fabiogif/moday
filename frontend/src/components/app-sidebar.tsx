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
  Settings,
} from "lucide-react"
import Link from "next/link"
import { Logo } from "@/components/logo"
import { useAuthStore } from "@/contexts/auth-context"

import { NavMain } from "@/components/nav-main"
import { NavUser } from "@/components/nav-user"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar"

const navGroups = [
  {
    label: "Sistema",
    items: [
      {
        title: "Usuários",
        url: "/users",
        icon: Users,
      },
      {
        title: "Pedidos",
        url: "/orders",
        icon: ShoppingCart,
      },
      {
        title: "Categorias",
        url: "/categories",
        icon: Tag,
      },
      {
        title: "Produtos",
        url: "/products",
        icon: Package,
      },
      {
        title: "Mesas",
        url: "/tables",
        icon: Table,
      },
      {
        title: "Clientes",
        url: "/clients",
        icon: UserCheck,
      },
      {
        title: "Relatórios",
        url: "/reports",
        icon: BarChart3,
      },
    ],
  },
  {
    label: "Controle de Acesso",
    items: [
      {
        title: "Permissões",
        url: "/permissions",
        icon: Shield,
      },
      {
        title: "Funções",
        url: "/roles",
        icon: UserCog,
      },
      {
        title: "Perfis",
        url: "/profiles",
        icon: Settings,
      },
    ],
  },
]

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  const { user, isAuthenticated } = useAuthStore()

  // Dados do usuário para exibir no sidebar
  const userData = {
    name: user?.name || "Usuário",
    email: user?.email || "user@example.com",
    avatar: "",
  }

  return (
    <Sidebar {...props}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href="/dashboard-2">
                <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                  <Logo size={24} className="text-current" />
                </div>
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-medium">Moday</span>
                  <span className="truncate text-xs">Sistema de Gestão</span>
                </div>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        {navGroups.map((group) => (
          <NavMain key={group.label} label={group.label} items={group.items} />
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
