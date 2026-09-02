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
import { ScrollArea } from "@/components/ui/scroll-area"
import { apiClient, endpoints } from "@/lib/api-client"
import { toast } from "sonner"
import { useRealtimeOrders } from "@/hooks/use-realtime"
import { 
  Wifi, 
  WifiOff, 
  RefreshCw, 
  Clock, 
  User, 
  Package, 
  MapPin,
  Truck,
  UtensilsCrossed,
  Archive,
  ChevronLeft,
  ChevronRight
} from "lucide-react"
import { useAuth } from "@/contexts/auth-context"
import { cn } from "@/lib/utils"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"

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

type OrderStatus = "Pendente" | "Aceito" | "Preparo" | "Concluído" | "Cancelado"

// ponytail: Tailwind não gera classes de cor a partir de valores dinâmicos
// (ex.: `bg-[${status.color}]`), então a cor de cada status é aplicada via
// inline style a partir de um hex, e não de classes utilitárias.
function hexToRgba(hex: string, alpha: number): string {
  const normalized = hex?.replace('#', '') || '3b82f6'
  const full = normalized.length === 3
    ? normalized.split('').map((c) => c + c).join('')
    : normalized.padEnd(6, '0').slice(0, 6)
  const r = parseInt(full.slice(0, 2), 16)
  const g = parseInt(full.slice(2, 4), 16)
  const b = parseInt(full.slice(4, 6), 16)
  return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

const COLUMNS: Array<{
  id: OrderStatus
  title: string
  color: string
  icon: React.ReactNode
}> = [
  { id: "Pendente", title: "Pendente", color: "#f59e0b", icon: <Clock className="h-4 w-4" /> },
  { id: "Aceito", title: "Aceito", color: "#6366f1", icon: <Package className="h-4 w-4" /> },
  { id: "Preparo", title: "Preparo", color: "#3b82f6", icon: <Clock className="h-4 w-4" /> },
  { id: "Concluído", title: "Concluído", color: "#10b981", icon: <Package className="h-4 w-4" /> },
  { id: "Cancelado", title: "Cancelado", color: "#f43f5e", icon: <RefreshCw className="h-4 w-4" /> },
]

interface OrderCardProps {
  order: Order
  isDragOverlay?: boolean
  onArchive: (order: Order) => void
  onMoveLeft?: (order: Order) => void
  onMoveRight?: (order: Order) => void
  canMoveLeft?: boolean
  canMoveRight?: boolean
  columns?: Array<{ id: OrderStatus; title: string; color: string; icon?: React.ReactNode }>
}

function OrderCard({ 
  order, 
  isDragOverlay = false, 
  onArchive,
  onMoveLeft,
  onMoveRight,
  canMoveLeft = false,
  canMoveRight = false,
  columns = []
}: OrderCardProps) {
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
    opacity: isDragging ? 0.4 : 1,
    cursor: isDragOverlay ? 'grabbing' : 'grab',
  }
  
  const deliveryAddress = order.is_delivery && (
    order.full_delivery_address || 
    (order.delivery_address && `${order.delivery_address}${order.delivery_number ? ', ' + order.delivery_number : ''} - ${order.delivery_neighborhood || ''}, ${order.delivery_city || ''} - ${order.delivery_state || ''}`)
  )

  const customerName = order.client?.name || order.client_full_name
  const total = typeof order.total === 'string' ? parseFloat(order.total) : order.total
  
  const columnInfo = columns.find(col => col.id === order.status) || COLUMNS.find(col => col.id === order.status)
  
  return (
    <div
      ref={setNodeRef}
      style={style}
      className={cn(
        "group relative rounded-lg border bg-card transition-all duration-200",
        "hover:shadow-lg",
        isDragging && "shadow-2xl border-primary ring-2 ring-primary/50 ring-offset-2",
        isDragOverlay && "shadow-2xl rotate-2",
        "cursor-grab active:cursor-grabbing",
        "w-full min-w-0 max-w-full overflow-hidden"
      )}
      {...attributes}
      {...listeners}
    >
      {/* Barra de cor superior */}
      <div
        className="h-1 rounded-t-lg"
        style={{ backgroundColor: columnInfo?.color || "#6b7280" }}
      />

      <div className="p-3 space-y-2.5 min-w-0">
        {/* Header */}
        <div className="flex items-center justify-between gap-2 min-w-0">
          <div className="flex items-center gap-1.5 min-w-0 flex-1">
            {columnInfo?.icon && (
              <span className="shrink-0" style={{ color: columnInfo.color }}>
                {columnInfo.icon}
              </span>
            )}
            <span className="font-semibold text-sm tracking-tight truncate">#{order.identify}</span>
          </div>
          <div className="flex items-center gap-1.5 shrink-0">
            <Badge
              variant="outline"
              className="text-[10px] font-medium border px-1.5 py-0.5"
              style={{
                backgroundColor: hexToRgba(columnInfo?.color || "#6b7280", 0.1),
                color: columnInfo?.color || "#6b7280",
                borderColor: hexToRgba(columnInfo?.color || "#6b7280", 0.3),
              }}
            >
              <span className="truncate max-w-[80px] block">{order.status}</span>
            </Badge>
            <Button
              variant="ghost"
              size="icon"
              title="Arquivar pedido"
              aria-label={`Arquivar pedido ${order.identify}`}
              className="h-7 w-7 text-muted-foreground hover:text-foreground shrink-0"
              disabled={isDragOverlay}
              onClick={(event) => {
                event.stopPropagation()
                onArchive(order)
              }}
              onMouseDown={(event) => event.stopPropagation()}
              onPointerDown={(event) => event.stopPropagation()}
            >
              <Archive className="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>
        
        {/* Botões de navegação para touch - Mover entre colunas */}
        {!isDragOverlay && (canMoveLeft || canMoveRight) && (
          <div className="flex items-stretch gap-1.5 py-1.5 border-y min-w-0">
            {canMoveLeft && onMoveLeft && (
              <Button
                variant="outline"
                size="sm"
                onClick={(event) => {
                  event.stopPropagation()
                  onMoveLeft(order)
                }}
                onMouseDown={(event) => event.stopPropagation()}
                onPointerDown={(event) => event.stopPropagation()}
                className="h-8 min-w-0 flex-1 shrink gap-0.5 overflow-hidden px-1.5 text-xs"
                title="Mover para coluna anterior"
              >
                <ChevronLeft className="size-3.5 shrink-0" />
                <span className="truncate">Anterior</span>
              </Button>
            )}
            {canMoveRight && onMoveRight && (
              <Button
                variant="outline"
                size="sm"
                onClick={(event) => {
                  event.stopPropagation()
                  onMoveRight(order)
                }}
                onMouseDown={(event) => event.stopPropagation()}
                onPointerDown={(event) => event.stopPropagation()}
                className="h-8 min-w-0 flex-1 shrink gap-0.5 overflow-hidden px-1.5 text-xs"
                title="Mover para próxima coluna"
              >
                <span className="truncate">Próxima</span>
                <ChevronRight className="size-3.5 shrink-0" />
              </Button>
            )}
          </div>
        )}
        
        {/* Info Section */}
        <div className="space-y-2">
          {customerName && (
            <div className="flex items-center gap-1.5 text-xs min-w-0">
              <User className="h-3 w-3 text-muted-foreground shrink-0" />
              <span className="truncate font-medium">{customerName}</span>
            </div>
          )}

          {order.table && (
            <div className="flex items-center gap-1.5 text-xs min-w-0">
              <UtensilsCrossed className="h-3 w-3 text-muted-foreground shrink-0" />
              <span className="truncate">{order.table.name}</span>
            </div>
          )}
          
          {deliveryAddress && (
            <div className="space-y-1">
              <div className="flex items-start gap-1.5 text-xs min-w-0">
                <MapPin className="h-3 w-3 text-muted-foreground shrink-0 mt-0.5" />
                <span className="min-w-0 flex-1 break-words leading-relaxed">{deliveryAddress}</span>
              </div>
              {order.delivery_notes && (
                <div className="text-[10px] text-muted-foreground italic pl-4 line-clamp-1">
                  "{order.delivery_notes}"
                </div>
              )}
            </div>
          )}
          
          {order.products && order.products.length > 0 && (
            <div className="space-y-1 pt-0.5">
              <div className="flex items-center gap-1.5 text-[10px] font-semibold text-muted-foreground uppercase tracking-wide">
                <Package className="h-2.5 w-2.5" />
                Produtos ({order.products.length})
              </div>
              <div className="space-y-0.5 pl-4">
                {order.products.slice(0, 2).map((product, idx) => (
                  <div key={product.identify || idx} className="flex items-start gap-1.5 text-[10px] min-w-0">
                    <Badge variant="secondary" className="h-4 px-1 text-[9px] font-medium shrink-0">
                      {product.quantity || 1}x
                    </Badge>
                    <span className="min-w-0 flex-1 break-words leading-4">{product.name}</span>
                  </div>
                ))}
                {order.products.length > 2 && (
                  <div className="text-[10px] text-muted-foreground font-medium">
                    +{order.products.length - 2} item(s)
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
        
        {/* Footer - Total */}
        <div className="flex items-center justify-between gap-2 pt-2 border-t min-w-0">
          <span className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wide shrink-0">Total</span>
          <span className="text-sm font-bold tabular-nums text-primary truncate">
            R$ {Number.isFinite(total) ? total.toFixed(2) : "0.00"}
          </span>
        </div>
      </div>
      
      {/* Indicador de drag */}
      <div className="absolute inset-0 rounded-lg bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
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
      className={cn(
        "flex flex-col gap-2 min-h-[400px] p-2 rounded-lg transition-all duration-200",
        isOver && "bg-primary/5 border-2 border-dashed border-primary ring-2 ring-primary/20"
      )}
    >
      {children}
    </div>
  )
}

interface BoardColumnProps {
  column: {
    id: OrderStatus
    title: string
    color: string
    icon: React.ReactNode
  }
  orders: Order[]
  isUpdating: boolean
  onArchive: (order: Order) => void
  onMoveOrder?: (order: Order, newStatus: OrderStatus) => void
  allColumns?: Array<{ id: OrderStatus; title: string; color: string }>
}

function BoardColumn({ column, orders, isUpdating, onArchive, onMoveOrder, allColumns = [] }: BoardColumnProps) {
  // Encontrar índice da coluna atual
  const currentIndex = allColumns.findIndex((c) => c.id === column.id)
  const prevColumn = currentIndex > 0 ? allColumns[currentIndex - 1] : null
  const nextColumn = currentIndex < allColumns.length - 1 ? allColumns[currentIndex + 1] : null

  const handleMoveLeft = (order: Order) => {
    if (prevColumn && onMoveOrder) {
      onMoveOrder(order, prevColumn.id)
    }
  }

  const handleMoveRight = (order: Order) => {
    if (nextColumn && onMoveOrder) {
      onMoveOrder(order, nextColumn.id)
    }
  }

  return (
    <Card
      className={cn(
        "border-2 shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col h-full",
        "w-full min-w-0 overflow-hidden py-0 gap-0 max-xl:min-w-[260px]"
      )}
    >
      <CardHeader
        className="flex flex-row items-center justify-between space-y-0 px-3 py-3 min-w-0 rounded-t-lg"
        style={{
          backgroundImage: `linear-gradient(to bottom right, ${hexToRgba(column.color, 0.12)}, ${hexToRgba(column.color, 0.06)})`,
        }}
      >
        <CardTitle className="flex items-center gap-2.5">
          <span style={{ color: column.color }}>{column.icon}</span>
          <span className="text-lg font-bold tracking-tight">{column.title}</span>
        </CardTitle>
        <Badge
          variant="outline"
          className="text-sm font-bold px-2.5 py-1 border-2"
          style={{
            backgroundColor: hexToRgba(column.color, 0.1),
            color: column.color,
            borderColor: hexToRgba(column.color, 0.3),
          }}
        >
          {orders.length}
        </Badge>
      </CardHeader>
      <CardContent className="p-0 flex-1 flex flex-col min-w-0 overflow-hidden">
        <ScrollArea className="flex-1 min-w-0">
          <DroppableColumnArea columnId={column.id}>
            {orders.length === 0 && !isUpdating && (
              <div className="flex flex-col items-center justify-center py-12 text-center space-y-2">
                <div className="h-12 w-12 rounded-full bg-muted/50 flex items-center justify-center" style={{ color: column.color }}>
                  {column.icon}
                </div>
                <p className="text-sm text-muted-foreground font-medium">
                  Nenhum pedido
                </p>
              </div>
            )}
            
            {orders.map((order) => (
              <OrderCard 
                key={order.identify} 
                order={order} 
                onArchive={onArchive}
                onMoveLeft={handleMoveLeft}
                onMoveRight={handleMoveRight}
                canMoveLeft={!!prevColumn}
                canMoveRight={!!nextColumn}
                columns={allColumns}
              />
            ))}
            
            {isUpdating && (
              <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground py-4">
                <RefreshCw className="h-4 w-4 animate-spin" />
                <span className="font-medium">Atualizando...</span>
              </div>
            )}
          </DroppableColumnArea>
        </ScrollArea>
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
  const [dynamicColumns, setDynamicColumns] = useState(COLUMNS)
  const [orderToArchive, setOrderToArchive] = useState<Order | null>(null)
  const [archiving, setArchiving] = useState(false)

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    })
  )

  const tenantId = user?.tenant_id ? parseInt(user.tenant_id, 10) : 0

  const openArchiveDialog = useCallback((order: Order) => {
    setOrderToArchive(order)
  }, [])

  const cancelArchiveDialog = useCallback(() => {
    if (!archiving) {
      setOrderToArchive(null)
    }
  }, [archiving])

  const confirmArchiveOrder = useCallback(async () => {
    if (!orderToArchive) return

    try {
      setArchiving(true)
      await apiClient.patch(endpoints.orders.archive(orderToArchive.identify))
      toast.success(`Pedido #${orderToArchive.identify} arquivado com sucesso!`)
      setOrders((prev) => prev.filter((order) => order.identify !== orderToArchive.identify))
      setOrderToArchive(null)
    } catch (error: any) {
      toast.error(error?.message || 'Erro ao arquivar pedido')
    } finally {
      setArchiving(false)
    }
  }, [orderToArchive])

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
      status: rawOrder.status || "Preparo",
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

      const normalized = normalizeOrder(newOrder)

      if (normalized.status === 'Arquivado') {
        return
      }

      setOrders((prev) => {
        if (prev.some(o => o.identify === normalized.identify)) {
          return prev
        }
        return [normalized, ...prev]
      })

      toast.success(`Novo pedido #${normalized.identify} criado!`)
    }, [normalizeOrder]),
    
    onOrderStatusUpdated: useCallback(({ order: updatedOrder, oldStatus, newStatus }: any) => {

      const normalized = normalizeOrder(updatedOrder)

      if (newStatus === 'Arquivado' || normalized.status === 'Arquivado') {
        setOrders((prev) => prev.filter((o) => o.identify !== updatedOrder.identify))
        toast.info(`Pedido #${updatedOrder.identify} foi arquivado.`)
        return
      }

      setOrders((prev) =>
        prev.map((o) =>
          o.identify === updatedOrder.identify
            ? { ...normalized, status: newStatus }
            : o
        )
      )

      toast.info(`Pedido #${updatedOrder.identify} mudou de "${oldStatus}" para "${newStatus}"`)
    }, [normalizeOrder]),
    
    onOrderUpdated: useCallback((updatedOrder: any) => {

      setOrders((prev) => {
        const normalized = normalizeOrder(updatedOrder)

        if (normalized.status === 'Arquivado') {
          return prev.filter((o) => o.identify !== normalized.identify)
        }

        return prev.map((o) =>
          o.identify === updatedOrder.identify
            ? normalized
            : o
        )
      })
    }, [normalizeOrder]),
  })

  const loadStatuses = useCallback(async () => {
    try {
      const res = await apiClient.get<any>(endpoints.orderStatuses.list(true))
      
      if (res.success && res.data && Array.isArray(res.data)) {
        // Mapear status da API para formato das colunas
        const iconMap: Record<string, React.ReactNode> = {
          'clock': <Clock className="h-4 w-4" />,
          'package': <Package className="h-4 w-4" />,
          'check-circle': <Package className="h-4 w-4" />,
          'check-circle-2': <Package className="h-4 w-4" />,
          'truck': <Truck className="h-4 w-4" />,
          'x-circle': <RefreshCw className="h-4 w-4" />,
        }

        const columns = res.data.map((status: any) => ({
          id: status.name,
          title: status.name,
          color: status.color || "#6b7280",
          icon: iconMap[status.icon] || <Package className="h-4 w-4" />,
        }))

        setDynamicColumns(columns)
      }
    } catch (e: any) {

      // Manter colunas padrão em caso de erro
    }
  }, [])

  const loadOrders = useCallback(async () => {
    try {
      setLoading(true)
      const res = await apiClient.get<any>(endpoints.orders.list)
      
      const raw = Array.isArray(res.data)
        ? res.data
        : (res.data?.orders || res.data?.data || [])
      
      const normalized: Order[] = raw
        .map((o: any) => normalizeOrder(o))
        .filter((order: Order) => order.status !== 'Arquivado')

      setOrders(normalized)
    } catch (e: any) {
      toast.error(e?.message || "Erro ao carregar pedidos")
    } finally {
      setLoading(false)
    }
  }, [normalizeOrder])

  useEffect(() => {
    loadStatuses()
    loadOrders()
  }, [loadOrders, loadStatuses])

  const groupedOrders = useMemo(() => {
    // Criar map dinâmico baseado nas colunas carregadas
    const map: Record<string, Order[]> = {}
    
    dynamicColumns.forEach((col) => {
      map[col.id] = []
    })
    
    for (const order of orders) {
      const status = dynamicColumns.find((c) => c.id === order.status)?.id || dynamicColumns[0]?.id || "Preparo"
      if (map[status]) {
        map[status].push(order)
      }
    }
    
    return map as Record<OrderStatus, Order[]>
  }, [orders, dynamicColumns])

  const updateOrderStatus = async (orderOrIdentify: Order | string, newStatus: OrderStatus) => {
    // Aceitar tanto Order quanto string (identify)
    const orderIdentify = typeof orderOrIdentify === 'string' 
      ? orderOrIdentify 
      : orderOrIdentify.identify
    const order = typeof orderOrIdentify === 'object' 
      ? orderOrIdentify 
      : orders.find((o) => o.identify === orderIdentify)
    if (!order) return
    
    const columnInfo = dynamicColumns.find((c) => c.id === newStatus)
    
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

    if (!newStatus || !dynamicColumns.find((c) => c.id === newStatus)) {
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

  const totalOrders = orders.length
  const pendingOrders = groupedOrders["Preparo"]?.length || 0

  return (
    <div className="flex min-w-0 flex-col gap-6 p-4 lg:p-6 h-full">
      {/* Header Section */}
      <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div className="space-y-1">
          <h1 className="text-3xl font-bold tracking-tight bg-gradient-to-br from-foreground to-foreground/70 bg-clip-text">
            Quadro de Pedidos
          </h1>
          <p className="text-sm text-muted-foreground flex items-center gap-2">
            <span>Arraste os pedidos entre colunas para atualizar o status</span>
            <Badge variant="outline" className="ml-2 font-mono text-xs">
              {totalOrders} {totalOrders === 1 ? 'pedido' : 'pedidos'}
            </Badge>
          </p>
        </div>
        
        <div className="flex flex-wrap items-center gap-2">
          {/* Connection Status */}
          <Badge 
            variant={isConnected ? "default" : "secondary"} 
            className={cn(
              "flex items-center gap-1.5 px-3 py-1.5 transition-all",
              isConnected && "bg-emerald-500 hover:bg-emerald-600"
            )}
          >
            {isConnected ? (
              <Wifi className="h-3.5 w-3.5 animate-pulse" />
            ) : (
              <WifiOff className="h-3.5 w-3.5" />
            )}
            <span className="font-medium">{isConnected ? "Tempo Real" : "Offline"}</span>
          </Badge>
          
          {/* Quick Stats */}
          {pendingOrders > 0 && (
            <Badge variant="outline" className="border-amber-300 bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400 px-3 py-1.5">
              <Clock className="h-3.5 w-3.5 mr-1" />
              {pendingOrders} em preparo
            </Badge>
          )}
          
          {/* Refresh Button */}
          <Button 
            variant="outline" 
            size="sm"
            onClick={loadOrders} 
            disabled={loading}
            className="gap-2 shadow-sm"
          >
            <RefreshCw className={cn("h-4 w-4", loading && "animate-spin")} />
            <span className="hidden sm:inline">Atualizar</span>
          </Button>
        </div>
      </div>

      {/* Board Section */}
      <DndContext 
        onDragStart={handleDragStart}
        onDragEnd={handleDragEnd} 
        collisionDetection={closestCorners} 
        sensors={sensors}
      >
        <div
          className={cn(
            "grid w-full min-w-0 gap-4 pb-4",
            "grid-flow-col auto-cols-[minmax(260px,1fr)] overflow-x-auto",
            "xl:grid-flow-row xl:grid-cols-5 xl:auto-cols-fr xl:overflow-x-visible"
          )}
        >
          {dynamicColumns.map((column) => (
            <BoardColumn 
              key={column.id} 
              column={column} 
              orders={groupedOrders[column.id] || []}
              isUpdating={groupedOrders[column.id]?.some(o => o.identify === updatingIdentify) || false}
              onArchive={openArchiveDialog}
              onMoveOrder={updateOrderStatus}
              allColumns={dynamicColumns}
            />
          ))}
        </div>
        
        <DragOverlay>
          {activeOrder ? (
            <div className="rotate-3 scale-105">
              <OrderCard order={activeOrder} isDragOverlay onArchive={() => {}} />
            </div>
          ) : null}
        </DragOverlay>
      </DndContext>

      <AlertDialog open={!!orderToArchive} onOpenChange={(open) => {
        if (!open) {
          cancelArchiveDialog()
        }
      }}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Arquivar pedido</AlertDialogTitle>
            <AlertDialogDescription>
              Deseja arquivar o pedido <strong>#{orderToArchive?.identify}</strong>? Essa ação não poderá ser desfeita.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={archiving}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={confirmArchiveOrder}
              disabled={archiving}
              className="bg-primary hover:bg-primary/90"
            >
              {archiving ? 'Arquivando...' : 'Arquivar'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}

