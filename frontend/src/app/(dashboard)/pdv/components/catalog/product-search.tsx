"use client"

import { useState, useMemo, useEffect, useRef } from "react"
import { Search, X, Clock } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"

type Product = {
  uuid?: string
  identify?: string
  name: string
  [key: string]: any
}

interface ProductSearchProps {
  products: Product[]
  onSearchChange?: (query: string) => void
  onProductSelect?: (product: Product) => void
  placeholder?: string
  className?: string
  showRecentSearches?: boolean
  maxRecentSearches?: number
}

const RECENT_SEARCHES_KEY = "pdv-recent-searches"

export function ProductSearch({
  products,
  onSearchChange,
  onProductSelect,
  placeholder = "Buscar produtos...",
  className,
  showRecentSearches = true,
  maxRecentSearches = 5,
}: ProductSearchProps) {
  const [searchQuery, setSearchQuery] = useState("")
  const [recentSearches, setRecentSearches] = useState<string[]>([])
  const [showResults, setShowResults] = useState(false)
  const [isFocused, setIsFocused] = useState(false)
  const searchRef = useRef<HTMLDivElement>(null)

  // Carregar buscas recentes do localStorage
  useEffect(() => {
    if (typeof window !== "undefined" && showRecentSearches) {
      const stored = localStorage.getItem(RECENT_SEARCHES_KEY)
      if (stored) {
        try {
          setRecentSearches(JSON.parse(stored))
        } catch {
          // Ignorar erro de parse
        }
      }
    }
  }, [showRecentSearches])

  // Filtrar produtos baseado na busca
  const filteredProducts = useMemo(() => {
    if (!searchQuery || searchQuery.trim().length < 1) {
      return []
    }

    const query = searchQuery.toLowerCase().trim()
    return products.filter((product) => {
      const name = product.name?.toLowerCase() || ""
      const identify = product.identify?.toLowerCase() || ""
      const uuid = product.uuid?.toLowerCase() || ""
      const description = product.description?.toLowerCase() || ""

      return (
        name.includes(query) ||
        identify.includes(query) ||
        uuid.includes(query) ||
        description.includes(query)
      )
    })
  }, [products, searchQuery])

  // Atualizar resultados quando a busca mudar
  useEffect(() => {
    if (filteredProducts.length > 0 && searchQuery.length >= 1) {
      setShowResults(true)
    } else {
      setShowResults(false)
    }
  }, [filteredProducts, searchQuery])

  // Fechar dropdown ao clicar fora
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        searchRef.current &&
        !searchRef.current.contains(event.target as Node)
      ) {
        setShowResults(false)
        setIsFocused(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  const handleSearchChange = (value: string) => {
    setSearchQuery(value)
    onSearchChange?.(value)
  }

  const handleClear = () => {
    setSearchQuery("")
    setShowResults(false)
    onSearchChange?.("")
  }

  const handleProductClick = (product: Product) => {
    if (onProductSelect) {
      onProductSelect(product)
    }
    // Salvar busca recente
    if (showRecentSearches && searchQuery.trim().length > 0) {
      const newRecent = [
        searchQuery.trim(),
        ...recentSearches.filter((s) => s !== searchQuery.trim()),
      ].slice(0, maxRecentSearches)
      setRecentSearches(newRecent)
      if (typeof window !== "undefined") {
        localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(newRecent))
      }
    }
    handleClear()
  }

  const handleRecentSearchClick = (search: string) => {
    setSearchQuery(search)
    onSearchChange?.(search)
  }

  return (
    <div ref={searchRef} className={cn("relative", className)}>
      <div className="relative">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          type="text"
          placeholder={placeholder}
          value={searchQuery}
          onChange={(e) => handleSearchChange(e.target.value)}
          onFocus={() => setIsFocused(true)}
          onBlur={() => {
            // Delay para permitir cliques nos resultados
            setTimeout(() => setIsFocused(false), 200)
          }}
          className="h-9 pl-10 pr-10 text-sm"
          data-product-search-input
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
      </div>

      {/* Dropdown de resultados */}
      {showResults && filteredProducts.length > 0 && (
        <div className="absolute left-0 right-0 top-full z-50 mt-2 max-h-64 overflow-y-auto rounded-lg border bg-card shadow-lg">
          {filteredProducts.slice(0, 10).map((product) => {
            const productId = product.identify || product.uuid || product.name
            return (
              <button
                key={productId}
                onClick={() => handleProductClick(product)}
                className="flex w-full items-center justify-between gap-3 border-b p-3 text-left hover:bg-muted/50 last:border-0 transition-colors"
              >
                <div className="flex-1">
                  <p className="font-semibold">{product.name}</p>
                  {product.identify && (
                    <p className="text-xs text-muted-foreground">
                      Código: {product.identify}
                    </p>
                  )}
                </div>
                <Search className="h-4 w-4 text-primary" />
              </button>
            )
          })}
        </div>
      )}

      {/* Mensagem quando não há resultados */}
      {showResults &&
        searchQuery.length >= 1 &&
        filteredProducts.length === 0 && (
          <div className="absolute left-0 right-0 top-full z-50 mt-2 rounded-lg border bg-card shadow-lg p-4 text-center text-sm text-muted-foreground">
            Nenhum produto encontrado
          </div>
        )}

      {/* Buscas recentes - só mostrar quando o input estiver focado */}
      {showRecentSearches &&
        isFocused &&
        !searchQuery &&
        recentSearches.length > 0 &&
        !showResults && (
          <div className="absolute left-0 right-0 top-full z-50 mt-2 rounded-lg border bg-card shadow-lg p-2">
            <div className="flex items-center gap-2 px-2 py-1.5 text-xs font-medium text-muted-foreground">
              <Clock className="h-3.5 w-3.5" />
              Buscas recentes
            </div>
            <div className="flex flex-wrap gap-1.5 p-2">
              {recentSearches.map((search, index) => (
                <Badge
                  key={index}
                  variant="secondary"
                  className="cursor-pointer hover:bg-primary hover:text-primary-foreground transition-colors"
                  onClick={() => handleRecentSearchClick(search)}
                >
                  {search}
                </Badge>
              ))}
            </div>
          </div>
        )}
    </div>
  )
}

