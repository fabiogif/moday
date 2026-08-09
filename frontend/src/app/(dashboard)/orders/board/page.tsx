"use client"

import React, { useState, useEffect, useMemo, useCallback } from "react"
import {
  DndContext,
  DragEndEvent,
  DragOverlay,
  DragStartEvent,
  closestCorners,
  PointerSensor,
  useSensor,
  useSensors,
} from "@dnd-kit/core"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { PageLoading } from "@/components/ui/loading-progress"
import { apiClient, endpoints } from "@/lib/api-client"
import { toast } from "sonner"
import { useRealtimeOrders } from "@/hooks/use-realtime"
import { Wifi, WifiOff, RefreshCw } from "lucide-react"
import { useAuth } from "@/contexts/auth-context"
import { BOARD_COLUMNS } from "./constants"
import { BoardColumn } from "./components/board-column"
import { OrderCard } from "./components/order-card"
import { OrderDetailSheet } from "./components/order-detail-sheet"
import { normalizeBoardOrder } from "./utils"
import type { BoardOrder, BoardOrderStatus } from "./types"

export default function OrdersBoardPage() {
  const { user } = useAuth()
  const [loading, setLoading] = useState(true)
  const [orders, setOrders] = useState<BoardOrder[]>([])
  const [updatingIdentify, setUpdatingIdentify] = useState<string | null>(null)
  const [activeOrder, setActiveOrder] = useState<BoardOrder | null>(null)
  const [selectedOrder, setSelectedOrder] = useState<BoardOrder | null>(null)
  const [detailOpen, setDetailOpen] = useState(false)

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    })
  )

  const tenantId = user?.tenant_id ? parseInt(user.tenant_id, 10) : 0

  const { isConnected } = useRealtimeOrders({
    tenantId,
    enabled: !!user?.tenant_id,
    onOrderCreated: useCallback((newOrder: any) => {
      const normalized = normalizeBoardOrder(newOrder)

      setOrders((prev) => {
        if (prev.some((o) => o.identify === normalized.identify)) {
          return prev
        }
        return [normalized, ...prev]
      })

      toast.success(`Novo pedido #${normalized.identify} criado!`)
    }, []),

    onOrderStatusUpdated: useCallback(({ order: updatedOrder, oldStatus, newStatus }: any) => {
      setOrders((prev) =>
        prev.map((o) =>
          o.identify === updatedOrder.identify
            ? { ...normalizeBoardOrder(updatedOrder), status: newStatus }
            : o
        )
      )

      toast.info(`Pedido #${updatedOrder.identify} mudou de "${oldStatus}" para "${newStatus}"`)
    }, []),

    onOrderUpdated: useCallback((updatedOrder: any) => {
      setOrders((prev) =>
        prev.map((o) =>
          o.identify === updatedOrder.identify ? normalizeBoardOrder(updatedOrder) : o
        )
      )
    }, []),
  })

  const loadOrders = useCallback(async () => {
    try {
      setLoading(true)
      const res = await apiClient.get<any>(endpoints.orders.board, { terminal_days: 7 })

      const raw = Array.isArray(res.data)
        ? res.data
        : res.data?.orders || res.data?.data || []

      setOrders(raw.map((o: any) => normalizeBoardOrder(o)))
    } catch (e: any) {
      toast.error(e?.message || "Erro ao carregar pedidos")
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    loadOrders()
  }, [loadOrders])

  const groupedOrders = useMemo(() => {
    const map: Record<BoardOrderStatus, BoardOrder[]> = {
      "Em Preparo": [],
      Pronto: [],
      Entregue: [],
      Cancelado: [],
    }

    for (const order of orders) {
      const status =
        BOARD_COLUMNS.find((c) => c.id === order.status)?.id || "Em Preparo"
      map[status].push(order)
    }

    return map
  }, [orders])

  const updateOrderStatus = async (orderIdentify: string, newStatus: BoardOrderStatus) => {
    const order = orders.find((o) => o.identify === orderIdentify)
    if (!order) return

    const columnInfo = BOARD_COLUMNS.find((c) => c.id === newStatus)

    try {
      setUpdatingIdentify(orderIdentify)

      await apiClient.put(endpoints.orders.update(orderIdentify), { status: newStatus })

      setOrders((prev) =>
        prev.map((o) => (o.identify === orderIdentify ? { ...o, status: newStatus } : o))
      )

      toast.success(`Pedido #${orderIdentify} movido para ${columnInfo?.title}`)
    } catch (e: any) {
      toast.error(e?.message || "Não foi possível atualizar o status")
      await loadOrders()
    } finally {
      setUpdatingIdentify(null)
    }
  }

  const handleOpenOrder = (order: BoardOrder) => {
    setSelectedOrder(order)
    setDetailOpen(true)
  }

  const handleDragStart = (event: DragStartEvent) => {
    const { active } = event
    const orderIdentify = String(active.id).replace("order-", "")
    const order = orders.find((o) => o.identify === orderIdentify)

    if (order) {
      setActiveOrder(order)
    }
  }

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event
    setActiveOrder(null)

    if (!over) return

    const orderIdentify = String(active.id).replace("order-", "")
    const currentOrder = orders.find((o) => o.identify === orderIdentify)

    if (!currentOrder) return

    const overData: any = over.data?.current
    let newStatus: BoardOrderStatus | null = null

    if (overData?.column) {
      newStatus = overData.column as BoardOrderStatus
    } else {
      const targetOrderIdentify = String(over.id).replace("order-", "")
      const targetOrder = orders.find((o) => o.identify === targetOrderIdentify)
      if (targetOrder) {
        newStatus = targetOrder.status as BoardOrderStatus
      }
    }

    if (!newStatus || !BOARD_COLUMNS.find((c) => c.id === newStatus)) {
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
          <p className="text-muted-foreground">
            Arraste pedidos entre colunas ou clique para ver os detalhes
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Badge
            variant={isConnected ? "default" : "secondary"}
            className="flex items-center gap-1.5 px-3"
          >
            {isConnected ? <Wifi className="h-3.5 w-3.5" /> : <WifiOff className="h-3.5 w-3.5" />}
            <span>{isConnected ? "Online" : "Offline"}</span>
          </Badge>
          <Button
            variant="outline"
            size="sm"
            onClick={loadOrders}
            disabled={loading}
            className="gap-2"
          >
            <RefreshCw className={`h-4 w-4 ${loading ? "animate-spin" : ""}`} />
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
          {BOARD_COLUMNS.map((column) => (
            <BoardColumn
              key={column.id}
              column={column}
              orders={groupedOrders[column.id] || []}
              isUpdating={
                groupedOrders[column.id]?.some((o) => o.identify === updatingIdentify) || false
              }
              onOpenOrder={handleOpenOrder}
            />
          ))}
        </div>

        <DragOverlay>
          {activeOrder ? <OrderCard order={activeOrder} isDragOverlay /> : null}
        </DragOverlay>
      </DndContext>

      <OrderDetailSheet
        order={selectedOrder}
        open={detailOpen}
        onOpenChange={(open) => {
          setDetailOpen(open)
          if (!open) setSelectedOrder(null)
        }}
      />
    </div>
  )
}
