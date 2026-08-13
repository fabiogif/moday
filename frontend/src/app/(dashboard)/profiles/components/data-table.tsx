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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Checkbox } from "@/components/ui/checkbox"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuCheckboxItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
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
import { ChevronDown, EllipsisVertical, Edit, Trash2, Plus, Shield, Download, Search, Pencil } from "lucide-react"
import { ProfileFormDialog } from "./profile-form-dialog"
import { AssignPermissionsDialog } from "./assign-permissions-dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

interface Permission {
  id: number
  name: string
  slug: string
}

interface Profile {
  id: number
  name: string
  description?: string
  created_at: string
  updated_at: string
  permissions?: Permission[]
}

interface ProfileFormValues {
  name: string
  description?: string
}

interface DataTableProps {
  profiles: Profile[]
  onDeleteProfile: (id: number) => void
  onEditProfile: (id: number, profileData: ProfileFormValues) => void
  onAddProfile: (profileData: ProfileFormValues) => void
  onRefresh?: () => void
}

export function DataTable({
  profiles,
  onDeleteProfile,
  onEditProfile,
  onAddProfile,
  onRefresh,
}: DataTableProps) {
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = useState({})
  const [globalFilter, setGlobalFilter] = useState("")
  
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [profileToDelete, setProfileToDelete] = useState<Profile | null>(null)
  const [assignPermissionsOpen, setAssignPermissionsOpen] = useState(false)
  const [profileToAssignPermissions, setProfileToAssignPermissions] = useState<Profile | null>(null)
  const [editProfileDialog, setEditProfileDialog] = useState<{
    open: boolean
    profile: Profile | null
  }>({ open: false, profile: null })

  const handleDeleteClick = (profile: Profile) => {
    setProfileToDelete(profile)
    setDeleteDialogOpen(true)
  }

  const handleDeleteConfirm = async () => {
    if (profileToDelete) {
      await onDeleteProfile(profileToDelete.id)
      setDeleteDialogOpen(false)
      setProfileToDelete(null)
    }
  }

  const handleAssignPermissionsClick = (profile: Profile) => {
    setProfileToAssignPermissions(profile)
    setAssignPermissionsOpen(true)
  }

  const handlePermissionsSuccess = () => {
    if (onRefresh) {
      onRefresh()
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

  const getProfileType = (name: string) => {
    const lowerName = name.toLowerCase()
    if (lowerName.includes('admin') || lowerName.includes('gerente')) {
      return <Badge variant="destructive">Administrativo</Badge>
    }
    if (lowerName.includes('user') || lowerName.includes('cliente')) {
      return <Badge variant="default">Usuário</Badge>
    }
    return <Badge variant="secondary">Geral</Badge>
  }

  const columns: ColumnDef<Profile>[] = [
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
      header: "Nome",
      cell: ({ row }) => {
        const profile = row.original
        return (
          <div className="flex flex-col">
            <span className="font-medium">{profile.name}</span>
            {profile.description && (
              <span className="text-sm text-muted-foreground truncate max-w-[300px]">
                {profile.description}
              </span>
            )}
          </div>
        )
      },
    },
    {
      accessorKey: "type",
      header: "Tipo",
      cell: ({ row }) => getProfileType(row.original.name),
    },
    {
      accessorKey: "permissions",
      header: "Permissões",
      cell: ({ row }) => {
        const permissions = row.original.permissions || []
        if (permissions.length === 0) {
          return <span className="text-sm text-muted-foreground">Nenhuma permissão</span>
        }
        return (
          <div className="flex items-center gap-2">
            <Badge variant="secondary" className="text-xs">
              {permissions.length} {permissions.length === 1 ? 'permissão' : 'permissões'}
            </Badge>
          </div>
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
        const profile = row.original
        return (
          <div className="flex items-center gap-2">
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8 cursor-pointer"
              onClick={() => setEditProfileDialog({ open: true, profile })}
              title="Editar perfil"
            >
              <Pencil className="size-4" />
              <span className="sr-only">Editar perfil</span>
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
                  onClick={() => handleAssignPermissionsClick(profile)}
                >
                  <Shield className="mr-2 size-4" />
                  Vincular Permissões
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  variant="destructive"
                  className="cursor-pointer"
                  onClick={() => handleDeleteClick(profile)}
                >
                  <Trash2 className="mr-2 size-4" />
                  Excluir Perfil
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        )
      },
    },
  ]

  const table = useReactTable({
    data: profiles,
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
                placeholder="Buscar perfis..."
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
            <ProfileFormDialog onAddProfile={onAddProfile}>
              <Button>
                <Plus className="mr-2 h-4 w-4" />
                Novo Perfil
              </Button>
            </ProfileFormDialog>
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
                    <div className="flex flex-col items-center justify-center space-y-2">
                      <p className="text-sm text-muted-foreground">Nenhum resultado.</p>
                      <ProfileFormDialog onAddProfile={onAddProfile}>
                        <Button variant="outline" size="sm">
                          <Plus className="mr-2 h-4 w-4" />
                          Cadastrar primeiro perfil
                        </Button>
                      </ProfileFormDialog>
                    </div>
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

      {/* Dialog de Confirmação de Exclusão */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir Perfil</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir o perfil "{profileToDelete?.name}"? 
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

      {/* Dialog para Vincular Permissões */}
      <AssignPermissionsDialog
        open={assignPermissionsOpen}
        onOpenChange={setAssignPermissionsOpen}
        profile={profileToAssignPermissions}
        onSuccess={handlePermissionsSuccess}
      />

      {/* Edit Profile Dialog */}
      {editProfileDialog.profile && (
        <ProfileFormDialog
          onAddProfile={onAddProfile}
          onEditProfile={onEditProfile}
          editingProfile={editProfileDialog.profile}
          open={editProfileDialog.open}
          onOpenChange={(open) =>
            setEditProfileDialog({ open, profile: open ? editProfileDialog.profile : null })
          }
        />
      )}
    </>
  )
}
