"use client"

import { useEffect, useRef } from "react"
import { useDraggable } from "@dnd-kit/core"
import { CSS } from "@dnd-kit/utilities"
import { Badge } from "@/components/ui/badge"
import { Truck, User, UtensilsCrossed } from "lucide-react"
import type { BoardOrder } from "../types"

interface OrderCardProps {
  order: BoardOrder
  isDragOverlay?: boolean
  onOpen?: (order: BoardOrder) => void
}

export function OrderCard({ order, isDragOverlay = false, onOpen }: OrderCardProps) {
  const { setNodeRef, attributes, listeners, transform, isDragging } = useDraggable({
    id: `order-${order.identify}`,
    data: { order },
  })
  const didDragRef = useRef(false)

  useEffect(() => {
    if (isDragging) {
      didDragRef.current = true
    }
  }, [isDragging])

  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    opacity: isDragging ? 0.5 : 1,
    cursor: isDragOverlay ? "grabbing" : "grab",
  }

  const deliveryAddress =
    order.is_delivery &&
    (order.full_delivery_address ||
      (order.delivery_address &&
        `${order.delivery_address}${order.delivery_number ? ", " + order.delivery_number : ""} - ${order.delivery_neighborhood || ""}, ${order.delivery_city || ""} - ${order.delivery_state || ""}`))

  const customerName = order.client?.name || order.client_full_name
  const total = typeof order.total === "string" ? parseFloat(order.total) : order.total

  const handleClick = (e: React.MouseEvent) => {
    if (isDragging || isDragOverlay || didDragRef.current) {
      didDragRef.current = false
      return
    }
    e.stopPropagation()
    onOpen?.(order)
  }

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={`rounded-md border bg-card p-3 text-sm shadow-sm hover:shadow-md transition-all ${
        isDragging ? "shadow-lg border-primary ring-2 ring-primary ring-offset-2" : ""
      } ${isDragOverlay ? "shadow-2xl" : ""} ${onOpen && !isDragOverlay ? "cursor-pointer" : ""}`}
      {...attributes}
      {...listeners}
      onClick={handleClick}
      role={onOpen ? "button" : undefined}
      tabIndex={onOpen ? 0 : undefined}
      onKeyDown={(e) => {
        if (!onOpen || isDragOverlay) return
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault()
          onOpen(order)
        }
      }}
    >
      <div className="flex items-center justify-between mb-2">
        <span className="font-medium text-base">#{order.identify}</span>
        <Badge variant="secondary" className="text-xs">
          {order.status}
        </Badge>
      </div>

      <div className="space-y-2">
        {customerName && (
          <div className="flex items-center gap-1.5 text-muted-foreground">
            <User className="h-3 w-3 shrink-0" />
            <span className="text-xs truncate">{customerName}</span>
          </div>
        )}

        {order.table && (
          <div className="flex items-center gap-1.5 text-muted-foreground">
            <UtensilsCrossed className="h-3 w-3 shrink-0" />
            <span className="text-xs truncate">{order.table.name}</span>
          </div>
        )}

        {deliveryAddress && (
          <div className="space-y-1">
            <div className="flex items-start gap-1.5 text-muted-foreground">
              <Truck className="h-3 w-3 shrink-0 mt-0.5" />
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
                    {product.quantity ? `${product.quantity}x` : "1x"}
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
          <span className="text-sm font-semibold">R$ {Number(total).toFixed(2)}</span>
        </div>
      </div>
    </div>
  )
}
