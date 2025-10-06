"use client"

import { useState } from "react"
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"

interface PermissionsPaginationProps {
  onPageChange: (page: number) => void
  currentPage: number
  totalPages: number
  totalItems: number
  itemsPerPage: number
}

export function PermissionsPagination({
  onPageChange,
  currentPage,
  totalPages,
  totalItems,
  itemsPerPage,
}: PermissionsPaginationProps) {
  const [page, setPage] = useState(currentPage)

  const handlePageChange = (newPage: number) => {
    if (newPage >= 1 && newPage <= totalPages) {
      setPage(newPage)
      onPageChange(newPage)
    }
  }

  const generatePageNumbers = () => {
    const pages: (number | string)[] = []
    const maxVisiblePages = 5

    if (totalPages <= maxVisiblePages) {
      // Se há poucas páginas, mostrar todas
      for (let i = 1; i <= totalPages; i++) {
        pages.push(i)
      }
    } else {
      // Lógica para mostrar páginas com ellipsis
      if (currentPage <= 3) {
        // Páginas iniciais
        for (let i = 1; i <= 4; i++) {
          pages.push(i)
        }
        pages.push("ellipsis")
        pages.push(totalPages)
      } else if (currentPage >= totalPages - 2) {
        // Páginas finais
        pages.push(1)
        pages.push("ellipsis")
        for (let i = totalPages - 3; i <= totalPages; i++) {
          pages.push(i)
        }
      } else {
        // Páginas do meio
        pages.push(1)
        pages.push("ellipsis")
        for (let i = currentPage - 1; i <= currentPage + 1; i++) {
          pages.push(i)
        }
        pages.push("ellipsis")
        pages.push(totalPages)
      }
    }

    return pages
  }

  const startItem = (currentPage - 1) * itemsPerPage + 1
  const endItem = Math.min(currentPage * itemsPerPage, totalItems)

  if (totalPages <= 1) {
    return null
  }

  return (
    <div className="flex items-center justify-between">
      <div className="text-sm text-muted-foreground">
        Mostrando {startItem} a {endItem} de {totalItems} permissões
      </div>
      
      <Pagination>
        <PaginationContent>
          <PaginationItem>
            <PaginationPrevious
              onClick={() => handlePageChange(currentPage - 1)}
              className={currentPage <= 1 ? "pointer-events-none opacity-50" : "cursor-pointer"}
            />
          </PaginationItem>

          {generatePageNumbers().map((pageNumber, index) => (
            <PaginationItem key={index}>
              {pageNumber === "ellipsis" ? (
                <PaginationEllipsis />
              ) : (
                <PaginationLink
                  onClick={() => handlePageChange(pageNumber as number)}
                  isActive={currentPage === pageNumber}
                  className="cursor-pointer"
                >
                  {pageNumber}
                </PaginationLink>
              )}
            </PaginationItem>
          ))}

          <PaginationItem>
            <PaginationNext
              onClick={() => handlePageChange(currentPage + 1)}
              className={currentPage >= totalPages ? "pointer-events-none opacity-50" : "cursor-pointer"}
            />
          </PaginationItem>
        </PaginationContent>
      </Pagination>
    </div>
  )
}
