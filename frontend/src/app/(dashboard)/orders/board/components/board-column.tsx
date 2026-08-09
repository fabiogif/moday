"use client"

import React from "react"
import { useDroppable } from "@dnd-kit/core"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { OrderCard } from "./order-card"
import type { BoardColumnDef, BoardOrder, BoardOrderStatus } from "../types"

interface DroppableColumnAreaProps {
  columnId: BoardOrderStatus
  children: React.ReactNode
}

function DroppableColumnArea({ columnId, children }: DroppableColumnAreaProps) {
  const { setNodeRef, isOver } = useDroppable({
    id: `column-${columnId}`,
    data: { column: columnId },
  })

  return (
    <div
      ref={setNodeRef}
      className={`flex flex-col gap-2 min-h-[200px] rounded-md p-2 transition-colors ${
        isOver ? "bg-accent/50 border-2 border-dashed border-primary" : ""
      }`}
    >
      {children}
    </div>
  )
}

interface BoardColumnProps {
  column: BoardColumnDef
  orders: BoardOrder[]
  isUpdating: boolean
  onOpenOrder: (order: BoardOrder) => void
}

export function BoardColumn({ column, orders, isUpdating, onOpenOrder }: BoardColumnProps) {
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
            <OrderCard key={order.identify} order={order} onOpen={onOpenOrder} />
          ))}
          {isUpdating && (
            <div className="text-xs text-muted-foreground text-center py-2">Atualizando...</div>
          )}
        </DroppableColumnArea>
      </CardContent>
    </Card>
  )
}
