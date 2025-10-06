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
  Eye,
  Pencil,
  Trash2,
  Download,
  Search,
  Key,
  UserCog,
} from "lucide-react"

import { Avatar, AvatarFallback } from "@/components/ui/avatar"
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
import { UserFormDialog } from "./user-form-dialog"
import { ChangePasswordDialog } from "./change-password-dialog"
import { AssignProfileDialog } from "./assign-profile-dialog"

interface Profile {
  id: number
  name: string
  description: string
  is_active: boolean
}

interface User {
  id: number
  name: string
  email: string
  phone?: string
  avatar?: string
  is_active: boolean
  created_at: string
  updated_at: string
  profiles?: Profile[]
}

interface UserFormValues {
  name: string
  email: string
  password: string
  password_confirmation: string
  phone?: string
  is_active: boolean
}

interface DataTableProps {
  users: User[]
  onDeleteUser: (id: number) => void
  onEditUser: (id: number, user: UserFormValues) => void
  onAddUser: (userData: UserFormValues) => void
  onRefresh: () => void
}

export function DataTable({ users, onDeleteUser, onEditUser, onAddUser, onRefresh }: DataTableProps) {
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})
  const [globalFilter, setGlobalFilter] = useState("")
  
  // State for dialogs
  const [changePasswordDialog, setChangePasswordDialog] = useState<{
    open: boolean
    user: User | null
  }>({ open: false, user: null })
  
  const [assignProfileDialog, setAssignProfileDialog] = useState<{
    open: boolean
    user: User | null
  }>({ open: false, user: null })

  const [editUserDialog, setEditUserDialog] = useState<{
    open: boolean
    user: User | null
  }>({ open: false, user: null })

  const getStatusColor = (isActive: boolean) => {
    return isActive
      ? "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20"
      : "text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20"
  }

  const generateAvatar = (name: string) => {
    const names = name.split(" ")
    if (names.length >= 2) {
      return `${names[0][0]}${names[1][0]}`.toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
  }

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString("pt-BR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  const exactFilter = (row: Row<User>, columnId: string, value: string) => {
    return row.getValue(columnId) === value
  }

  const columns: ColumnDef<User>[] = [
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
      header: "Usuário",
      cell: ({ row }) => {
        const user = row.original
        return (
          <div className="flex items-center gap-3">
            <Avatar className="h-8 w-8">
              <AvatarFallback className="text-xs font-medium">
                {user.avatar || generateAvatar(user.name)}
              </AvatarFallback>
            </Avatar>
            <div className="flex flex-col">
              <span className="font-medium">{user.name}</span>
              <span className="text-sm text-muted-foreground">{user.email}</span>
            </div>
          </div>
        )
      },
    },
    {
      accessorKey: "profiles",
      header: "Perfis",
      cell: ({ row }) => {
        const profiles = row.original.profiles || []
        if (profiles.length === 0) {
          return <span className="text-sm text-muted-foreground">Nenhum perfil</span>
        }
        return (
          <div className="flex flex-wrap gap-1">
            {profiles.map((profile) => (
              <Badge
                key={profile.id}
                variant="secondary"
                className="text-xs"
              >
                {profile.name}
              </Badge>
            ))}
          </div>
        )
      },
    },
    {
      accessorKey: "is_active",
      header: "Status",
      cell: ({ row }) => {
        const isActive = row.getValue("is_active") as boolean
        return (
          <Badge variant="secondary" className={getStatusColor(isActive)}>
            {isActive ? "Ativo" : "Inativo"}
          </Badge>
        )
      },
      filterFn: exactFilter,
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
        const user = row.original
        return (
          <div className="flex items-center gap-2">
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8 cursor-pointer"
              onClick={() => setEditUserDialog({ open: true, user })}
              title="Editar usuário"
            >
              <Pencil className="size-4" />
              <span className="sr-only">Editar usuário</span>
            </Button>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="h-8 w-8 cursor-pointer">
                  <EllipsisVertical className="size-4" />
                  <span className="sr-only">Mais ações</span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem
                  className="cursor-pointer"
                  onClick={() =>
                    setChangePasswordDialog({ open: true, user })
                  }
                >
                  <Key className="mr-2 size-4" />
                  Alterar Senha
                </DropdownMenuItem>
                <DropdownMenuItem
                  className="cursor-pointer"
                  onClick={() =>
                    setAssignProfileDialog({ open: true, user })
                  }
                >
                  <UserCog className="mr-2 size-4" />
                  Vincular Perfil
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  variant="destructive"
                  className="cursor-pointer"
                  onClick={() => onDeleteUser(user.id)}
                >
                  <Trash2 className="mr-2 size-4" />
                  Excluir Usuário
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        )
      },
    },
  ]

  const table = useReactTable({
    data: users,
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

  const statusFilter = table.getColumn("is_active")?.getFilterValue() as boolean | undefined

  return (
    <>
      <div className="w-full space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex flex-1 items-center space-x-2">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder="Buscar usuários..."
                value={globalFilter ?? ""}
                onChange={(event) => setGlobalFilter(String(event.target.value))}
                className="pl-9"
              />
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <Button variant="outline" className="cursor-pointer" onClick={onRefresh}>
              <Download className="mr-2 size-4" />
              Atualizar
            </Button>
            <UserFormDialog onAddUser={onAddUser} />
          </div>
        </div>

        <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
          <div className="space-y-2">
            <Label htmlFor="status-filter" className="text-sm font-medium">
              Status
            </Label>
            <Select
              value={statusFilter === undefined ? "all" : statusFilter ? "true" : "false"}
              onValueChange={(value) =>
                table
                  .getColumn("is_active")
                  ?.setFilterValue(value === "all" ? undefined : value === "true")
              }
            >
              <SelectTrigger className="cursor-pointer w-full" id="status-filter">
                <SelectValue placeholder="Selecione o Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todos os Status</SelectItem>
                <SelectItem value="true">Ativo</SelectItem>
                <SelectItem value="false">Inativo</SelectItem>
              </SelectContent>
            </Select>
          </div>
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
                  No results.
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

    {/* Change Password Dialog */}
    {changePasswordDialog.user && (
      <ChangePasswordDialog
        userId={changePasswordDialog.user.id}
        userName={changePasswordDialog.user.name}
        open={changePasswordDialog.open}
        onOpenChange={(open) =>
          setChangePasswordDialog({ open, user: open ? changePasswordDialog.user : null })
        }
        onSuccess={onRefresh}
      />
    )}

    {/* Assign Profile Dialog */}
    {assignProfileDialog.user && (
      <AssignProfileDialog
        userId={assignProfileDialog.user.id}
        userName={assignProfileDialog.user.name}
        currentProfiles={assignProfileDialog.user.profiles}
        open={assignProfileDialog.open}
        onOpenChange={(open) =>
          setAssignProfileDialog({ open, user: open ? assignProfileDialog.user : null })
        }
        onSuccess={onRefresh}
      />
    )}

    {/* Edit User Dialog */}
    {editUserDialog.user && (
      <UserFormDialog
        onAddUser={onAddUser}
        onEditUser={onEditUser}
        editingUser={editUserDialog.user}
        open={editUserDialog.open}
        onOpenChange={(open) =>
          setEditUserDialog({ open, user: open ? editUserDialog.user : null })
        }
      />
    )}
  </>
  )
}
