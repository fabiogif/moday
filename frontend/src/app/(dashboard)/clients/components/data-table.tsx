"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
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
} from "@tanstack/react-table";
import {
  ChevronDown,
  EllipsisVertical,
  Eye,
  Pencil,
  Trash2,
  Download,
  Search,
  Plus,
} from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { showErrorToast } from "@/components/ui/error-toast";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import { Combobox, ComboboxOption } from "@/components/ui/combobox";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";

interface Client {
  id: number;
  name: string;
  cpf: string;
  email: string;
  phone: string;
  address?: string;
  city?: string;
  state?: string;
  zip_code?: string;
  neighborhood?: string;
  number?: string;
  complement?: string;
  full_address?: string;
  has_complete_address?: boolean;
  total_orders: number;
  last_order?: string;
  last_order_raw?: string;
  is_active: boolean;
  created_at: string;
  created_at_formatted: string;
  updated_at: string;
}

interface ClientFormValues {
  name: string;
  cpf: string;
  email: string;
  phone: string;
  address?: string;
  city?: string;
  state?: string;
  zip_code?: string;
  neighborhood?: string;
  number?: string;
  complement?: string;
  isActive: boolean;
}

interface DataTableProps {
  clients: Client[];
  onDeleteClient: (id: number) => void;
  onEditClient: (id: number, clientData: ClientFormValues) => void;
  onAddClient: (clientData: ClientFormValues) => void;
  onOpenDialog?: () => void;
  onShowSuccessAlert?: (title: string, message: string) => void;
}

export function DataTable({
  clients,
  onDeleteClient,
  onEditClient,
  onAddClient,
  onOpenDialog,
  onShowSuccessAlert,
}: DataTableProps) {
  const router = useRouter();
  const [sorting, setSorting] = useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({});
  const [rowSelection, setRowSelection] = useState({});
  const [globalFilter, setGlobalFilter] = useState("");
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [clientToDelete, setClientToDelete] = useState<Client | null>(null);

  const getStatusColor = (isActive: boolean) => {
    return isActive
      ? "bg-green-100 text-green-800 hover:bg-green-200 border-green-300 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800"
      : "bg-red-100 text-red-800 hover:bg-red-200 border-red-300 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800";
  };

  const handleViewDetails = (clientId: number) => {
    router.push(`/clients/${clientId}`);
  };

  const handleDeleteClick = (client: Client) => {
    setClientToDelete(client);
    setDeleteDialogOpen(true);
  };

  const handleConfirmDelete = async () => {
    if (clientToDelete) {
      try {
        await onDeleteClient(clientToDelete.id);
        setDeleteDialogOpen(false);
        setClientToDelete(null);
      } catch (error) {
        const apiError = error as any;

        const title = apiError?.status === 409 ? "Ação não permitida" : "Erro ao Excluir Cliente";
        const defaultMessage = "Cliente não pode ser excluído, existe um pedido ativo ou não arquivado vinculado.";
        const formattedError = {
          ...apiError,
          message: apiError?.status === 409 ? (apiError?.message || defaultMessage) : apiError?.message,
        };

        showErrorToast(formattedError, title);
      }
    }
  };

  const handleCancelDelete = () => {
    setDeleteDialogOpen(false);
    setClientToDelete(null);
  };

  const columns: ColumnDef<Client>[] = [
    {
      id: "select",
      header: ({ table }) => (
        <Checkbox
          checked={
            table.getIsAllPageRowsSelected() ||
            (table.getIsSomePageRowsSelected() && "indeterminate")
          }
          onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
          aria-label="Selecione todos"
        />
      ),
      cell: ({ row }) => (
        <Checkbox
          checked={row.getIsSelected()}
          onCheckedChange={(value) => row.toggleSelected(!!value)}
          aria-label="Selecione..."
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
          <div className="text-sm text-muted-foreground">
            {row.original.email}
          </div>
        </div>
      ),
    },
    {
      accessorKey: "cpf",
      header: "CPF",
      cell: ({ row }) => (
        <div>
          {row.getValue("cpf") || (
            <span className="text-muted-foreground">-</span>
          )}
        </div>
      ),
    },
    {
      accessorKey: "phone",
      header: "Telefone",
      cell: ({ row }) => (
        <div>
          {row.getValue("phone") || (
            <span className="text-muted-foreground">-</span>
          )}
        </div>
      ),
    },
    {
      accessorKey: "full_address",
      header: "Endereço",
      cell: ({ row }) => {
        const fullAddress = row.original.full_address;
        return fullAddress ? (
          <div className="max-w-[200px] truncate" title={fullAddress}>
            {fullAddress}
          </div>
        ) : (
          <span className="text-muted-foreground">Não informado</span>
        );
      },
    },
    {
      accessorKey: "total_orders",
      header: "Total Pedidos",
      cell: ({ row }) => (
        <div className="text-center font-medium">
          {row.getValue("total_orders")}
        </div>
      ),
    },
    {
      accessorKey: "last_order",
      header: "Último Pedido",
      cell: ({ row }) => {
        const lastOrder = row.getValue("last_order") as string;
        return lastOrder ? (
          <div>{lastOrder}</div>
        ) : (
          <div className="text-muted-foreground">Nenhum</div>
        );
      },
    },
    {
      accessorKey: "is_active",
      header: "Status",
      cell: ({ row }) => {
        const isActive = row.getValue("is_active") as boolean;
        return (
          <Badge variant="outline" className={getStatusColor(isActive)}>
            {isActive ? "Ativo" : "Inativo"}
          </Badge>
        );
      },
    },
    {
      accessorKey: "created_at_formatted",
      header: "Cadastrado em",
      cell: ({ row }) => {
        const dateFormatted = row.getValue("created_at_formatted") as string;
        const dateRaw = row.original.created_at;

        if (dateFormatted) {
          return <div>{dateFormatted}</div>;
        }

        if (dateRaw) {
          try {
            const date = new Date(dateRaw);
            return <div>{date.toLocaleDateString("pt-BR")}</div>;
          } catch {
            return <div className="text-muted-foreground">Data inválida</div>;
          }
        }

        return <div className="text-muted-foreground">-</div>;
      },
    },
    {
      id: "actions",
      enableHiding: false,
      cell: ({ row }) => {
        const client = row.original;

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Abrir menu</span>
                <EllipsisVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem
                onClick={() => handleViewDetails(client.id)}
              >
                <Eye className="mr-2 h-4 w-4" />
                Ver detalhes
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={() =>
                  onEditClient(client.id, {
                    name: client.name,
                    cpf: client.cpf,
                    email: client.email,
                    phone: client.phone,
                    address: client.address,
                    city: client.city,
                    state: client.state,
                    zip_code: client.zip_code,
                    neighborhood: client.neighborhood,
                    number: client.number,
                    complement: client.complement,
                    isActive: client.is_active,
                  })
                }
              >
                <Pencil className="mr-2 h-4 w-4" />
                Editar
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                onClick={() => handleDeleteClick(client)}
                className="text-red-600"
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Excluir
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  const table = useReactTable({
    data: clients,
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
  });

  return (
    <div className="w-full">
      <div className="flex items-center justify-between py-4">
        <div className="flex flex-1 items-center space-x-2">
          <div className="relative">
            <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Buscar clientes..."
              value={globalFilter ?? ""}
              onChange={(event) => setGlobalFilter(String(event.target.value))}
              className="pl-8"
            />
          </div>
          {table.getColumn("is_active") && (
            <Combobox
              value={
                (table.getColumn("is_active")?.getFilterValue() as string) ?? ""
              }
              onValueChange={(value) =>
                table
                  .getColumn("is_active")
                  ?.setFilterValue(value === "all" ? "" : value)
              }
              options={[
                { value: "all", label: "Todos os status" },
                { value: "true", label: "Ativos" },
                { value: "false", label: "Inativos" },
              ]}
              placeholder="Filtrar por status"
              searchPlaceholder="Buscar status..."
              emptyText="Nenhum status encontrado"
              className="w-[180px]"
            />
          )}
        </div>
        <div className="flex items-center space-x-2">
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
                  const columnLabels: Record<string, string> = {
                    name: "Nome",
                    cpf: "CPF",
                    phone: "Telefone",
                    full_address: "Endereço",
                    total_orders: "Total de Pedido",
                    last_order: "Ultima compra",
                    is_active: "Status",
                    created_at_formatted: "Cadastrado em",
                  };
                  return (
                    <DropdownMenuCheckboxItem
                      key={column.id}
                      className="capitalize"
                      checked={column.getIsVisible()}
                      onCheckedChange={(value) =>
                        column.toggleVisibility(!!value)
                      }
                    >
                      {columnLabels[column.id] || column.id}
                    </DropdownMenuCheckboxItem>
                  );
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
                  );
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
                  Nenhum cliente encontrado.
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

      {/* AlertDialog para confirmação de exclusão */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar Exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir o cliente{" "}
              <span className="font-semibold">{clientToDelete?.name}</span>?
              <br />
              <span className="text-red-600 font-medium">
                Esta ação não pode ser desfeita.
              </span>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={handleCancelDelete}>
              Cancelar
            </AlertDialogCancel>
            <AlertDialogAction
              onClick={handleConfirmDelete}
              className="bg-red-600 hover:bg-red-700"
            >
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
