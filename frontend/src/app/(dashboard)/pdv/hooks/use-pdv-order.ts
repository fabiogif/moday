/**
 * Hook customizado para gerenciamento de estado do pedido no PDV
 * Encapsula lógica de carrinho, cálculos e operações do pedido
 */

import { useState, useCallback, useMemo } from "react"
import { toast } from "sonner"
import {
  calculateOrderTotal,
  getCartItemUnitPrice,
  getCartItemTotal,
  type CartItem,
} from "../services/order-calculator"

export interface UsePDVOrderOptions {
  onItemAdded?: (item: CartItem) => void
  onItemRemoved?: (item: CartItem) => void
  onItemUpdated?: (item: CartItem) => void
  onCartCleared?: () => void
}

export interface UsePDVOrderReturn {
  cart: CartItem[]
  addItem: (item: Omit<CartItem, "signature">, signature: string) => void
  removeItem: (signature: string) => void
  updateItemQuantity: (signature: string, delta: number) => void
  updateItemObservation: (signature: string, observation: string) => void
  clearCart: () => void
  getItemBySignature: (signature: string) => CartItem | undefined
  orderTotal: number
  subtotal: number
  taxes: number
  discounts: number
  itemCount: number
}

/**
 * Hook para gerenciar estado do pedido no PDV
 */
export function usePDVOrder(
  options: UsePDVOrderOptions = {}
): UsePDVOrderReturn {
  const [cart, setCart] = useState<CartItem[]>([])

  const addItem = useCallback(
    (item: Omit<CartItem, "signature">, signature: string) => {
      setCart((prev) => {
        const exists = prev.find((i) => i.signature === signature)
        if (exists) {
          const updated = prev.map((i) =>
            i.signature === signature
              ? { ...i, quantity: i.quantity + 1 }
              : i
          )
          options.onItemUpdated?.(updated.find((i) => i.signature === signature)!)
          return updated
        }

        const newItem: CartItem = { ...item, signature }
        options.onItemAdded?.(newItem)
        return [...prev, newItem]
      })
      const productName =
        // Tipagem de CartItem em order-calculator não garante 'name', então usamos fallback seguro
        (item.product as any).name || "Item"
      toast.success(`${productName} adicionado ao pedido`)
    },
    [options]
  )

  const removeItem = useCallback(
    (signature: string) => {
      setCart((prev) => {
        const item = prev.find((i) => i.signature === signature)
        if (item) {
          options.onItemRemoved?.(item)
        }
        return prev.filter((i) => i.signature !== signature)
      })
    },
    [options]
  )

  const updateItemQuantity = useCallback(
    (signature: string, delta: number) => {
      setCart((prev) =>
        prev
          .map((item) => {
            if (item.signature !== signature) return item
            const newQty = Math.max(0, item.quantity + delta)
            if (newQty === 0) {
              options.onItemRemoved?.(item)
              return null
            }
            const updated = { ...item, quantity: newQty }
            options.onItemUpdated?.(updated)
            return updated
          })
          .filter((item): item is CartItem => item !== null)
      )
    },
    [options]
  )

  const updateItemObservation = useCallback(
    (signature: string, observation: string) => {
      setCart((prev) =>
        prev.map((item) => {
          if (item.signature !== signature) return item
          const updated = { ...item, observation }
          options.onItemUpdated?.(updated)
          return updated
        })
      )
    },
    [options]
  )

  const clearCart = useCallback(() => {
    setCart([])
    options.onCartCleared?.()
  }, [options])

  const getItemBySignature = useCallback(
    (signature: string) => {
      return cart.find((item) => item.signature === signature)
    },
    [cart]
  )

  // Cálculos memoizados
  const calculations = useMemo(() => {
    return calculateOrderTotal(cart)
  }, [cart])

  const itemCount = useMemo(() => {
    return cart.reduce((sum, item) => sum + item.quantity, 0)
  }, [cart])

  return {
    cart,
    addItem,
    removeItem,
    updateItemQuantity,
    updateItemObservation,
    clearCart,
    getItemBySignature,
    orderTotal: calculations.total,
    subtotal: calculations.subtotal,
    taxes: calculations.taxes,
    discounts: calculations.discounts,
    itemCount,
  }
}

