"use client"

import { useMemo, useState } from "react"
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
  ArrowRight,
  ChevronDown,
  EllipsisVertical,
  Eye,
  Pencil,
  Trash2,
  Download,
  Search,
  Receipt,
  Printer,
  XCircle,
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
import { Order } from "../types"
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
import {
  canEditOrder,
  getNextStatus,
  resolveBulkAdvanceSelection,
} from "@/lib/order-status"

interface DataTableProps {
  orders: Order[]
  onDeleteOrder: (order: Order) => void
  onEditOrder: (order: Order) => void
  onViewOrder: (order: Order) => void
  onInvoiceOrder?: (order: Order) => void
  onReceiptOrder: (order: Order) => void
  onBulkDelete?: (orderIds: string[]) => Promise<void>
  onBulkUpdateStatus?: (orderIds: string[], status: string) => Promise<void>
}

export function DataTable({
  orders,
  onDeleteOrder,
  onEditOrder,
  onViewOrder,
  onInvoiceOrder,
  onReceiptOrder,
  onBulkDelete,
  onBulkUpdateStatus,
}: DataTableProps) {
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})
  const [globalFilter, setGlobalFilter] = useState("")
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [orderToDelete, setOrderToDelete] = useState<Order | null>(null)
  const [bulkDeleteDialogOpen, setBulkDeleteDialogOpen] = useState(false)
  const [bulkUpdateStatusDialogOpen, setBulkUpdateStatusDialogOpen] = useState(false)
  const [bulkActionLoading, setBulkActionLoading] = useState(false)
  const [singleAdvanceOrder, setSingleAdvanceOrder] = useState<Order | null>(null)

  const handleDialogOpenChange = (open: boolean) => {
    setDeleteDialogOpen(open)
    if (!open) {
      setOrderToDelete(null)
    }
  }

  const handleDeleteClick = (order: Order) => {
    setOrderToDelete(order)
    setDeleteDialogOpen(true)
  }

  const handleCancelDelete = () => {
    setDeleteDialogOpen(false)
    setOrderToDelete(null)
  }

  const handleConfirmDelete = async () => {
    if (!orderToDelete) return

    try {
      await onDeleteOrder(orderToDelete)
    } finally {
      setDeleteDialogOpen(false)
      setOrderToDelete(null)
    }
  }

  const getOrderId = (order: Order): string =>
    order.identify || order.id?.toString() || ""

  const getSelectedOrders = (): Order[] =>
    table.getFilteredSelectedRowModel().rows.map((row) => row.original)

  const getSelectedOrderIds = (): string[] =>
    getSelectedOrders()
      .map(getOrderId)
      .filter((id) => id !== "")

  const handleBulkDelete = async () => {
    if (!onBulkDelete) return

    const selectedIds = getSelectedOrderIds()
    if (selectedIds.length === 0) return

    setBulkActionLoading(true)
    try {
      await onBulkDelete(selectedIds)
      setRowSelection({})
    } finally {
      setBulkActionLoading(false)
      setBulkDeleteDialogOpen(false)
    }
  }

  const handleBulkUpdateStatus = async () => {
    if (!onBulkUpdateStatus || bulkAdvance.kind !== "ready") return

    const selectedIds = getSelectedOrderIds()
    if (selectedIds.length === 0) return

    setBulkActionLoading(true)
    try {
      await onBulkUpdateStatus(selectedIds, bulkAdvance.nextStatus)
      setRowSelection({})
    } finally {
      setBulkActionLoading(false)
      setBulkUpdateStatusDialogOpen(false)
    }
  }

  const handleConfirmSingleAdvance = async () => {
    if (!onBulkUpdateStatus || !singleAdvanceOrder) return

    const nextStatus = getNextStatus(singleAdvanceOrder.status)
    const orderId = getOrderId(singleAdvanceOrder)
    if (!nextStatus || !orderId) return

    setBulkActionLoading(true)
    try {
      await onBulkUpdateStatus([orderId], nextStatus)
    } finally {
      setBulkActionLoading(false)
      setSingleAdvanceOrder(null)
    }
  }

  const getStatusColor = (status: string) => {
    const lowerStatus = status?.toLowerCase()
    switch (lowerStatus) {
      case "concluído":
      case "concluido":
        return "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
      case "pendente":
        return "text-orange-600 bg-orange-50 dark:text-orange-400 dark:bg-orange-900/20"
      case "aceito":
        return "text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/20"
      case "preparo":
        return "text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20"
      case "cancelado":
        return "text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20"
      default:
        return "text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20"
    }
  }

  const columns: ColumnDef<Order>[] = [
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
      accessorKey: "identify",
      header: "Número do Pedido",
      cell: ({ row }) => {
        const identify = row.getValue("identify") as string
        const orderNumber = row.original.orderNumber || identify
        return <div className="font-medium">{orderNumber || "N/A"}</div>
      },
    },
    {
      accessorKey: "client",
      header: "Cliente",
      cell: ({ row }) => {
        const client = row.getValue("client") as { name?: string; email?: string } | null
        return (
          <div>
            <div className="font-medium">
              {client?.name ||
                row.original?.client?.name ||
                row.original?.customerName ||
                "Nome não informado"}
            </div>
            <div className="text-sm text-muted-foreground">
              {client?.email ||
                row.original?.client?.email ||
                row.original?.customerEmail ||
                "Email não informado"}
            </div>
          </div>
        )
      },
    },
    {
      accessorKey: "status",
      header: "Status",
      cell: ({ row }) => {
        const status = row.getValue("status") as string
        return <Badge className={getStatusColor(status)}>{status}</Badge>
      },
    },
    {
      accessorKey: "total",
      header: "Total",
      cell: ({ row }) => {
        const total = parseFloat(row.getValue("total"))
        const isTest = total < 1000 && !Number.isInteger(total)
        const formatted = new Intl.NumberFormat(isTest ? "en-US" : "pt-BR", {
          style: "currency",
          currency: isTest ? "USD" : "BRL",
        }).format(total)
        return <div className="font-medium">{formatted}</div>
      },
    },
    {
      accessorKey: "products",
      header: "Itens",
      cell: ({ row }) => {
        const products = row.getValue("products") as unknown[]
        const itemsCount = row.original.items || products?.length || 0
        return <div className="text-center">{itemsCount}</div>
      },
    },
    {
      accessorKey: "date",
      header: "Data do Pedido",
      cell: ({ row }) => {
        const date = row.getValue("date") as string
        return <div>{date || "Data não informada"}</div>
      },
    },
    {
      id: "actions",
      enableHiding: false,
      cell: ({ row }) => {
        const order = row.original
        const nextStatus = getNextStatus(order.status)

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                <span className="sr-only">Abrir menu</span>
                <EllipsisVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => onViewOrder(order)}>
                <Eye className="mr-2 h-4 w-4" />
                Ver detalhes
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={() => onEditOrder(order)}
                disabled={!canEditOrder(order.status)}
              >
                <Pencil className="mr-2 h-4 w-4" />
                Editar
              </DropdownMenuItem>
              {onInvoiceOrder && (
                <DropdownMenuItem onClick={() => onInvoiceOrder(order)}>
                  <Receipt className="mr-2 h-4 w-4" />
                  Faturar
                </DropdownMenuItem>
              )}
              <DropdownMenuItem onClick={() => onReceiptOrder(order)}>
                <Printer className="mr-2 h-4 w-4" />
                Recibo
              </DropdownMenuItem>
              {onBulkUpdateStatus && nextStatus && (
                <DropdownMenuItem onClick={() => setSingleAdvanceOrder(order)}>
                  <ArrowRight className="mr-2 h-4 w-4" />
                  Mover para {nextStatus}
                </DropdownMenuItem>
              )}
              <DropdownMenuSeparator />
              <DropdownMenuItem
                onClick={() => handleDeleteClick(order)}
                className="text-red-600"
                disabled={!order.identify && !order.id}
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

  const table = useReactTable({
    data: orders,
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

  const selectedCount = table.getFilteredSelectedRowModel().rows.length
  const hasSelection = selectedCount > 0

  const bulkAdvance = useMemo(() => {
    const selectedStatuses = table
      .getFilteredSelectedRowModel()
      .rows.map((row) => row.original.status)
    return resolveBulkAdvanceSelection(selectedStatuses)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [rowSelection, orders, selectedCount])

  const singleAdvanceNextStatus = singleAdvanceOrder
    ? getNextStatus(singleAdvanceOrder.status)
    : null

  const actionButtonClass = "h-9 w-full sm:w-auto shrink-0"

  return (
    <div className="w-full min-w-0 space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div className="relative w-full min-w-0 sm:max-w-sm">
          <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Buscar pedidos..."
            value={globalFilter ?? ""}
            onChange={(event) => setGlobalFilter(String(event.target.value))}
            className="h-9 pl-8"
          />
        </div>
        <div className="grid grid-cols-1 gap-2 sm:flex sm:flex-wrap sm:items-center">
          {table.getColumn("status") && (
            <Select
              value={(table.getColumn("status")?.getFilterValue() as string) ?? "all"}
              onValueChange={(value) =>
                table.getColumn("status")?.setFilterValue(value === "all" ? "" : value)
              }
            >
              <SelectTrigger className="h-9 w-full sm:w-[180px]">
                <SelectValue placeholder="Filtrar por status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todos os status</SelectItem>
                <SelectItem value="Pendente">Pendente</SelectItem>
                <SelectItem value="Aceito">Aceito</SelectItem>
                <SelectItem value="Preparo">Preparo</SelectItem>
                <SelectItem value="Concluído">Concluído</SelectItem>
                <SelectItem value="Cancelado">Cancelado</SelectItem>
              </SelectContent>
            </Select>
          )}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" size="sm" className={actionButtonClass}>
                Colunas <ChevronDown className="ml-2 h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              {table
                .getAllColumns()
                .filter((column) => column.getCanHide())
                .map((column) => (
                  <DropdownMenuCheckboxItem
                    key={column.id}
                    className="capitalize"
                    checked={column.getIsVisible()}
                    onCheckedChange={(value) => column.toggleVisibility(!!value)}
                  >
                    {column.id}
                  </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
          </DropdownMenu>
          <Button variant="outline" size="sm" className={actionButtonClass}>
            <Download className="mr-2 h-4 w-4" />
            Exportar
          </Button>
        </div>
      </div>

      {hasSelection && (
        <div className="flex flex-col gap-3 rounded-lg border bg-muted/50 p-3 sm:p-4">
          <div className="min-w-0 space-y-1">
            <p className="text-sm font-medium">
              {selectedCount} pedido(s) selecionado(s)
            </p>
            {bulkAdvance.kind === "ready" && (
              <p className="text-xs text-muted-foreground">
                Status atual:{" "}
                <span className="font-medium text-foreground">
                  {bulkAdvance.currentStatus}
                </span>
              </p>
            )}
            {bulkAdvance.kind === "mixed" && (
              <p className="text-xs text-amber-700 dark:text-amber-300">
                Os pedidos possuem status distintos. Selecione pedidos com o mesmo
                status para avançar.
              </p>
            )}
            {bulkAdvance.kind === "final" && (
              <p className="text-xs text-muted-foreground">
                Status atual: {bulkAdvance.currentStatus}. Não há próximo status no
                fluxo.
              </p>
            )}
          </div>

          <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            {onBulkUpdateStatus && (
              <Button
                variant="default"
                size="sm"
                onClick={() => setBulkUpdateStatusDialogOpen(true)}
                disabled={bulkActionLoading || bulkAdvance.kind !== "ready"}
                className={`${actionButtonClass} bg-green-600 hover:bg-green-700 disabled:bg-green-600/50`}
                title={
                  bulkAdvance.kind === "mixed"
                    ? "Pedidos com status distintos"
                    : bulkAdvance.kind === "final"
                      ? "Sem próximo status no fluxo"
                      : undefined
                }
              >
                <ArrowRight className="mr-2 h-4 w-4" />
                {bulkAdvance.kind === "ready"
                  ? `Mover para ${bulkAdvance.nextStatus}`
                  : "Mover para próximo status"}
              </Button>
            )}
            {onBulkDelete && (
              <Button
                variant="destructive"
                size="sm"
                onClick={() => setBulkDeleteDialogOpen(true)}
                disabled={bulkActionLoading}
                className={actionButtonClass}
              >
                <XCircle className="mr-2 h-4 w-4" />
                Excluir Selecionados
              </Button>
            )}
            <Button
              variant="outline"
              size="sm"
              onClick={() => setRowSelection({})}
              disabled={bulkActionLoading}
              className={actionButtonClass}
            >
              Limpar Seleção
            </Button>
          </div>
        </div>
      )}

      <div className="overflow-x-auto rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => (
                  <TableHead key={header.id}>
                    {header.isPlaceholder
                      ? null
                      : flexRender(header.column.columnDef.header, header.getContext())}
                  </TableHead>
                ))}
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
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={columns.length} className="h-24 text-center">
                  Nenhum pedido encontrado.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      <div className="flex flex-col gap-3 py-2 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-sm text-muted-foreground">
          {table.getFilteredSelectedRowModel().rows.length} de{" "}
          {table.getFilteredRowModel().rows.length} linha(s) selecionada(s).
        </p>
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            className="h-9 flex-1 sm:flex-none"
            onClick={() => table.previousPage()}
            disabled={!table.getCanPreviousPage()}
          >
            Anterior
          </Button>
          <Button
            variant="outline"
            size="sm"
            className="h-9 flex-1 sm:flex-none"
            onClick={() => table.nextPage()}
            disabled={!table.getCanNextPage()}
          >
            Próximo
          </Button>
        </div>
      </div>

      <AlertDialog open={deleteDialogOpen} onOpenChange={handleDialogOpenChange}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza de que deseja excluir o pedido{" "}
              <span className="font-semibold">
                {orderToDelete?.orderNumber ||
                  orderToDelete?.identify ||
                  orderToDelete?.id}
              </span>
              ?
              <br />
              <span className="font-medium text-red-600">
                Esta ação não poderá ser desfeita.
              </span>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={handleCancelDelete}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleConfirmDelete}
              className="bg-red-600 hover:bg-red-700"
            >
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog open={bulkDeleteDialogOpen} onOpenChange={setBulkDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar exclusão em massa</AlertDialogTitle>
            <AlertDialogDescription>
              Deseja realmente realizar esta operação?
              <br />
              <span className="font-semibold">
                {selectedCount} pedido(s) serão excluído(s).
              </span>
              <br />
              <span className="font-medium text-red-600">
                Esta ação não poderá ser desfeita.
              </span>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={bulkActionLoading}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleBulkDelete}
              disabled={bulkActionLoading}
              className="bg-red-600 hover:bg-red-700"
            >
              {bulkActionLoading ? "Excluindo..." : "Excluir"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog
        open={bulkUpdateStatusDialogOpen}
        onOpenChange={setBulkUpdateStatusDialogOpen}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar atualização de status</AlertDialogTitle>
            <AlertDialogDescription>
              Deseja realmente realizar esta operação?
              <br />
              {bulkAdvance.kind === "ready" && (
                <span className="font-semibold">
                  {selectedCount} pedido(s) serão movido(s) de{" "}
                  {bulkAdvance.currentStatus} para {bulkAdvance.nextStatus}.
                </span>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={bulkActionLoading}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleBulkUpdateStatus}
              disabled={bulkActionLoading || bulkAdvance.kind !== "ready"}
              className="bg-green-600 hover:bg-green-700"
            >
              {bulkActionLoading ? "Atualizando..." : "Confirmar"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog
        open={!!singleAdvanceOrder}
        onOpenChange={(open) => {
          if (!open) setSingleAdvanceOrder(null)
        }}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar avanço de status</AlertDialogTitle>
            <AlertDialogDescription>
              {singleAdvanceOrder && singleAdvanceNextStatus && (
                <>
                  Mover o pedido{" "}
                  <span className="font-semibold">
                    {singleAdvanceOrder.orderNumber ||
                      singleAdvanceOrder.identify ||
                      singleAdvanceOrder.id}
                  </span>{" "}
                  de {singleAdvanceOrder.status} para {singleAdvanceNextStatus}?
                </>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={bulkActionLoading}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleConfirmSingleAdvance}
              disabled={bulkActionLoading || !singleAdvanceNextStatus}
              className="bg-green-600 hover:bg-green-700"
            >
              {bulkActionLoading ? "Atualizando..." : "Confirmar"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
