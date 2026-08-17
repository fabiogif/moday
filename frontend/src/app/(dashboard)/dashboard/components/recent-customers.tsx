"use client"

import { useMemo } from "react"
import { Users } from "lucide-react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import { EmptyState } from "@/components/ui/empty-state"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"

interface Client {
  id: number
  name: string
  email: string
  last_order: string | null
  last_order_raw: string | null
  total_orders: number
  is_active: boolean
}

function getInitials(name: string) {
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2)
}

/**
 * Reaproveita a mesma chamada (endpoint + params) que CustomersChart já faz,
 * para cair no mesmo cache do useAuthenticatedApi e não duplicar a
 * requisição de clientes. A API não retorna "valor gasto" por cliente
 * hoje — mostramos "total de pedidos" como proxy real em vez de inventar
 * um valor.
 */
export function RecentCustomers() {
  const { data: clients, loading, error, refetch } = useAuthenticatedApi<any>(endpoints.clients.list, {
    queryParams: { per_page: 200 },
  })

  const clientsList: Client[] = Array.isArray(clients) ? clients : (clients as any)?.data ?? []

  const recentClients = useMemo(() => {
    return [...clientsList]
      .filter((c) => c.last_order_raw)
      .sort((a, b) => new Date(b.last_order_raw!).getTime() - new Date(a.last_order_raw!).getTime())
      .slice(0, 6)
  }, [clientsList])

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Clientes Recentes</CardTitle>
          <CardDescription>Últimas compras</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="flex items-center gap-3">
              <Skeleton className="h-9 w-9 rounded-full" />
              <div className="flex-1 space-y-1.5">
                <Skeleton className="h-4 w-32" />
                <Skeleton className="h-3 w-24" />
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    )
  }

  if (error && clientsList.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Clientes Recentes</CardTitle>
        </CardHeader>
        <CardContent>
          <EmptyState
            variant="error"
            icon={Users}
            title="Não foi possível carregar os clientes."
            action={{ label: "Tentar novamente", onClick: () => refetch() }}
          />
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Users className="h-5 w-5" />
          Clientes Recentes
        </CardTitle>
        <CardDescription>Últimas compras</CardDescription>
      </CardHeader>
      <CardContent>
        {recentClients.length === 0 ? (
          <EmptyState
            icon={Users}
            title="Nenhuma compra de cliente registrada ainda."
            size="sm"
          />
        ) : (
          <div className="space-y-4">
            {recentClients.map((client) => (
              <div key={client.id} className="flex items-center gap-3">
                <Avatar className="h-9 w-9">
                  <AvatarFallback>{getInitials(client.name)}</AvatarFallback>
                </Avatar>
                <div className="flex-1 min-w-0">
                  <p className="truncate text-sm font-medium leading-none">{client.name}</p>
                  <p className="text-xs text-muted-foreground mt-1">
                    Última compra: {client.last_order ?? "—"} · {client.total_orders} pedido
                    {client.total_orders === 1 ? "" : "s"}
                  </p>
                </div>
                <Badge variant={client.is_active ? "default" : "outline"} className="text-xs">
                  {client.is_active ? "Ativo" : "Inativo"}
                </Badge>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
