"use client"

import { useState, useRef, useEffect, useMemo } from "react"
import { Search, Loader2, X, Edit, Utensils } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { apiClient, endpoints } from "@/lib/api-client"
import { cn } from "@/lib/utils"

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
  table?: {
    uuid?: string
    identify?: string
    name?: string
  }
}

type Table = {
  uuid?: string
  identify?: string
  name: string
  isOccupied?: boolean
  orderCount?: number
}

interface CombinedSearchProps {
  onOrderSelect: (orderId: string) => void
  onTableSelect?: (table: Table) => void
  tables: Table[]
  placeholder?: string
  className?: string
}

export function CombinedSearch({
  onOrderSelect,
  onTableSelect,
  tables,
  placeholder = "Buscar pedido ou mesa... (Ctrl+F)",
  className,
}: CombinedSearchProps) {
  const [searchQuery, setSearchQuery] = useState("")
  const [orderResults, setOrderResults] = useState<Order[]>([])
  const [tableResults, setTableResults] = useState<Table[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const searchRef = useRef<HTMLDivElement>(null)

  // Filtrar mesas baseado na busca
  const filteredTables = useMemo(() => {
    if (!searchQuery || searchQuery.length < 1) {
      return []
    }

    const query = searchQuery.toLowerCase().trim()
    return tables.filter((table) => {
      const name = table.name?.toLowerCase() || ""
      const identify = table.identify?.toLowerCase() || ""
      const uuid = table.uuid?.toLowerCase() || ""
      return name.includes(query) || identify.includes(query) || uuid.includes(query)
    })
  }, [tables, searchQuery])

  // Fechar dropdown ao clicar fora
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
        setOrderResults([])
        setTableResults([])
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

  // Atualizar resultados de mesas quando a busca mudar
  useEffect(() => {
    if (filteredTables.length > 0 && searchQuery.length >= 1) {
      setTableResults(filteredTables)
    } else {
      setTableResults([])
    }
  }, [filteredTables, searchQuery])

  const handleSearch = async (query: string) => {
    if (!query || query.length < 2) {
      setOrderResults([])
      return
    }

    setIsLoading(true)
    try {
      const response = await apiClient.get(endpoints.orders.searchByNumber + `?query=${encodeURIComponent(query)}`)
      if (response.success && response.data) {
        setOrderResults(Array.isArray(response.data) ? response.data : [])
      } else {
        setOrderResults([])
      }
    } catch (error) {
      setOrderResults([])
    } finally {
      setIsLoading(false)
    }
  }

  const handleClear = () => {
    setSearchQuery("")
    setOrderResults([])
    setTableResults([])
  }

  const handleOrderClick = (order: Order) => {
    const orderId = order.identify || order.uuid || order.id
    if (orderId) {
      onOrderSelect(String(orderId))
      handleClear()
    }
  }

  const handleTableClick = (table: Table) => {
    if (onTableSelect) {
      onTableSelect(table)
    }
    handleClear()
  }

  const hasResults = orderResults.length > 0 || tableResults.length > 0
  const showResults = hasResults && searchQuery.length >= 1

  return (
    <div ref={searchRef} className={cn("relative flex-1 sm:max-w-xs", className)}>
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
        data-combined-search-input
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
      {showResults && (
        <div className="absolute left-0 right-0 top-full z-50 mt-2 max-h-80 overflow-y-auto rounded-lg border bg-card shadow-lg">
          {/* Resultados de Pedidos */}
          {orderResults.length > 0 && (
            <div className="border-b p-2">
              <p className="text-xs font-semibold text-muted-foreground mb-2 px-2">Pedidos</p>
              {orderResults.map((order: Order) => {
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
                      {order.table && (
                        <p className="text-xs text-muted-foreground">
                          Mesa: {order.table.name}
                        </p>
                      )}
                    </div>
                    <Edit className="h-4 w-4 text-primary" />
                  </button>
                )
              })}
            </div>
          )}

          {/* Resultados de Mesas */}
          {tableResults.length > 0 && (
            <div className="p-2">
              <p className="text-xs font-semibold text-muted-foreground mb-2 px-2">Mesas</p>
              {tableResults.map((table: Table) => {
                const tableId = table.identify || table.uuid || table.name
                return (
                  <button
                    key={tableId}
                    onClick={() => handleTableClick(table)}
                    className="flex w-full items-center justify-between gap-3 border-b p-3 text-left hover:bg-muted/50 last:border-0 transition-colors"
                  >
                    <div className="flex items-center gap-3 flex-1">
                      <Utensils className="h-4 w-4 text-muted-foreground" />
                      <div className="flex-1">
                        <p className="font-semibold">{table.name}</p>
                        {table.orderCount !== undefined && table.orderCount > 0 && (
                          <p className="text-xs text-muted-foreground">
                            {table.orderCount} pedido{table.orderCount !== 1 ? "s" : ""} em aberto
                          </p>
                        )}
                      </div>
                    </div>
                    {table.isOccupied && (
                      <Badge variant="destructive" className="text-xs">
                        Ocupada
                      </Badge>
                    )}
                  </button>
                )
              })}
            </div>
          )}

          {/* Mensagem quando não há resultados */}
          {searchQuery.length >= 2 && orderResults.length === 0 && tableResults.length === 0 && !isLoading && (
            <div className="p-4 text-center text-sm text-muted-foreground">
              Nenhum pedido ou mesa encontrado
            </div>
          )}
        </div>
      )}
    </div>
  )
}









