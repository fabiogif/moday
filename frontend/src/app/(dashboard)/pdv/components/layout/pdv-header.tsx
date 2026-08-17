"use client"

import { useState, useEffect } from "react"
import { useRouter } from "next/navigation"
import Image from "next/image"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Plus, ShoppingCart, Utensils, MessageSquare, BarChart, Eye, Truck, User } from "lucide-react"
import { OrderSearch } from "../search/order-search"
import { TableSearch } from "../search/table-search"
import { CombinedSearch } from "../search/combined-search"
import { ConnectionStatus } from "./connection-status"
import { cn } from "@/lib/utils"
import { apiClient } from "@/lib/api-client"
import { resolveImageUrl } from "@/lib/resolve-image-url"
import { useAuth } from "@/contexts/auth-context"

type Table = {
  uuid?: string
  identify?: string
  name: string
  isOccupied?: boolean
  orderCount?: number
}

type Order = {
  id?: string | number
  uuid?: string
  identify?: string
  table?: {
    uuid?: string
    identify?: string
    name?: string
  }
  status?: string | null
}

interface PDVHeaderProps {
  // Operador
  operatorName?: string

  // Busca
  onOrderSelect: (orderId: string) => void
  tables: Table[]
  onTableSelect?: (table: Table) => void
  isDelivery: boolean

  // Ações
  onNewOrder: () => void
  onFeedback?: () => void

  // Status
  cartItemCount: number
  selectedTableName?: string | null

  // Dashboard
  showDashboard?: boolean
  onToggleDashboard?: () => void
  canViewReports?: boolean
  dashboardComponent?: React.ReactNode
  useCombinedSearch?: boolean
}

interface TenantInfo {
  id: number
  uuid: string
  name: string
  slug: string
  logo?: string
  is_active: boolean
}

export function PDVHeader({
  operatorName,
  onOrderSelect,
  tables,
  onTableSelect,
  isDelivery,
  onNewOrder,
  onFeedback,
  cartItemCount,
  selectedTableName,
  showDashboard = false,
  onToggleDashboard,
  canViewReports = false,
  dashboardComponent,
  useCombinedSearch = false,
}: PDVHeaderProps) {
  const router = useRouter()
  const { user } = useAuth()
  const [tenant, setTenant] = useState<TenantInfo | null>(null)
  const [deliveryOn, setDeliveryOn] = useState(true)
  const [waiterOn, setWaiterOn] = useState(true)

  // Carregar informações do tenant
  useEffect(() => {
    const loadTenant = async () => {
      try {
        // Primeiro tentar obter do user do AuthContext
        if (user?.tenant) {
          const tenantData = user.tenant as any
          setTenant({
            id: parseInt(user.tenant_id || "0"),
            uuid: tenantData.uuid,
            name: tenantData.name,
            slug: tenantData.slug || "",
            logo: tenantData.logo,
            is_active: tenantData.is_active !== false,
          })
          return
        }

        // Se não tiver no user, buscar via /api/auth/me
        const userResponse = await apiClient.get("/api/auth/me")
        if (userResponse.success && userResponse.data) {
          const userData = userResponse.data as any
          if (userData.tenant) {
            setTenant({
              id: userData.tenant.id || parseInt(userData.tenant_id || "0"),
              uuid: userData.tenant.uuid,
              name: userData.tenant.name,
              slug: userData.tenant.slug || "",
              logo: userData.tenant.logo,
              is_active: userData.tenant.is_active !== false,
            })
            return
          }
        }

        // Se ainda não tiver, tentar buscar pelo UUID do tenant_id do user
        if (user?.tenant_id) {
          const tenantResponse = await apiClient.get(`/api/tenant/${user.tenant_id}`)
          if (tenantResponse.success && tenantResponse.data) {
            const tenantData = tenantResponse.data as any
            setTenant({
              id: tenantData.id,
              uuid: tenantData.uuid,
              name: tenantData.name,
              slug: tenantData.slug || "",
              logo: tenantData.logo,
              is_active: tenantData.is_active !== false,
            })
          }
        }
      } catch (error) {
        // Silenciosamente falhar - não é crítico para o funcionamento do PDV
        // O header funcionará sem o logo/nome do tenant
        if (process.env.NODE_ENV === "development") {
          console.warn("Não foi possível carregar informações do tenant:", error)
        }
      }
    }
    
    if (user) {
      loadTenant()
    }
  }, [user])

  return (
    <header className="flex-shrink-0 border-b bg-white dark:bg-gray-900 p-2.5 shadow-sm">
      {/* Layout otimizado: Logo | Busca | Status/Badge | Novo Pedido | ConnectionStatus */}
      <div className="flex items-center gap-3 flex-wrap">
        {/* Logo e Nome */}
        <div className="flex items-center gap-3 flex-shrink-0">
          {tenant?.logo && (
            <Image
              src={resolveImageUrl(tenant.logo) || "/placeholder.png"}
              alt={tenant.name}
              width={36}
              height={36}
              className="rounded-lg object-cover"
              unoptimized
            />
          )}
          <span className="font-bold text-lg lg:text-xl whitespace-nowrap">{tenant?.name || "PDV"}</span>
        </div>

        {/* Campo de Busca - ao lado do logo */}
        <div className="flex-1 min-w-[200px] max-w-[400px]">
          {useCombinedSearch ? (
            <CombinedSearch
              onOrderSelect={onOrderSelect}
              onTableSelect={onTableSelect}
              tables={tables}
              placeholder="Buscar produto ou mesa..."
            />
          ) : (
            <>
              <OrderSearch
                onOrderSelect={onOrderSelect}
                placeholder="Buscar produto ou mesa..."
              />
              {!isDelivery && (
                <TableSearch
                  tables={tables}
                  onTableSelect={onTableSelect}
                  placeholder="Buscar mesa..."
                  className="hidden lg:block"
                />
              )}
            </>
          )}
        </div>

        {/* Status Icons - compactos */}
        <div className="flex items-center gap-1.5 flex-shrink-0">
          <Badge
            variant={deliveryOn ? "default" : "secondary"}
            className="cursor-pointer text-[10px] px-2 py-0.5"
            onClick={() => setDeliveryOn(!deliveryOn)}
          >
            <Truck className="w-3 h-3 mr-1" />
            <span className="hidden sm:inline">Delivery</span> {deliveryOn ? "ON" : "OFF"}
          </Badge>
          <Badge
            variant={waiterOn ? "default" : "secondary"}
            className="cursor-pointer text-[10px] px-2 py-0.5"
            onClick={() => setWaiterOn(!waiterOn)}
          >
            <User className="w-3 h-3 mr-1" />
            <span className="hidden sm:inline">Garçom</span> {waiterOn ? "ON" : "OFF"}
          </Badge>
        </div>

        {/* Carrinho Badge */}
        <Badge variant="outline" className="text-sm px-2.5 py-1 flex-shrink-0">
          <ShoppingCart className="w-4 h-4 mr-1.5" />
          {cartItemCount}
        </Badge>

        {/* Novo Pedido - ao lado da busca */}
        <Button 
          onClick={onNewOrder} 
          className="bg-primary hover:bg-primary/90 flex-shrink-0 h-9 px-3 text-sm"
        >
          <Plus className="w-4 h-4 mr-1.5" />
          <span className="hidden sm:inline">Novo Pedido</span>
          <span className="sm:hidden">Novo</span>
        </Button>

        {/* Status de Conexão */}
        <div className="flex-shrink-0">
          <ConnectionStatus />
        </div>
        
        {/* Dashboard Rápido */}
        {canViewReports && showDashboard && dashboardComponent && (
          <div className="min-w-0 flex-1 basis-full lg:basis-auto">{dashboardComponent}</div>
        )}
        {canViewReports && !showDashboard && onToggleDashboard && (
          <Button
            variant="ghost"
            size="icon"
            onClick={onToggleDashboard}
            className="h-8 w-8 flex-shrink-0"
            title="Exibir painel"
          >
            <Eye className="h-4 w-4" />
          </Button>
        )}
      </div>
    </header>
  )
}

