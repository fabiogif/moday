"use client"

import { useState } from "react"
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
  CreditCard,
  Truck,
  Home,
  FileText,
  X,
  CalendarPlus,
} from "lucide-react"
import { getApiBaseUrl } from "@/lib/api-config"
import { endpoints } from "@/lib/api-client"
import { toast } from "sonner"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Separator } from "@/components/ui/separator"
import { ScrollArea } from "@/components/ui/scroll-area"
import { OrderDetails } from "../types"

interface OrderDetailsDialogProps {
  order: OrderDetails | null
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function OrderDetailsDialog({ order, open, onOpenChange }: OrderDetailsDialogProps) {
  const [downloadingCalendar, setDownloadingCalendar] = useState(false)

  const handleDownloadCalendar = async () => {
    if (!order) return
    setDownloadingCalendar(true)
    try {
      const token = typeof window !== "undefined" ? localStorage.getItem("auth-token") : null
      const baseUrl = getApiBaseUrl()
      const res = await fetch(`${baseUrl}${endpoints.orders.calendarLink(order.identify)}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      })
      if (!res.ok) throw new Error("Falha ao gerar arquivo de calendário")
      const blob = await res.blob()
      const url = URL.createObjectURL(blob)
      const a = document.createElement("a")
      a.href = url
      a.download = `pedido-${order.identify}.ics`
      a.click()
      URL.revokeObjectURL(url)
    } catch {
      toast.error("Não foi possível gerar o arquivo de calendário")
    } finally {
      setDownloadingCalendar(false)
    }
  }

  if (!order) return null

  const getStatusColor = (status: string) => {
    switch (status) {
      case "Concluído":
        return "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
      case "Pendente":
        return "text-orange-600 bg-orange-50 dark:text-orange-400 dark:bg-orange-900/20"
      case "Aceito":
        return "text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/20"
      case "Preparo":
        return "text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20"
      case "Cancelado":
        return "text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20"
      default:
        return "text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20"
    }
  }

  const formatCurrency = (value: number | string | undefined) => {
    const numValue = typeof value === 'string' ? parseFloat(value) : value
    if (numValue === undefined || numValue === null || isNaN(numValue)) {
      return 'R$ 0,00'
    }
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(numValue)
  }

  const formatDate = (dateString: string) => {
    // SEMPRE usar o valor exato do banco, sem conversões de timezone
    // Se já está formatado (dd/mm/yyyy), retornar diretamente
    if (!dateString) return 'Data não informada'
    
    // Retornar exatamente como veio do banco
    return dateString
  }

  const getFullDeliveryAddress = () => {
    // First check if we have the pre-formatted address from backend
    if (order.full_delivery_address) {
      return order.full_delivery_address
    }

    if (order.use_client_address && order.client?.address) {
      const parts = [
        order.client?.address,
        order.client?.number,
        order.client?.complement,
        order.client?.neighborhood,
        order.client?.city,
        order.client?.state,
        order.client?.zip_code
      ].filter(Boolean)
      return parts.join(", ")
    }

    if (order.is_delivery && order.delivery_address) {
      const parts = [
        order.delivery_address,
        order.delivery_number,
        order.delivery_complement,
        order.delivery_neighborhood,
        order.delivery_city,
        order.delivery_state,
        order.delivery_zip_code
      ].filter(Boolean)
      return parts.join(", ")
    }

    return "Endereço não informado"
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden">
        <DialogHeader>
          <div className="flex items-center justify-between">
            <DialogTitle className="text-xl font-semibold">
              Detalhes do Pedido #{order.identify || order.orderNumber}
            </DialogTitle>
            <Badge className={getStatusColor(order.status)}>
              {order.status}
            </Badge>
          </div>
        </DialogHeader>

        <ScrollArea className="max-h-[calc(90vh-120px)] pr-4">
          <div className="space-y-6">
            {/* Informações do Cliente */}
            {order.client && (
              <div className="space-y-4">
                <div className="flex items-center gap-2">
                  <User className="h-5 w-5 text-muted-foreground" />
                  <h3 className="text-lg font-medium">Informações do Cliente</h3>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pl-7">
                  <div>
                    <p className="text-sm text-muted-foreground">Nome</p>
                    <p className="font-medium">{order.client?.name || 'N/A'}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Email</p>
                    <div className="flex items-center gap-2">
                      <Mail className="h-4 w-4 text-muted-foreground" />
                      <p className="font-medium">{order.client?.email || 'N/A'}</p>
                    </div>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Telefone</p>
                    <div className="flex items-center gap-2">
                      <Phone className="h-4 w-4 text-muted-foreground" />
                      <p className="font-medium">{order.client?.phone || 'N/A'}</p>
                    </div>
                  </div>
                  {order.client?.address && (
                    <div>
                      <p className="text-sm text-muted-foreground">Endereço</p>
                      <div className="flex items-center gap-2">
                        <Home className="h-4 w-4 text-muted-foreground" />
                        <p className="font-medium">{order.client?.address || 'N/A'}</p>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}

            {order.client && <Separator />}

            {/* Informações do Pedido */}
            <div className="space-y-4">
              <div className="flex items-center gap-2">
                <FileText className="h-5 w-5 text-muted-foreground" />
                <h3 className="text-lg font-medium">Informações do Pedido</h3>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pl-7">
                <div>
                  <p className="text-sm text-muted-foreground">Data do Pedido</p>
                  <div className="flex items-center gap-2">
                    <Calendar className="h-4 w-4 text-muted-foreground" />
                    <p className="font-medium">{formatDate(order.date || order.orderDate || '')}</p>
                  </div>
                </div>
                {order.deliveryDate && (
                  <div>
                    <p className="text-sm text-muted-foreground">Data de Entrega</p>
                    <div className="flex items-center gap-2">
                      <Clock className="h-4 w-4 text-muted-foreground" />
                      <p className="font-medium">{formatDate(order.deliveryDate)}</p>
                    </div>
                  </div>
                )}
                <div>
                  <p className="text-sm text-muted-foreground">Tipo</p>
                  <div className="flex items-center gap-2">
                    {order.is_delivery ? (
                      <Truck className="h-4 w-4 text-muted-foreground" />
                    ) : (
                      <Home className="h-4 w-4 text-muted-foreground" />
                    )}
                    <p className="font-medium">
                      {order.is_delivery ? "Delivery" : "Balcão"}
                    </p>
                  </div>
                </div>
                {order.table && (
                  <div>
                    <p className="text-sm text-muted-foreground">Mesa</p>
                    <p className="font-medium">
                      {order.table.name} (Capacidade: {order.table.capacity})
                    </p>
                  </div>
                )}
              </div>
            </div>

            {/* Agendamento */}
            {order.is_scheduled && order.scheduled_at && (
              <>
                <Separator />
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <CalendarPlus className="h-5 w-5 text-primary" />
                      <h3 className="text-lg font-medium">Pedido Agendado</h3>
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={handleDownloadCalendar}
                      disabled={downloadingCalendar}
                      className="flex items-center gap-2"
                    >
                      <CalendarPlus className="h-4 w-4" />
                      {downloadingCalendar ? "Gerando..." : "Adicionar à Agenda"}
                    </Button>
                  </div>
                  <div className="pl-7">
                    <p className="text-sm text-muted-foreground">Data e hora agendada</p>
                    <p className="font-medium text-primary">
                      {new Date(order.scheduled_at).toLocaleString("pt-BR", {
                        dateStyle: "full",
                        timeStyle: "short",
                      })}
                    </p>
                  </div>
                </div>
              </>
            )}

            {/* Endereço de Entrega */}
            {order.is_delivery && (
              <>
                <Separator />
                <div className="space-y-4">
                  <div className="flex items-center gap-2">
                    <MapPin className="h-5 w-5 text-muted-foreground" />
                    <h3 className="text-lg font-medium">Endereço de Entrega</h3>
                  </div>
                  <div className="pl-7">
                    <p className="font-medium">{getFullDeliveryAddress()}</p>
                    {order.delivery_notes && (
                      <div className="mt-2">
                        <p className="text-sm text-muted-foreground">Observações:</p>
                        <p className="text-sm">{order.delivery_notes}</p>
                      </div>
                    )}
                  </div>
                </div>
              </>
            )}

            <Separator />

            {/* Itens do Pedido */}
            <div className="space-y-4">
              <div className="flex items-center gap-2">
                <Package className="h-5 w-5 text-muted-foreground" />
                <h3 className="text-lg font-medium">Itens do Pedido</h3>
              </div>
              <div className="pl-7">
                <div className="space-y-3">
                  {(order.products || order.items || []).map((item, index) => (
                    <div key={item.id || `item-${index}`} className="flex justify-between items-center p-3 border rounded-lg">
                      <div className="flex-1">
                        <p className="font-medium">{item.name}</p>
                        <p className="text-sm text-muted-foreground">
                          {item.quantity || 1}x {formatCurrency(item.price)}
                        </p>
                      </div>
                      <p className="font-medium">{formatCurrency(item.total || (item.price * (item.quantity || 1)))}</p>
                    </div>
                  ))}
                </div>
                <Separator className="my-4" />
                <div className="flex justify-between items-center text-lg font-semibold">
                  <span>Total:</span>
                  <span>{formatCurrency(order.total)}</span>
                </div>
              </div>
            </div>

            {/* Observações */}
            {order.comment && (
              <>
                <Separator />
                <div className="space-y-4">
                  <div className="flex items-center gap-2">
                    <FileText className="h-5 w-5 text-muted-foreground" />
                    <h3 className="text-lg font-medium">Observações</h3>
                  </div>
                  <div className="pl-7">
                    <p className="text-sm bg-muted p-3 rounded-lg">{order.comment}</p>
                  </div>
                </div>
              </>
            )}
          </div>
        </ScrollArea>

        <div className="flex justify-end gap-2 pt-4 border-t">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Fechar
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}

