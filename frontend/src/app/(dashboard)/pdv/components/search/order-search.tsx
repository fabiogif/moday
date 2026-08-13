"use client"

import { useState, useRef, useEffect } from "react"
import { Search, Loader2, X, Edit } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { apiClient, endpoints } from "@/lib/api-client"

// Funções auxiliares para formatação
function parsePrice(value?: number | string | null): number {
  if (typeof value === "number") {
    return value
  }
  if (typeof value === "string") {
    const parsed = parseFloat(value)
    return Number.isNaN(parsed) ? 0 : parsed
  }
  return 0
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
  }).format(value)
}

type Order = {
  id?: string | number
  uuid?: string
  identify?: string
  status?: string | null
  total?: number | string
  client?: {
    name?: string
    phone?: string
  }
}

interface OrderSearchProps {
  onOrderSelect: (orderId: string) => void
  placeholder?: string
  className?: string
}

export function OrderSearch({ onOrderSelect, placeholder = "Buscar pedido... (Ctrl+F)", className }: OrderSearchProps) {
  const [searchQuery, setSearchQuery] = useState("")
  const [searchResults, setSearchResults] = useState<Order[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const searchRef = useRef<HTMLDivElement>(null)

  // Fechar dropdown ao clicar fora
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
        setSearchResults([])
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  // Atalho Ctrl+F / Cmd+F
  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if ((event.ctrlKey || event.metaKey) && event.key === "f") {
        event.preventDefault()
        const input = searchRef.current?.querySelector("input")
        if (input) {
          input.focus()
        }
      }
    }

    document.addEventListener("keydown", handleKeyDown)
    return () => document.removeEventListener("keydown", handleKeyDown)
  }, [])

  const handleSearch = async (query: string) => {
    if (!query || query.length < 2) {
      setSearchResults([])
      return
    }

    setIsLoading(true)
    try {
      const response = await apiClient.get(endpoints.orders.searchByNumber + `?query=${encodeURIComponent(query)}`)
      if (response.success && response.data) {
        setSearchResults(Array.isArray(response.data) ? response.data : [])
      } else {
        setSearchResults([])
      }
    } catch (error) {
      setSearchResults([])
    } finally {
      setIsLoading(false)
    }
  }

  const handleClear = () => {
    setSearchQuery("")
    setSearchResults([])
  }

  const handleOrderClick = (order: Order) => {
    const orderId = order.identify || order.uuid || order.id
    if (orderId) {
      onOrderSelect(String(orderId))
      handleClear()
    }
  }

  return (
    <div ref={searchRef} className={`relative flex-1 sm:max-w-xs ${className || ""}`}>
      <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
      <Input
        type="text"
        placeholder={placeholder}
        value={searchQuery}
        onChange={(e) => {
          const value = e.target.value
          setSearchQuery(value)
          handleSearch(value)
        }}
        className="h-9 pl-10 pr-10 text-sm"
        data-search-input
      />
      {isLoading && (
        <Loader2 className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
      )}
      {searchQuery && !isLoading && (
        <Button
          variant="ghost"
          size="icon"
          className="absolute right-1 top-1/2 h-7 w-7 -translate-y-1/2"
          onClick={handleClear}
        >
          <X className="h-3.5 w-3.5" />
        </Button>
      )}

      {/* Dropdown de resultados */}
      {searchResults.length > 0 && (
        <div className="absolute left-0 right-0 top-full z-50 mt-2 max-h-64 overflow-y-auto rounded-lg border bg-card shadow-lg">
          {searchResults.map((order: Order) => {
            const orderId = order.identify || order.uuid || order.id
            const orderTotal = parsePrice(order.total)
            const orderStatus = order.status || "Pendente"
            return (
              <button
                key={orderId}
                onClick={() => handleOrderClick(order)}
                className="flex w-full items-center justify-between gap-3 border-b p-3 text-left hover:bg-muted/50 last:border-0 transition-colors"
              >
                <div className="flex-1">
                  <p className="font-semibold">Pedido #{order.identify || order.id}</p>
                  <p className="text-xs text-muted-foreground">
                    {orderStatus} • {formatCurrency(orderTotal)}
                  </p>
                  {order.client && (
                    <p className="text-xs text-muted-foreground">
                      Cliente: {order.client.name}
                    </p>
                  )}
                </div>
                <Edit className="h-4 w-4 text-primary" />
              </button>
            )
          })}
        </div>
      )}
    </div>
  )
}

