"use client"

import { useState } from "react"
import { useRouter } from "next/navigation"
import {
  type ColumnDef,
  type ColumnFiltersState,
  type SortingState,
  type VisibilityState,
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table"
import {
  ChevronDown,
  EllipsisVertical,
  Eye,
  Pencil,
  Trash2,
  Download,
  Search,
  Plus,
} from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { ProductFormDialog } from "./product-form-dialog"
import { ProductEditDialog } from "./product-edit-dialog"
import { DeleteProductDialog } from "./delete-product-dialog"

interface Product {
  id: number
  name: string
  description: string
  price: number
  categories: Array<{
    identify: string
    name: string
  }>
  price_cost: number
  is_active: boolean
  created_at: string
  createdAt: string
  url?: string
}

interface ProductFormValues {
  name: string
  description: string
  price: number
  categories: string[]
  price_cost: number
  qtd_stock: number
  image?: File
}

interface DataTableProps {
  products: Product[]
  onDeleteProduct: (id: number) => void
  onEditProduct: (product: Product) => void
  onAddProduct: (productData: ProductFormValues) => void
}

export function DataTable({ products, onDeleteProduct, onEditProduct, onAddProduct }: DataTableProps) {
  const router = useRouter();
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})
  const [globalFilter, setGlobalFilter] = useState("")

  const getStatusColor = (isActive: boolean) => {
    return isActive
      ? "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
      : "text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20"
  }

  const getStockColor = (qtd_stock: number) => {
    if (qtd_stock === 0) return "text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20"
    if (qtd_stock < 3) return "text-orange-600 bg-orange-50 dark:text-orange-400 dark:bg-orange-900/20"
     return "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
  }

  const handleViewDetails = (productId: number | string) => {
    router.push(`/products/${productId}`);
  };

  const filterCategories = (row: any, columnId: string, filterValue: string) => {
    if (!filterValue || filterValue === "all") return true
    const categories = row.getValue(columnId) as Array<{identify: string, name: string}>
    return categories?.some(category => category.name === filterValue) || false
  }

  const columns: ColumnDef<Product>[] = [
    {
      id: "select",
      header: ({ table }) => (
        <Checkbox
          checked={
            table.getIsAllPageRowsSelected() ||
            (table.getIsSomePageRowsSelected() && "indeterminate")
          }
          onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
          aria-label="Select all"
        />
      ),
      cell: ({ row }) => (
        <Checkbox
          checked={row.getIsSelected()}
          onCheckedChange={(value) => row.toggleSelected(!!value)}
          aria-label="Select row"
        />
      ),
      enableSorting: false,
      enableHiding: false,
    },
    {
      accessorKey: "name",
      header: "Nome",
      cell: ({ row }) => (
        <div className="flex items-center gap-3">
          {/* Product Image Thumbnail */}
          <div className="relative h-12 w-12 flex-shrink-0 overflow-hidden rounded-md bg-muted">
            {row.original.url ? (
              <img
                src={row.original.url}
                alt={row.getValue("name")}
                className="h-full w-full object-cover"
                onError={(e) => {
                  // Fallback if image fails to load
                  e.currentTarget.style.display = 'none'
                  e.currentTarget.parentElement!.innerHTML = '<div class="flex h-full w-full items-center justify-center text-xs text-muted-foreground">Sem imagem</div>'
                }}
              />
            ) : (
              <div className="flex h-full w-full items-center justify-center text-xs text-muted-foreground">
                Sem imagem
              </div>
            )}
          </div>
          {/* Product Info */}
          <div className="min-w-0 flex-1">
            <div className="font-medium truncate">{row.getValue("name")}</div>
            <div className="text-sm text-muted-foreground max-w-[200px] truncate">
              {row.original.description}
            </div>
          </div>
        </div>
      ),
    },
    {
      accessorKey: "price",
      header: "Preço",
      cell: ({ row }) => {
        const price = parseFloat(row.getValue("price"))
        const formatted = new Intl.NumberFormat("pt-BR", {
          style: "currency",
          currency: "BRL",
        }).format(price)
        return <div className="font-medium">{formatted}</div>
      },
    },
    {
      accessorKey: "categories",
      header: "Categorias",
      filterFn: filterCategories,
      cell: ({ row }) => {
        const categories = row.getValue("categories") as Array<{identify: string, name: string}>
        return (
          <div className="flex flex-wrap gap-1">
            {categories?.map((category, index) => (
              <Badge key={category.identify || `category-${index}`} variant="outline">
                {category.name}
              </Badge>
            )) || <span className="text-muted-foreground">Sem categorias</span>}
          </div>
        )
      },
    },
    {
      accessorKey: "price_cost",
      header: "Preço de custo",
      cell: ({ row }) => {
        const price = parseFloat(row.getValue("price_cost"))
        const formatted = new Intl.NumberFormat("pt-BR", {
          style: "currency",
          currency: "BRL",
        }).format(price)
        return <div className="font-medium">{formatted}</div>
  
      },
    },
    {
      accessorKey: "qty_stock",
      header: "Qtd. Estoque",
      cell: ({ row }) => {
        const stock = row.getValue("qty_stock") as number
        return (
          <Badge className={getStockColor(stock)}>
            {stock} unidades
          </Badge>
        )
      },
    },
    {
      accessorKey: "is_active",
      header: "Status",
      cell: ({ row }) => {
        const isActive = row.getValue("is_active") as boolean
        return (
          <Badge className={getStatusColor(isActive)}>
            {isActive ? "Ativo" : "Inativo"}
          </Badge>
        )
      },
    },
    {
      accessorKey: "created_at",
      header: "Criado em",
      cell: ({ row }) => {
        const dateValue = row.getValue("created_at") as string
        return <div>{dateValue || "Data não disponível"}</div>
      },
    },
    {
      id: "actions",
      enableHiding: false,
      cell: ({ row }) => {
        const product = row.original

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Abrir menu</span>
                <EllipsisVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => handleViewDetails(product.id?.toString() || '')}>
                <Eye className="mr-2 h-4 w-4" />
                Ver detalhes
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <ProductEditDialog product={product} onEditProduct={onEditProduct} />
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem asChild>
                <DeleteProductDialog product={product} onDeleteProduct={onDeleteProduct} />
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        )
      },
    },
  ]

  const table = useReactTable({
    data: products,
    columns,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onColumnVisibilityChange: setColumnVisibility,
    onRowSelectionChange: setRowSelection,
    onGlobalFilterChange: setGlobalFilter,
    globalFilterFn: "includesString",
    state: {
      sorting,
      columnFilters,
      columnVisibility,
      rowSelection,
      globalFilter,
    },
  })

  return (
    <div className="w-full">
      <div className="flex items-center justify-between py-4">
        <div className="flex flex-1 items-center space-x-2">
          <div className="relative">
            <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Buscar produtos..."
              value={globalFilter ?? ""}
              onChange={(event) => setGlobalFilter(String(event.target.value))}
              className="pl-8"
            />
          </div>
          {table.getColumn("categories") && (
            <Select
              value={(table.getColumn("categories")?.getFilterValue() as string) ?? ""}
              onValueChange={(value) =>
                table.getColumn("categories")?.setFilterValue(value === "all" ? "" : value)
              }
            >
              <SelectTrigger className="w-[180px]">
                <SelectValue placeholder="Filtrar por categoria" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todas as categorias</SelectItem>
                <SelectItem value="Pizzas">Pizzas</SelectItem>
                <SelectItem value="Bebidas">Bebidas</SelectItem>
                <SelectItem value="Sobremesas">Sobremesas</SelectItem>
                <SelectItem value="Lanches">Lanches</SelectItem>
                <SelectItem value="Saladas">Saladas</SelectItem>
              </SelectContent>
            </Select>
          )}
        </div>
        <div className="flex items-center space-x-2">
          <Button onClick={() => window.location.href = '/products/new'}>
            <Plus className="mr-2 h-4 w-4" />
            Novo Produto
          </Button>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" size="sm" className="ml-auto">
                Colunas <ChevronDown className="ml-2 h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              {table
                .getAllColumns()
                .filter((column) => column.getCanHide())
                .map((column) => {
                  return (
                    <DropdownMenuCheckboxItem
                      key={column.id}
                      className="capitalize"
                      checked={column.getIsVisible()}
                      onCheckedChange={(value) =>
                        column.toggleVisibility(!!value)
                      }
                    >
                      {column.id}
                    </DropdownMenuCheckboxItem>
                  )
                })}
            </DropdownMenuContent>
          </DropdownMenu>
          <Button variant="outline" size="sm">
            <Download className="mr-2 h-4 w-4" />
            Exportar
          </Button>
        </div>
      </div>
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  return (
                    <TableHead key={header.id}>
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                            header.column.columnDef.header,
                            header.getContext()
                          )}
                    </TableHead>
                  )
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && "selected"}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="h-24 text-center"
                >
                  Nenhum produto encontrado.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
      <div className="flex items-center justify-end space-x-2 py-4">
        <div className="flex-1 text-sm text-muted-foreground">
          {table.getFilteredSelectedRowModel().rows.length} de{" "}
          {table.getFilteredRowModel().rows.length} linha(s) selecionada(s).
        </div>
        <div className="space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.previousPage()}
            disabled={!table.getCanPreviousPage()}
          >
            Anterior
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.nextPage()}
            disabled={!table.getCanNextPage()}
          >
            Próximo
          </Button>
        </div>
      </div>
    </div>
  )
}
