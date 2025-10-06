"use client"

import React, { useState, useEffect, useMemo, useCallback } from "react"
import { 
  DndContext, 
  DragEndEvent, 
  DragOverlay,
  DragStartEvent,
  useDroppable, 
  useDraggable, 
  closestCorners, 
  PointerSensor, 
  useSensor, 
  useSensors 
} from "@dnd-kit/core"
import { CSS } from "@dnd-kit/utilities"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { PageLoading } from "@/components/ui/loading-progress"
import { apiClient, endpoints } from "@/lib/api-client"
import { toast } from "sonner"
import { useRealtimeOrders } from "@/hooks/use-realtime"
import { Wifi, WifiOff, RefreshCw } from "lucide-react"
import { useAuth } from "@/contexts/auth-context"

interface Product {
  identify?: string
  name: string
  price: string | number
  quantity?: number
}

interface Client {
  id: number
  name: string
  email?: string
  phone?: string
}

interface Table {
  id: number
  identify?: string
  name: string
  capacity?: string | number
}

interface Order {
  identify: string
  total: string | number
  client?: Client
  client_full_name?: string
  client_email?: string
  client_phone?: string
  table?: Table
  status: string
  date: string
  created_at: string
  products: Product[]
  is_delivery?: boolean
  full_delivery_address?: string
  delivery_address?: string
  delivery_city?: string
  delivery_state?: string
  delivery_zip_code?: string
  delivery_neighborhood?: string
  delivery_number?: string
  delivery_complement?: string
  delivery_notes?: string
  comment?: string
}

type OrderStatus = "Em Preparo" | "Pronto" | "Entregue" | "Cancelado"

const COLUMNS: Array<{ id: OrderStatus; title: string; color: string }> = [
  { id: "Em Preparo", title: "Em Preparo", color: "bg-yellow-100 text-yellow-800" },
  { id: "Pronto", title: "Pronto", color: "bg-blue-100 text-blue-800" },
  { id: "Entregue", title: "Entregue", color: "bg-green-100 text-green-800" },
  { id: "Cancelado", title: "Cancelado", color: "bg-red-100 text-red-800" },
]

interface OrderCardProps {
  order: Order
  isDragOverlay?: boolean
}

function OrderCard({ order, isDragOverlay = false }: OrderCardProps) {
  const { 
    setNodeRef, 
    attributes, 
    listeners, 
    transform, 
    isDragging 
  } = useDraggable({ 
    id: `order-${order.identify}`,
    data: { order }
  })
  
  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    opacity: isDragging ? 0.5 : 1,
    cursor: isDragOverlay ? 'grabbing' : 'grab',
  }
  
  const deliveryAddress = order.is_delivery && (
    order.full_delivery_address || 
    (order.delivery_address && `${order.delivery_address}${order.delivery_number ? ', ' + order.delivery_number : ''} - ${order.delivery_neighborhood || ''}, ${order.delivery_city || ''} - ${order.delivery_state || ''}`)
  )

  const customerName = order.client?.name || order.client_full_name
  const total = typeof order.total === 'string' ? parseFloat(order.total) : order.total
  
  return (
    <div
      ref={setNodeRef}
      style={style}
      className={`rounded-md border bg-card p-3 text-sm shadow-sm hover:shadow-md transition-all ${
        isDragging ? "shadow-lg border-primary ring-2 ring-primary ring-offset-2" : ""
      } ${isDragOverlay ? "shadow-2xl" : ""}`}
      {...attributes}
      {...listeners}
    >
      <div className="flex items-center justify-between mb-2">
        <span className="font-medium text-base">#{order.identify}</span>
        <Badge variant="secondary" className="text-xs">{order.status}</Badge>
      </div>
      
      <div className="space-y-2">
        {customerName && (
          <div className="flex items-center gap-1 text-muted-foreground">
            <span className="text-xs">👤</span>
            <span className="text-xs truncate">{customerName}</span>
          </div>
        )}

        {order.table && (
          <div className="flex items-center gap-1 text-muted-foreground">
            <span className="text-xs">🪑</span>
            <span className="text-xs truncate">{order.table.name}</span>
          </div>
        )}
        
        {deliveryAddress && (
          <div className="space-y-1">
            <div className="flex items-start gap-1 text-muted-foreground">
              <span className="text-xs shrink-0">🚚</span>
              <span className="text-xs line-clamp-2">{deliveryAddress}</span>
            </div>
            {order.delivery_notes && (
              <div className="text-xs text-muted-foreground italic ml-4">
                Obs: {order.delivery_notes}
              </div>
            )}
          </div>
        )}
        
        {order.products && order.products.length > 0 && (
          <div className="space-y-1">
            <div className="text-xs font-medium text-muted-foreground">Produtos:</div>
            <div className="space-y-0.5">
              {order.products.slice(0, 3).map((product, idx) => (
                <div key={product.identify || idx} className="text-xs flex items-start gap-1">
                  <span className="text-muted-foreground shrink-0">
                    {product.quantity ? `${product.quantity}x` : '1x'}
                  </span>
                  <span className="truncate">{product.name}</span>
                </div>
              ))}
              {order.products.length > 3 && (
                <div className="text-xs text-muted-foreground">
                  +{order.products.length - 3} item(s)...
                </div>
              )}
            </div>
          </div>
        )}
        
        <div className="flex items-center justify-between pt-1 border-t">
          <span className="text-xs text-muted-foreground">Total:</span>
          <span className="text-sm font-semibold">
            R$ {total.toFixed(2)}
          </span>
        </div>
      </div>
    </div>
  )
}

interface DroppableColumnAreaProps {
  columnId: OrderStatus
  children: React.ReactNode
}

function DroppableColumnArea({ columnId, children }: DroppableColumnAreaProps) {
  const { setNodeRef, isOver } = useDroppable({ 
    id: `column-${columnId}`, 
    data: { column: columnId } 
  })
  
  return (
    <div 
      ref={setNodeRef} 
      className={`flex flex-col gap-2 min-h-[200px] rounded-md p-2 transition-colors ${
        isOver ? 'bg-accent/50 border-2 border-dashed border-primary' : ''
      }`}
    >
      {children}
    </div>
  )
}

interface BoardColumnProps {
  column: { id: OrderStatus; title: string; color: string }
  orders: Order[]
  isUpdating: boolean
}

function BoardColumn({ column, orders, isUpdating }: BoardColumnProps) {
  return (
    <Card className="border">
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-base font-medium flex items-center gap-2">
          {column.title}
          <Badge className={column.color}>{orders.length}</Badge>
        </CardTitle>
      </CardHeader>
      <CardContent className="p-2">
        <DroppableColumnArea columnId={column.id}>
          {orders.map((order) => (
            <OrderCard key={order.identify} order={order} />
          ))}
          {isUpdating && (
            <div className="text-xs text-muted-foreground text-center py-2">
              Atualizando...
            </div>
          )}
        </DroppableColumnArea>
      </CardContent>
    </Card>
  )
}

export default function OrdersBoardPage() {
  const { user } = useAuth()
  const [loading, setLoading] = useState(true)
  const [orders, setOrders] = useState<Order[]>([])
  const [updatingIdentify, setUpdatingIdentify] = useState<string | null>(null)
  const [activeOrder, setActiveOrder] = useState<Order | null>(null)

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    })
  )

  const tenantId = user?.tenant_id ? parseInt(user.tenant_id, 10) : 0

  const normalizeOrder = useCallback((rawOrder: any): Order => {
    const total = typeof rawOrder.total === 'string' 
      ? parseFloat(rawOrder.total) 
      : (rawOrder.total || 0)

    return {
      identify: rawOrder.identify || String(rawOrder.id),
      total,
      client: rawOrder.client,
      client_full_name: rawOrder.client?.name || rawOrder.client_full_name,
      client_email: rawOrder.client?.email || rawOrder.client_email,
      client_phone: rawOrder.client?.phone || rawOrder.client_phone,
      table: rawOrder.table,
      status: rawOrder.status || "Em Preparo",
      date: rawOrder.date || rawOrder.created_at,
      created_at: rawOrder.created_at,
      products: Array.isArray(rawOrder.products) 
        ? rawOrder.products.map((p: any) => ({
            identify: p.identify,
            name: p.name || 'Produto',
            price: p.price || '0.00',
            quantity: p.quantity || 1,
          }))
        : [],
      is_delivery: rawOrder.is_delivery || false,
      full_delivery_address: rawOrder.full_delivery_address,
      delivery_address: rawOrder.delivery_address,
      delivery_city: rawOrder.delivery_city,
      delivery_state: rawOrder.delivery_state,
      delivery_zip_code: rawOrder.delivery_zip_code,
      delivery_neighborhood: rawOrder.delivery_neighborhood,
      delivery_number: rawOrder.delivery_number,
      delivery_complement: rawOrder.delivery_complement,
      delivery_notes: rawOrder.delivery_notes,
      comment: rawOrder.comment,
    }
  }, [])

  const { isConnected } = useRealtimeOrders({
    tenantId,
    enabled: !!user?.tenant_id,
    onOrderCreated: useCallback((newOrder: any) => {
      console.log('Real-time: New order created', newOrder)
      const normalized = normalizeOrder(newOrder)
      
      setOrders((prev) => {
        if (prev.some(o => o.identify === normalized.identify)) {
          return prev
        }
        return [normalized, ...prev]
      })
      
      toast.success(`Novo pedido #${normalized.identify} criado!`)
    }, [normalizeOrder]),
    
    onOrderStatusUpdated: useCallback(({ order: updatedOrder, oldStatus, newStatus }: any) => {
      console.log('Real-time: Order status updated', { updatedOrder, oldStatus, newStatus })
      
      setOrders((prev) =>
        prev.map((o) =>
          o.identify === updatedOrder.identify
            ? { ...normalizeOrder(updatedOrder), status: newStatus }
            : o
        )
      )
      
      toast.info(`Pedido #${updatedOrder.identify} mudou de "${oldStatus}" para "${newStatus}"`)
    }, [normalizeOrder]),
    
    onOrderUpdated: useCallback((updatedOrder: any) => {
      console.log('Real-time: Order updated', updatedOrder)
      
      setOrders((prev) =>
        prev.map((o) =>
          o.identify === updatedOrder.identify
            ? normalizeOrder(updatedOrder)
            : o
        )
      )
    }, [normalizeOrder]),
  })

  const loadOrders = useCallback(async () => {
    try {
      setLoading(true)
      const res = await apiClient.get<any>(endpoints.orders.list)
      
      const raw = Array.isArray(res.data)
        ? res.data
        : (res.data?.orders || res.data?.data || [])
      
      const normalized: Order[] = raw.map((o: any) => normalizeOrder(o))
      setOrders(normalized)
    } catch (e: any) {
      toast.error(e?.message || "Erro ao carregar pedidos")
    } finally {
      setLoading(false)
    }
  }, [normalizeOrder])

  useEffect(() => {
    loadOrders()
  }, [loadOrders])

  const groupedOrders = useMemo(() => {
    const map: Record<OrderStatus, Order[]> = { 
      "Em Preparo": [], 
      "Pronto": [], 
      "Entregue": [], 
      "Cancelado": [] 
    }
    
    for (const order of orders) {
      const status = COLUMNS.find((c) => c.id === order.status)?.id || "Em Preparo"
      map[status].push(order)
    }
    
    return map
  }, [orders])

  const updateOrderStatus = async (orderIdentify: string, newStatus: OrderStatus) => {
    const order = orders.find((o) => o.identify === orderIdentify)
    if (!order) return
    
    const columnInfo = COLUMNS.find((c) => c.id === newStatus)
    
    try {
      setUpdatingIdentify(orderIdentify)
      
      await apiClient.put(endpoints.orders.update(orderIdentify), { status: newStatus })
      
      setOrders((prev) => 
        prev.map((o) => 
          o.identify === orderIdentify ? { ...o, status: newStatus } : o
        )
      )
      
      toast.success(`Pedido #${orderIdentify} movido para ${columnInfo?.title}`)
    } catch (e: any) {
      toast.error(e?.message || "Não foi possível atualizar o status")
      await loadOrders()
    } finally {
      setUpdatingIdentify(null)
    }
  }

  const handleDragStart = (event: DragStartEvent) => {
    const { active } = event
    const orderIdentify = String(active.id).replace('order-', '')
    const order = orders.find((o) => o.identify === orderIdentify)
    
    if (order) {
      setActiveOrder(order)
    }
  }

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event
    setActiveOrder(null)
    
    if (!over) return

    const orderIdentify = String(active.id).replace('order-', '')
    const currentOrder = orders.find((o) => o.identify === orderIdentify)
    
    if (!currentOrder) return

    const overData: any = over.data?.current
    let newStatus: OrderStatus | null = null
    
    if (overData?.column) {
      newStatus = overData.column as OrderStatus
    } else {
      const targetOrderIdentify = String(over.id).replace('order-', '')
      const targetOrder = orders.find((o) => o.identify === targetOrderIdentify)
      if (targetOrder) {
        newStatus = targetOrder.status as OrderStatus
      }
    }

    if (!newStatus || !COLUMNS.find((c) => c.id === newStatus)) {
      return
    }

    if (currentOrder.status === newStatus) {
      return
    }

    updateOrderStatus(orderIdentify, newStatus)
  }

  if (loading) {
    return <PageLoading />
  }

  return (
    <div className="flex flex-col gap-6 p-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Quadro de Pedidos</h1>
          <p className="text-muted-foreground">Arraste os pedidos entre para atualizar</p>
        </div>
        <div className="flex items-center gap-2">
          <Badge 
            variant={isConnected ? "default" : "secondary"} 
            className="flex items-center gap-1.5 px-3"
          >
            {isConnected ? (
              <Wifi className="h-3.5 w-3.5" />
            ) : (
              <WifiOff className="h-3.5 w-3.5" />
            )}
            <span>{isConnected ? "Online" : "Offline"}</span>
          </Badge>
          <Button 
            variant="outline" 
            size="sm"
            onClick={loadOrders} 
            disabled={loading}
            className="gap-2"
          >
            <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
            Atualizar
          </Button>
        </div>
      </div>

      <DndContext 
        onDragStart={handleDragStart}
        onDragEnd={handleDragEnd} 
        collisionDetection={closestCorners} 
        sensors={sensors}
      >
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {COLUMNS.map((column) => (
            <BoardColumn 
              key={column.id} 
              column={column} 
              orders={groupedOrders[column.id] || []}
              isUpdating={groupedOrders[column.id]?.some(o => o.identify === updatingIdentify) || false}
            />
          ))}
        </div>
        
        <DragOverlay>
          {activeOrder ? <OrderCard order={activeOrder} isDragOverlay /> : null}
        </DragOverlay>
      </DndContext>
    </div>
  )
}


