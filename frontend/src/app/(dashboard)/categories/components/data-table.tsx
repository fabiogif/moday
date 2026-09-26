"use client"

import { useState, useEffect } from "react"
import { useRouter } from "next/navigation"
import {
  type ColumnDef,
  type ColumnFiltersState,
  type SortingState,
  type VisibilityState,
  type Row,
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
  Ban,
  Trash2,
  Download,
  Search,
  GripVertical,
} from "lucide-react"
import {
  DndContext,
  type DragEndEvent,
  closestCenter,
  PointerSensor,
  useSensor,
  useSensors,
} from "@dnd-kit/core"
import {
  SortableContext,
  arrayMove,
  useSortable,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import { restrictToVerticalAxis } from "@dnd-kit/modifiers"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { apiClient, endpoints } from "@/lib/api-client"
import { toast } from "sonner"
import { cn } from "@/lib/utils"
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
import { Label } from "@/components/ui/label"
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
import { CategoryFormDialog, type CategoryFormValues } from "./category-form-dialog"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"

interface Category {
  id?: number
  identify: string
  name: string
  description: string
  url: string
  color?: string
  order?: number
  productCount?: number
  isActive?: boolean
  status: string
  created_at: string
  createdAt?: string
}

interface DataTableProps {
  categories: Category[]
  onDeleteCategory: (identify: string) => void | Promise<void>
  onInactivateCategory?: (identify: string) => void | Promise<void>
  onEditCategory: (category: Category) => void
  onAddCategory: (categoryData: CategoryFormValues) => void | Promise<void>
  onRefresh?: () => void | Promise<void>
}

interface DraggableRowProps {
  row: Row<Category>
  isReordering?: boolean
  canDrag?: boolean
}

function DraggableRow({ row, isReordering, canDrag = true }: DraggableRowProps) {
  const {
    attributes,
    listeners,
    setNodeRef,
    setActivatorNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({
    id: row.original.identify,
    disabled: !canDrag || isReordering,
  })

  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.6 : undefined,
  }

  return (
    <TableRow
      ref={setNodeRef}
      style={style}
      data-state={row.getIsSelected() && "selected"}
      className={cn(
        "bg-background transition-shadow",
        isDragging && "shadow-lg ring-2 ring-primary/40 z-10 relative"
      )}
    >
      {row.getVisibleCells().map((cell) => {
        if (cell.column.id === "drag-handle") {
          return (
            <TableCell key={cell.id} className="w-12 text-center p-2">
              <Button
                type="button"
                variant="ghost"
                size="icon"
                ref={canDrag && !isReordering ? setActivatorNodeRef : undefined}
                {...(canDrag && !isReordering ? listeners : {})}
                {...(canDrag && !isReordering ? attributes : {})}
                disabled={!canDrag || isReordering}
                className={cn(
                  "h-8 w-8 cursor-grab active:cursor-grabbing",
                  (!canDrag || isReordering) && "cursor-not-allowed opacity-40"
                )}
                aria-label={`Reordenar categoria ${row.original.name}`}
                title={!canDrag ? "Limpe os filtros para reorganizar" : "Arrastar para reordenar"}
              >
                <GripVertical className="h-4 w-4 text-muted-foreground" />
              </Button>
            </TableCell>
          )
        }

        return (
          <TableCell key={cell.id}>
            {flexRender(cell.column.columnDef.cell, cell.getContext())}
          </TableCell>
        )
      })}
    </TableRow>
  )
}

export function DataTable({ categories, onDeleteCategory, onInactivateCategory, onEditCategory, onAddCategory, onRefresh }: DataTableProps) {
  const router = useRouter()
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})
  const [globalFilter, setGlobalFilter] = useState("")
  const [categoryToInactivate, setCategoryToInactivate] = useState<Category | null>(null)
  const [categoryToDelete, setCategoryToDelete] = useState<Category | null>(null)

  const getStatusColor = (isActive: boolean) => {
    return isActive 
      ? "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
      : "text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20"
  }

  const handleViewDetails = (categoryId: string) => {
    router.push(`/categories/${categoryId}`)
  }

  const columns: ColumnDef<Category>[] = [
    {
      id: "drag-handle",
      header: () => <span className="sr-only">Mover</span>,
      cell: () => null,
      enableSorting: false,
      enableHiding: false,
      size: 48,
    },
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
      header: "Categoria",
      cell: ({ row }) => (
        <div className="flex items-center gap-3">
          <span 
            className="h-3.5 w-3.5 rounded-full border border-border shrink-0" 
            style={{ backgroundColor: row.original.color || '#6B7280' }}
            aria-hidden
          />
          <div className="flex flex-col">
            <span className="font-medium text-foreground">{row.getValue("name")}</span>
            {row.original.description && (
              <span className="text-xs text-muted-foreground line-clamp-1 max-w-[280px]">
                {row.original.description}
              </span>
            )}
          </div>
        </div>
      ),
    },
    {
      accessorKey: "order",
      header: "Ordem",
      cell: ({ row }) => (
        <div className="text-center font-medium">{row.original.order ?? 0}</div>
      ),
    },
    {
      accessorKey: "description",
      header: "Descrição",
      cell: ({ row }) => (
        <div className="max-w-[200px] truncate">{row.getValue("description")}</div>
      ),
    },
    {
      accessorKey: "productCount",
      header: "Produtos",
      cell: ({ row }) => (
        <div className="text-center">{row.original.productCount || 0}</div>
      ),
    },
    {
      accessorKey: "status",
      header: "Status",
      cell: ({ row }) => {
        const status = row.getValue("status") as string
        const isActive = status === "A"
        return (
          <Badge className={getStatusColor(isActive)}>
            {isActive ? "Ativa" : "Inativa"}
          </Badge>
        )
      },
    },
    {
      accessorKey: "created_at",
      header: "Criada em",
      cell: ({ row }) => {
        const dateString = row.getValue("created_at") as string
        // Se for no formato DD/MM/YYYY, usar diretamente
        if (dateString && dateString.includes('/')) {
          return <div>{dateString}</div>
        }
        // Caso contrário, tentar converter
        try {
          const date = new Date(dateString)
          return <div>{date.toLocaleDateString("pt-BR")}</div>
        } catch {
          return <div>{dateString}</div>
        }
      },
    },
    {
      id: "actions",
      enableHiding: false,
      cell: ({ row }) => {
        const category = row.original

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <EllipsisVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => handleViewDetails(category.identify)}>
                <Eye className="mr-2 h-4 w-4" />
                Ver detalhes
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => onEditCategory(category)}>
                <Pencil className="mr-2 h-4 w-4" />
                Editar
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                onSelect={(e) => {
                  e.preventDefault()
                  setCategoryToInactivate(category)
                }}
                disabled={category.status === "I"}
              >
                <Ban className="mr-2 h-4 w-4 text-orange-600" />
                Inativar
              </DropdownMenuItem>
              <DropdownMenuItem
                onSelect={(e) => {
                  e.preventDefault()
                  setCategoryToDelete(category)
                }}
                className="text-red-600 focus:text-red-600"
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Excluir
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        )
      },
    },
  ]

  const [data, setData] = useState<Category[]>(categories)
  const [isReordering, setIsReordering] = useState(false)

  useEffect(() => {
    setData(categories)
  }, [categories])

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    })
  )

  const isFiltered = Boolean(globalFilter || columnFilters.length > 0)

  const handleDragEnd = async (event: DragEndEvent) => {
    const { active, over } = event

    if (!over || active.id === over.id) {
      return
    }

    const activeId = String(active.id)
    const overId = String(over.id)

    const oldIndex = data.findIndex((cat) => cat.identify === activeId)
    const newIndex = data.findIndex((cat) => cat.identify === overId)

    if (oldIndex === -1 || newIndex === -1) {
      return
    }

    const previousData = [...data]
    const reordered = arrayMove(data, oldIndex, newIndex).map((cat, idx) => ({
      ...cat,
      order: idx + 1,
    }))

    setData(reordered)
    setIsReordering(true)

    try {
      await apiClient.post(endpoints.categories.reorder, {
        order: reordered.map((cat) => cat.identify),
      })
      toast.success("Ordem atualizada com sucesso")
      if (onRefresh) {
        await onRefresh()
      }
    } catch (error: unknown) {
      setData(previousData)
      const err = error as { response?: { data?: { message?: string } }; message?: string }
      toast.error(err?.response?.data?.message || err?.message || "Não foi possível atualizar a ordem")
      setIsReordering(false)
    }
  }

  const table = useReactTable({
    data,
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
              placeholder="Buscar categorias..."
              value={globalFilter ?? ""}
              onChange={(event) => setGlobalFilter(String(event.target.value))}
              className="pl-8"
            />
          </div>
          {table.getColumn("status") && (
            <Select
              value={(table.getColumn("status")?.getFilterValue() as string) ?? ""}
              onValueChange={(value) =>
                table.getColumn("status")?.setFilterValue(value === "all" ? "" : value)
              }
            >
              <SelectTrigger className="w-[180px]">
                <SelectValue placeholder="Filtrar por status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todos os status</SelectItem>
                <SelectItem value="A">Ativa</SelectItem>
                <SelectItem value="I">Inativa</SelectItem>
              </SelectContent>
            </Select>
          )}
        </div>
        <div className="flex items-center space-x-2">
          <CategoryFormDialog onAddCategory={onAddCategory} />
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
      <Card className="mt-4">
        <CardHeader className="flex flex-col gap-1 pb-3">
          <div className="flex items-center justify-between gap-4">
            <div>
              <CardTitle className="text-lg font-semibold">Categorias Cadastradas</CardTitle>
              <CardDescription>
                Organize a ordem arrastando as linhas da tabela
                {isFiltered && (
                  <span className="ml-2 text-xs text-amber-600 dark:text-amber-400 font-medium">
                    (Limpe a busca/filtros para reordenar)
                  </span>
                )}
              </CardDescription>
            </div>
            {isReordering && (
              <Badge variant="outline" className="text-xs animate-pulse">
                Atualizando ordem...
              </Badge>
            )}
          </div>
        </CardHeader>
        <CardContent className="p-0">
          <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            modifiers={[restrictToVerticalAxis]}
            onDragEnd={handleDragEnd}
          >
            <div className="overflow-x-auto">
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
                    <SortableContext
                      items={data.map((cat) => cat.identify)}
                      strategy={verticalListSortingStrategy}
                    >
                      {table.getRowModel().rows.map((row) => (
                        <DraggableRow
                          key={row.original.identify}
                          row={row}
                          isReordering={isReordering}
                          canDrag={!isFiltered}
                        />
                      ))}
                    </SortableContext>
                  ) : (
                    <TableRow>
                      <TableCell
                        colSpan={columns.length}
                        className="h-24 text-center"
                      >
                        Nenhuma categoria encontrada. {Array.isArray(categories) ? `(${categories.length} categorias carregadas)` : "Carregando..."}
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </DndContext>
        </CardContent>
      </Card>
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

      <AlertDialog
        open={!!categoryToInactivate}
        onOpenChange={(open) => !open && setCategoryToInactivate(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar inativação</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja inativar a categoria{" "}
              <strong>{categoryToInactivate?.name}</strong>? Ela deixará de aparecer nas
              listagens ativas e no cardápio.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (categoryToInactivate) {
                  const action = onInactivateCategory ?? onDeleteCategory
                  action(categoryToInactivate.identify)
                  setCategoryToInactivate(null)
                }
              }}
              className="bg-orange-600 text-white hover:bg-orange-700"
            >
              Inativar
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog
        open={!!categoryToDelete}
        onOpenChange={(open) => !open && setCategoryToDelete(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir a categoria{" "}
              <strong>{categoryToDelete?.name}</strong>? Ela será excluída logicamente do sistema.
              A exclusão será bloqueada se houver produtos ativos vinculados.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (categoryToDelete) {
                  onDeleteCategory(categoryToDelete.identify)
                  setCategoryToDelete(null)
                }
              }}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}