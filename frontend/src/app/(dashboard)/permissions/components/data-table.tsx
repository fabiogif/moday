"use client"

import { useState } from "react"
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
  Pencil,
  Trash2,
  Download,
  Search,
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
import { PermissionFormDialog } from "./permission-form-dialog"

interface Permission {
  id: number
  name: string
  slug: string
  description?: string
  module?: string
  action?: string
  resource?: string
  created_at: string
  updated_at: string
}

interface PermissionFormValues {
  name: string
  slug?: string
  description?: string
  module?: string
  action?: string
  resource?: string
}

interface DataTableProps {
  permissions: Permission[]
  onDeletePermission: (id: number) => void
  onEditPermission: (id: number, permissionData: PermissionFormValues) => void
  onAddPermission: (permissionData: PermissionFormValues) => void
  onRefresh?: () => void
}

export function DataTable({
  permissions,
  onDeletePermission,
  onEditPermission,
  onAddPermission,
  onRefresh,
}: DataTableProps) {
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})
  const [globalFilter, setGlobalFilter] = useState("")
  
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [permissionToDelete, setPermissionToDelete] = useState<Permission | null>(null)
  const [editPermissionDialog, setEditPermissionDialog] = useState<{
    open: boolean
    permission: Permission | null
  }>({ open: false, permission: null })

  const handleDeleteClick = (permission: Permission) => {
    setPermissionToDelete(permission)
    setDeleteDialogOpen(true)
  }

  const handleDeleteConfirm = async () => {
    if (permissionToDelete) {
      await onDeletePermission(permissionToDelete.id)
      setDeleteDialogOpen(false)
      setPermissionToDelete(null)
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const getPermissionModule = (module?: string) => {
    if (!module) return <Badge variant="secondary">-</Badge>
    
    const moduleColors: Record<string, string> = {
      users: "bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400",
      profiles: "bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400",
      permissions: "bg-pink-100 text-pink-800 dark:bg-pink-900/20 dark:text-pink-400",
      clients: "bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400",
      products: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400",
      categories: "bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400",
      tables: "bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400",
    }
    
    return (
      <Badge variant="secondary" className={moduleColors[module] || "bg-gray-100 text-gray-800"}>
        {module}
      </Badge>
    )
  }

  const columns: ColumnDef<Permission>[] = [
    {
      id: "select",
      header: ({ table }) => (
        <div className="flex items-center justify-center px-2">
          <Checkbox
            checked={
              table.getIsAllPageRowsSelected() ||
              (table.getIsSomePageRowsSelected() && "indeterminate")
            }
            onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
            aria-label="Select all"
          />
        </div>
      ),
      cell: ({ row }) => (
        <div className="flex items-center justify-center px-2">
          <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={(value) => row.toggleSelected(!!value)}
            aria-label="Select row"
          />
        </div>
      ),
      enableSorting: false,
      enableHiding: false,
      size: 50,
    },
    {
      accessorKey: "name",
      header: "Permissão",
      cell: ({ row }) => {
        const permission = row.original
        return (
          <div className="flex flex-col">
            <span className="font-medium">{permission.name}</span>
            {permission.slug && (
              <span className="text-sm text-muted-foreground">
                {permission.slug}
              </span>
            )}
          </div>
        )
      },
    },
    {
      accessorKey: "module",
      header: "Módulo",
      cell: ({ row }) => getPermissionModule(row.original.module),
    },
    {
      accessorKey: "action",
      header: "Ação",
      cell: ({ row }) => {
        const action = row.original.action
        if (!action) return <span className="text-sm text-muted-foreground">-</span>
        return <span className="text-sm">{action}</span>
      },
    },
    {
      accessorKey: "description",
      header: "Descrição",
      cell: ({ row }) => {
        const description = row.original.description
        if (!description) return <span className="text-sm text-muted-foreground">-</span>
        return (
          <span className="text-sm truncate max-w-[200px] block" title={description}>
            {description}
          </span>
        )
      },
    },
    {
      accessorKey: "created_at",
      header: "Data de Criação",
      cell: ({ row }) => {
        const date = row.getValue("created_at") as string
        return <span className="text-sm">{formatDate(date)}</span>
      },
    },
    {
      accessorKey: "updated_at",
      header: "Data de Alteração",
      cell: ({ row }) => {
        const date = row.getValue("updated_at") as string
        return <span className="text-sm">{formatDate(date)}</span>
      },
    },
    {
      id: "actions",
      header: "Ações",
      cell: ({ row }) => {
        const permission = row.original
        return (
          <div className="flex items-center gap-2">
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8 cursor-pointer"
              onClick={() => setEditPermissionDialog({ open: true, permission })}
              title="Editar permissão"
            >
              <Pencil className="size-4" />
              <span className="sr-only">Editar permissão</span>
            </Button>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="h-8 w-8 cursor-pointer">
                  <EllipsisVertical className="size-4" />
                  <span className="sr-only">Mais ações</span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  variant="destructive"
                  className="cursor-pointer"
                  onClick={() => handleDeleteClick(permission)}
                >
                  <Trash2 className="mr-2 size-4" />
                  Excluir Permissão
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        )
      },
    },
  ]

  const table = useReactTable({
    data: permissions,
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
    state: {
      sorting,
      columnFilters,
      columnVisibility,
      rowSelection,
      globalFilter,
    },
  })

  return (
    <>
      <div className="w-full space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex flex-1 items-center space-x-2">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder="Buscar permissões..."
                value={globalFilter ?? ""}
                onChange={(event) => setGlobalFilter(String(event.target.value))}
                className="pl-9"
              />
            </div>
          </div>
          <div className="flex items-center space-x-2">
            {onRefresh && (
              <Button variant="outline" className="cursor-pointer" onClick={onRefresh}>
                <Download className="mr-2 size-4" />
                Atualizar
              </Button>
            )}
            <PermissionFormDialog onAddPermission={onAddPermission} />
          </div>
        </div>

        <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
          <div className="space-y-2">
            <Label htmlFor="column-visibility" className="text-sm font-medium">
              Visibilidade das Colunas
            </Label>
            <DropdownMenu>
              <DropdownMenuTrigger asChild id="column-visibility">
                <Button variant="outline" className="cursor-pointer w-full">
                  Colunas <ChevronDown className="ml-2 size-4" />
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
                        onCheckedChange={(value) => column.toggleVisibility(!!value)}
                      >
                        {column.id}
                      </DropdownMenuCheckboxItem>
                    )
                  })}
              </DropdownMenuContent>
            </DropdownMenu>
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
                    Nenhum resultado.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>

        <div className="flex items-center justify-between space-x-2 py-4">
          <div className="flex items-center space-x-2">
            <Label htmlFor="page-size" className="text-sm font-medium">
              Mostrar
            </Label>
            <Select
              value={`${table.getState().pagination.pageSize}`}
              onValueChange={(value) => {
                table.setPageSize(Number(value))
              }}
            >
              <SelectTrigger className="w-20 cursor-pointer" id="page-size">
                <SelectValue placeholder={table.getState().pagination.pageSize} />
              </SelectTrigger>
              <SelectContent side="top">
                {[10, 20, 30, 40, 50].map((pageSize) => (
                  <SelectItem key={pageSize} value={`${pageSize}`}>
                    {pageSize}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="flex-1 text-sm text-muted-foreground hidden sm:block">
            {table.getFilteredSelectedRowModel().rows.length} de{" "}
            {table.getFilteredRowModel().rows.length} linha(s) selecionada(s).
          </div>
          <div className="flex items-center space-x-6 lg:space-x-8">
            <div className="flex items-center space-x-2 hidden sm:block">
              <p className="text-sm font-medium">Página</p>
              <strong className="text-sm">
                {table.getState().pagination.pageIndex + 1} de{" "}
                {table.getPageCount()}
              </strong>
            </div>
            <div className="flex items-center space-x-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => table.previousPage()}
                disabled={!table.getCanPreviousPage()}
                className="cursor-pointer"
              >
                Anterior
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => table.nextPage()}
                disabled={!table.getCanNextPage()}
                className="cursor-pointer"
              >
                Próxima
              </Button>
            </div>
          </div>
        </div>
      </div>

      {/* Delete Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir Permissão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir a permissão "{permissionToDelete?.name}"? 
              Esta ação não pode ser desfeita.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteConfirm}
              className="bg-red-600 hover:bg-red-700"
            >
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Edit Permission Dialog */}
      {editPermissionDialog.permission && (
        <PermissionFormDialog
          onAddPermission={onAddPermission}
          onEditPermission={onEditPermission}
          editingPermission={editPermissionDialog.permission}
          open={editPermissionDialog.open}
          onOpenChange={(open) =>
            setEditPermissionDialog({ open, permission: open ? editPermissionDialog.permission : null })
          }
        />
      )}
    </>
  )
}
