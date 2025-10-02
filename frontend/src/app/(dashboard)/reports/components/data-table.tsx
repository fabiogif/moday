"use client"

import { useState } from "react"
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
  Play,
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
import { ReportFormDialog } from "./report-form-dialog"

interface Report {
  id: number
  name: string
  type: string
  description: string
  lastGenerated: string
  status: string
  isActive: boolean
  createdAt: string
}

interface ReportFormValues {
  name: string
  type: string
  description: string
  isActive: boolean
}

interface DataTableProps {
  reports: Report[]
  onDeleteReport: (id: number) => void
  onEditReport: (report: Report) => void
  onAddReport: (reportData: ReportFormValues) => void
}

export function DataTable({ reports, onDeleteReport, onEditReport, onAddReport }: DataTableProps) {
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})
  const [globalFilter, setGlobalFilter] = useState("")

  const getStatusColor = (status: string) => {
    switch (status) {
      case "Gerado":
        return "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
      case "Não Gerado":
        return "text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20"
      case "Em Processamento":
        return "text-orange-600 bg-orange-50 dark:text-orange-400 dark:bg-orange-900/20"
      default:
        return "text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20"
    }
  }

  const getTypeColor = (type: string) => {
    switch (type) {
      case "Vendas":
        return "text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20"
      case "Clientes":
        return "text-purple-600 bg-purple-50 dark:text-purple-400 dark:bg-purple-900/20"
      case "Produtos":
        return "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
      case "Financeiro":
        return "text-yellow-600 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20"
      case "Mesas":
        return "text-pink-600 bg-pink-50 dark:text-pink-400 dark:bg-pink-900/20"
      default:
        return "text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20"
    }
  }

  const columns: ColumnDef<Report>[] = [
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
        <div>
          <div className="font-medium">{row.getValue("name")}</div>
          <div className="text-sm text-muted-foreground max-w-[200px] truncate">
            {row.original.description}
          </div>
        </div>
      ),
    },
    {
      accessorKey: "type",
      header: "Tipo",
      cell: ({ row }) => {
        const type = row.getValue("type") as string
        return (
          <Badge className={getTypeColor(type)}>
            {type}
          </Badge>
        )
      },
    },
    {
      accessorKey: "status",
      header: "Status",
      cell: ({ row }) => {
        const status = row.getValue("status") as string
        return (
          <Badge className={getStatusColor(status)}>
            {status}
          </Badge>
        )
      },
    },
    {
      accessorKey: "lastGenerated",
      header: "Última Geração",
      cell: ({ row }) => {
        const lastGenerated = row.getValue("lastGenerated") as string
        return lastGenerated ? (
          <div>{new Date(lastGenerated).toLocaleDateString("pt-BR")}</div>
        ) : (
          <div className="text-muted-foreground">Nunca</div>
        )
      },
    },
    {
      accessorKey: "isActive",
      header: "Ativo",
      cell: ({ row }) => {
        const isActive = row.getValue("isActive") as boolean
        return (
          <Badge variant={isActive ? "default" : "secondary"}>
            {isActive ? "Sim" : "Não"}
          </Badge>
        )
      },
    },
    {
      accessorKey: "createdAt",
      header: "Criado em",
      cell: ({ row }) => {
        const date = new Date(row.getValue("createdAt"))
        return <div>{date.toLocaleDateString("pt-BR")}</div>
      },
    },
    {
      id: "actions",
      enableHiding: false,
      cell: ({ row }) => {
        const report = row.original

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <EllipsisVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => navigator.clipboard.writeText(report.name)}>
                <Play className="mr-2 h-4 w-4" />
                Gerar Relatório
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => navigator.clipboard.writeText(report.name)}>
                <Eye className="mr-2 h-4 w-4" />
                Ver detalhes
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => onEditReport(report)}>
                <Pencil className="mr-2 h-4 w-4" />
                Editar
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                onClick={() => onDeleteReport(report.id)}
                className="text-red-600"
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
    data: reports,
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
              placeholder="Buscar relatórios..."
              value={globalFilter ?? ""}
              onChange={(event) => setGlobalFilter(String(event.target.value))}
              className="pl-8"
            />
          </div>
          {table.getColumn("type") && (
            <Select
              value={(table.getColumn("type")?.getFilterValue() as string) ?? ""}
              onValueChange={(value) =>
                table.getColumn("type")?.setFilterValue(value === "all" ? "" : value)
              }
            >
              <SelectTrigger className="w-[180px]">
                <SelectValue placeholder="Filtrar por tipo" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todos os tipos</SelectItem>
                <SelectItem value="Vendas">Vendas</SelectItem>
                <SelectItem value="Clientes">Clientes</SelectItem>
                <SelectItem value="Produtos">Produtos</SelectItem>
                <SelectItem value="Financeiro">Financeiro</SelectItem>
                <SelectItem value="Mesas">Mesas</SelectItem>
              </SelectContent>
            </Select>
          )}
        </div>
        <div className="flex items-center space-x-2">
          <ReportFormDialog onAddReport={onAddReport} />
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
                  Nenhum relatório encontrado.
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
