"use client"

import { useEffect, useState } from "react"
import { format } from "date-fns"
import { ptBR } from "date-fns/locale"
import {
  Calendar,
  Clock,
  MapPin,
  Package,
  User,
  Phone,
  Mail,
  Truck,
  Home,
  FileText,
} from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import { ScrollArea } from "@/components/ui/scroll-area"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import { apiClient, endpoints } from "@/lib/api-client"
import type { OrderDetails } from "../../types"
import type { BoardOrder } from "../types"
import { boardOrderToDetails, normalizeBoardOrder } from "../utils"

interface OrderDetailSheetProps {
  order: BoardOrder | null
  open: boolean
  onOpenChange: (open: boolean) => void
}

function getStatusColor(status: string) {
  switch (status) {
    case "Entregue":
      return "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
    case "Pronto":
      return "text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20"
    case "Em Preparo":
      return "text-yellow-700 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20"
    case "Cancelado":
      return "text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20"
    default:
      return "text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20"
  }
}

function formatCurrency(value: number | string | undefined) {
  const numValue = typeof value === "string" ? parseFloat(value) : value
  if (numValue === undefined || numValue === null || isNaN(numValue)) {
    return "R$ 0,00"
  }
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
  }).format(numValue)
}

function formatDate(dateString: string) {
  if (dateString && dateString.includes("/")) {
    return dateString
  }
  try {
    return format(new Date(dateString), "dd/MM/yyyy 'às' HH:mm", { locale: ptBR })
  } catch {
    return dateString || "Data inválida"
  }
}

function getFullDeliveryAddress(order: OrderDetails) {
  if (order.full_delivery_address) return order.full_delivery_address

  if (order.use_client_address && order.client?.address) {
    return [
      order.client.address,
      order.client.number,
      order.client.complement,
      order.client.neighborhood,
      order.client.city,
      order.client.state,
      order.client.zip_code,
    ]
      .filter(Boolean)
      .join(", ")
  }

  if (order.is_delivery && order.delivery_address) {
    return [
      order.delivery_address,
      order.delivery_number,
      order.delivery_complement,
      order.delivery_neighborhood,
      order.delivery_city,
      order.delivery_state,
      order.delivery_zip_code,
    ]
      .filter(Boolean)
      .join(", ")
  }

  return "Endereço não informado"
}

export function OrderDetailSheet({ order, open, onOpenChange }: OrderDetailSheetProps) {
  const [details, setDetails] = useState<OrderDetails | null>(null)
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    if (!open || !order) {
      setDetails(null)
      return
    }

    setDetails(boardOrderToDetails(order))

    let cancelled = false
    async function refresh() {
      try {
        setLoading(true)
        const res = await apiClient.get<any>(endpoints.orders.show(order!.identify))
        const raw = res.data?.data ?? res.data
        if (!cancelled && raw) {
          setDetails(boardOrderToDetails(normalizeBoardOrder(raw)))
        }
      } catch {
        // Mantém dados do board se o fetch falhar
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    refresh()
    return () => {
      cancelled = true
    }
  }, [open, order])

  const view = details

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent side="right" className="w-full sm:max-w-lg p-0 flex flex-col gap-0">
        <SheetHeader className="p-4 border-b space-y-1">
          <div className="flex items-center justify-between gap-2 pr-6">
            <SheetTitle>
              Pedido #{view?.identify || order?.identify || "?"}
            </SheetTitle>
            {view && (
              <Badge className={getStatusColor(view.status)}>{view.status}</Badge>
            )}
          </div>
          <SheetDescription>
            {loading ? "Atualizando detalhes..." : "Detalhes do pedido sem sair do quadro"}
          </SheetDescription>
        </SheetHeader>

        {view ? (
          <ScrollArea className="flex-1 px-4">
            <div className="space-y-6 py-4">
              {view.client && (
                <div className="space-y-3">
                  <div className="flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    <h3 className="font-medium">Cliente</h3>
                  </div>
                  <div className="grid gap-3 pl-6 text-sm">
                    <div>
                      <p className="text-muted-foreground">Nome</p>
                      <p className="font-medium">{view.client.name || "N/A"}</p>
                    </div>
                    {view.client.email && (
                      <div className="flex items-center gap-2">
                        <Mail className="h-3.5 w-3.5 text-muted-foreground" />
                        <span>{view.client.email}</span>
                      </div>
                    )}
                    {view.client.phone && (
                      <div className="flex items-center gap-2">
                        <Phone className="h-3.5 w-3.5 text-muted-foreground" />
                        <span>{view.client.phone}</span>
                      </div>
                    )}
                  </div>
                </div>
              )}

              <Separator />

              <div className="space-y-3">
                <div className="flex items-center gap-2">
                  <FileText className="h-4 w-4 text-muted-foreground" />
                  <h3 className="font-medium">Pedido</h3>
                </div>
                <div className="grid gap-3 pl-6 text-sm">
                  <div className="flex items-center gap-2">
                    <Calendar className="h-3.5 w-3.5 text-muted-foreground" />
                    <span>{formatDate(view.date || view.orderDate || "")}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    {view.is_delivery ? (
                      <Truck className="h-3.5 w-3.5 text-muted-foreground" />
                    ) : (
                      <Home className="h-3.5 w-3.5 text-muted-foreground" />
                    )}
                    <span>{view.is_delivery ? "Delivery" : "Balcão / Mesa"}</span>
                  </div>
                  {view.table && (
                    <div>
                      <p className="text-muted-foreground">Mesa</p>
                      <p className="font-medium">
                        {view.table.name}
                        {view.table.capacity
                          ? ` (Capacidade: ${view.table.capacity})`
                          : ""}
                      </p>
                    </div>
                  )}
                  {view.deliveryDate && (
                    <div className="flex items-center gap-2">
                      <Clock className="h-3.5 w-3.5 text-muted-foreground" />
                      <span>{formatDate(view.deliveryDate)}</span>
                    </div>
                  )}
                </div>
              </div>

              {view.is_delivery && (
                <>
                  <Separator />
                  <div className="space-y-3">
                    <div className="flex items-center gap-2">
                      <MapPin className="h-4 w-4 text-muted-foreground" />
                      <h3 className="font-medium">Endereço de entrega</h3>
                    </div>
                    <div className="pl-6 text-sm">
                      <p className="font-medium">{getFullDeliveryAddress(view)}</p>
                      {view.delivery_notes && (
                        <p className="mt-2 text-muted-foreground italic">
                          Obs: {view.delivery_notes}
                        </p>
                      )}
                    </div>
                  </div>
                </>
              )}

              <Separator />

              <div className="space-y-3">
                <div className="flex items-center gap-2">
                  <Package className="h-4 w-4 text-muted-foreground" />
                  <h3 className="font-medium">Itens</h3>
                </div>
                <div className="pl-6 space-y-2">
                  {(view.products || []).map((item, index) => (
                    <div
                      key={item.id || `item-${index}`}
                      className="flex justify-between items-center p-2.5 border rounded-md text-sm"
                    >
                      <div>
                        <p className="font-medium">{item.name}</p>
                        <p className="text-muted-foreground">
                          {item.quantity || 1}x {formatCurrency(item.price)}
                        </p>
                      </div>
                      <p className="font-medium">
                        {formatCurrency(item.total || item.price * (item.quantity || 1))}
                      </p>
                    </div>
                  ))}
                  <div className="flex justify-between items-center pt-2 font-semibold">
                    <span>Total</span>
                    <span>{formatCurrency(view.total)}</span>
                  </div>
                </div>
              </div>

              {view.comment && (
                <>
                  <Separator />
                  <div className="space-y-3">
                    <div className="flex items-center gap-2">
                      <FileText className="h-4 w-4 text-muted-foreground" />
                      <h3 className="font-medium">Observações</h3>
                    </div>
                    <p className="pl-6 text-sm bg-muted p-3 rounded-md">{view.comment}</p>
                  </div>
                </>
              )}
            </div>
          </ScrollArea>
        ) : (
          <div className="flex-1 p-4 text-sm text-muted-foreground">Carregando...</div>
        )}

        <SheetFooter className="border-t p-4">
          <Button variant="outline" onClick={() => onOpenChange(false)} className="w-full">
            Fechar
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}
