"use client"

import { useState, useRef, useEffect, useMemo } from "react"
import { Search, Loader2, X, Utensils, CheckCircle2, Clock } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"

type Table = {
  uuid?: string
  identify?: string
  name: string
  isOccupied?: boolean
  orderCount?: number
}

interface TableSearchProps {
  tables: Table[]
  onTableSelect?: (table: Table) => void
  placeholder?: string
  className?: string
  showStatus?: boolean
}

export function TableSearch({
  tables,
  onTableSelect,
  placeholder = "Buscar mesa...",
  className,
  showStatus = true,
}: TableSearchProps) {
  const [searchQuery, setSearchQuery] = useState("")
  const [searchResults, setSearchResults] = useState<Table[]>([])
  const [isOpen, setIsOpen] = useState(false)
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

  // Atualizar resultados quando a busca mudar
  useEffect(() => {
    if (filteredTables.length > 0 && searchQuery.length >= 1) {
      setSearchResults(filteredTables)
      setIsOpen(true)
    } else {
      setSearchResults([])
      setIsOpen(false)
    }
  }, [filteredTables, searchQuery])

  // Fechar dropdown ao clicar fora
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  const handleClear = () => {
    setSearchQuery("")
    setSearchResults([])
    setIsOpen(false)
  }

  const handleTableClick = (table: Table) => {
    if (onTableSelect) {
      onTableSelect(table)
    }
    handleClear()
  }

  const getTableStatus = (table: Table) => {
    if (table.isOccupied) {
      return {
        label: "Ocupada",
        icon: Clock,
        variant: "destructive" as const,
      }
    }
    return {
      label: "Disponível",
      icon: CheckCircle2,
      variant: "secondary" as const,
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
        }}
        className="h-9 pl-10 pr-10 text-sm"
        data-table-search-input
      />
      {searchQuery && (
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
      {isOpen && searchResults.length > 0 && (
        <div className="absolute left-0 right-0 top-full z-50 mt-2 max-h-64 overflow-y-auto rounded-lg border bg-card shadow-lg">
          {searchResults.map((table: Table) => {
            const tableId = table.identify || table.uuid || table.name
            const status = getTableStatus(table)
            const StatusIcon = status.icon

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
                {showStatus && (
                  <Badge variant={status.variant} className="flex items-center gap-1">
                    <StatusIcon className="h-3 w-3" />
                    {status.label}
                  </Badge>
                )}
              </button>
            )
          })}
        </div>
      )}

      {/* Mensagem quando não há resultados */}
      {isOpen && searchQuery.length >= 1 && searchResults.length === 0 && (
        <div className="absolute left-0 right-0 top-full z-50 mt-2 rounded-lg border bg-card shadow-lg p-4 text-center text-sm text-muted-foreground">
          Nenhuma mesa encontrada
        </div>
      )}
    </div>
  )
}

