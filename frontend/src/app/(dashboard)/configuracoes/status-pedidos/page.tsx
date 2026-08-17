"use client"

import { useState, useEffect } from "react"
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
import { Plus, Edit, Trash2, GripVertical, Save } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Switch } from "@/components/ui/switch"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
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
import { Badge } from "@/components/ui/badge"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { toast } from "sonner"
import { apiClient, endpoints } from "@/lib/api-client"
import type { OrderStatus } from "@/types/order-status"
import { StatusStatCards } from "./components/status-stat-cards"
import { cn } from "@/lib/utils"

interface SortableStatusRowProps {
  status: OrderStatus
  onEdit: (status: OrderStatus) => void
  onDelete: (uuid: string) => void
  onToggleActive: (uuid: string, isActive: boolean) => Promise<void> | void
  disableInteractions?: boolean
}

function SortableStatusRow({
  status,
  onEdit,
  onDelete,
  onToggleActive,
  disableInteractions = false,
}: SortableStatusRowProps) {
  const {
    attributes,
    listeners,
    setActivatorNodeRef,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({
    id: status.uuid,
    disabled: disableInteractions,
  })

  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.6 : undefined,
  }

  const canDrag = (status.can_reorder ?? true) && !disableInteractions

  return (
    <TableRow
      ref={setNodeRef}
      style={style}
      className={cn(
        "bg-background transition-shadow",
        isDragging && "shadow-lg ring-2 ring-primary/40"
      )}
    >
      <TableCell className="w-14">
        <Button
          variant="ghost"
          size="icon"
          ref={canDrag ? setActivatorNodeRef : undefined}
          {...(canDrag ? listeners : {})}
          {...(canDrag ? attributes : {})}
          disabled={!canDrag}
          className={cn("cursor-grab", !canDrag && "cursor-not-allowed")}
          aria-label={`Reordenar status ${status.name}`}
        >
          <GripVertical className="h-4 w-4 text-muted-foreground" />
        </Button>
      </TableCell>
      <TableCell>
        <div className="flex items-center gap-3">
          <span
            className="h-3 w-3 rounded-full border border-border"
            style={{ backgroundColor: status.color }}
            aria-hidden
          />
          <div className="flex flex-col">
            <span className="font-medium">{status.name}</span>
            {status.description && (
              <span className="text-xs text-muted-foreground line-clamp-1">
                {status.description}
              </span>
            )}
          </div>
        </div>
      </TableCell>
      <TableCell className="w-48">
        <div className="flex flex-wrap items-center gap-2">
          {status.is_initial && <Badge variant="outline">Inicial</Badge>}
          {status.is_final && <Badge variant="secondary">Final</Badge>}
          {!status.is_active && <Badge variant="destructive">Inativo</Badge>}
        </div>
      </TableCell>
      <TableCell className="w-32 text-sm text-muted-foreground">
        {status.orders_count ?? 0}
      </TableCell>
      <TableCell className="w-24 text-sm text-muted-foreground">
        {status.order_position}
      </TableCell>
      <TableCell className="w-56">
        <div className="flex items-center justify-end gap-2">
          <div className="flex items-center gap-2">
            <Label htmlFor={`active-${status.uuid}`} className="text-xs">
              {status.is_active ? "Ativo" : "Inativo"}
            </Label>
            <Switch
              id={`active-${status.uuid}`}
              checked={status.is_active}
              onCheckedChange={(checked) => onToggleActive(status.uuid, checked)}
              disabled={disableInteractions}
            />
          </div>
          <Button
            variant="outline"
            size="icon"
            onClick={() => onEdit(status)}
            disabled={disableInteractions}
          >
            <Edit className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            size="icon"
            onClick={() => onDelete(status.uuid)}
            disabled={disableInteractions || status.can_delete === false}
            title={
              status.can_delete === false
                ? "Não é possível deletar status com pedidos associados"
                : "Excluir status"
            }
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      </TableCell>
    </TableRow>
  )
}

export default function OrderStatusesPage() {
  const [statuses, setStatuses] = useState<OrderStatus[]>([])
  const [loading, setLoading] = useState(true)
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingStatus, setEditingStatus] = useState<OrderStatus | null>(null)
  const [deletingUuid, setDeletingUuid] = useState<string | null>(null)
  const [isReordering, setIsReordering] = useState(false)
  
  // Form states
  const [formName, setFormName] = useState("")
  const [formDescription, setFormDescription] = useState("")
  const [formColor, setFormColor] = useState("#6B7280")
  const [formIcon, setFormIcon] = useState("package")
  const [formIsInitial, setFormIsInitial] = useState(false)
  const [formIsFinal, setFormIsFinal] = useState(false)
  const [formIsActive, setFormIsActive] = useState(true)

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: { distance: 6 },
    })
  )

  const fetchStatuses = async () => {
    try {
      setLoading(true)
      const response = await apiClient.get<OrderStatus[]>(endpoints.orderStatuses.list(false))
      
      if (response.success) {
        setStatuses(response.data)
      }
    } catch (error: any) {
      toast.error('Erro ao carregar status')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    fetchStatuses()
  }, [])

  const resetForm = () => {
    setFormName("")
    setFormDescription("")
    setFormColor("#6B7280")
    setFormIcon("package")
    setFormIsInitial(false)
    setFormIsFinal(false)
    setFormIsActive(true)
    setEditingStatus(null)
  }

  const openCreateDialog = () => {
    resetForm()
    setDialogOpen(true)
  }

  const openEditDialog = (status: OrderStatus) => {
    setEditingStatus(status)
    setFormName(status.name)
    setFormDescription(status.description || "")
    setFormColor(status.color)
    setFormIcon(status.icon)
    setFormIsInitial(status.is_initial)
    setFormIsFinal(status.is_final)
    setFormIsActive(status.is_active)
    setDialogOpen(true)
  }

  const handleSubmit = async () => {
    if (!formName) {
      toast.error('Nome é obrigatório')
      return
    }

    const data = {
      name: formName,
      description: formDescription || undefined,
      color: formColor,
      icon: formIcon,
      is_initial: formIsInitial,
      is_final: formIsFinal,
      is_active: formIsActive,
    }

    try {
      if (editingStatus) {
        const response = await apiClient.put(endpoints.orderStatuses.update(editingStatus.uuid), data)
        if (response.success) {
          toast.success('Status atualizado com sucesso')
          setDialogOpen(false)
          resetForm()
          fetchStatuses()
        }
      } else {
        const response = await apiClient.post(endpoints.orderStatuses.create, data)
        if (response.success) {
          toast.success('Status criado com sucesso')
          setDialogOpen(false)
          resetForm()
          fetchStatuses()
        }
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erro ao salvar status')
    }
  }

  const handleDelete = async (uuid: string) => {
    try {
      const response = await apiClient.delete(endpoints.orderStatuses.delete(uuid))
      if (response.success) {
        toast.success('Status deletado com sucesso')
        setDeletingUuid(null)
        fetchStatuses()
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erro ao deletar status')
    }
  }

  const handleToggleActive = async (uuid: string, isActive: boolean) => {
    try {
      const response = await apiClient.put(endpoints.orderStatuses.update(uuid), {
        is_active: isActive
      })
      if (response.success) {
        toast.success(isActive ? 'Status ativado' : 'Status desativado')
        fetchStatuses()
      }
    } catch (error: any) {
      toast.error('Erro ao atualizar status')
    }
  }

  const handleDragEnd = async (event: DragEndEvent) => {
    const { active, over } = event

    if (!over || active.id === over.id) {
      return
    }

    const activeId = String(active.id)
    const overId = String(over.id)

    const oldIndex = statuses.findIndex((status) => status.uuid === activeId)
    const newIndex = statuses.findIndex((status) => status.uuid === overId)

    if (oldIndex === -1 || newIndex === -1) {
      return
    }

    const previousStatuses = statuses
    const reordered = arrayMove(previousStatuses, oldIndex, newIndex)

    setStatuses(reordered)
    setIsReordering(true)

    try {
      await apiClient.post(endpoints.orderStatuses.reorder, {
        order: reordered.map((status) => status.uuid),
      })
      toast.success('Ordem atualizada com sucesso')
    } catch (error: any) {
      setStatuses(previousStatuses)
      toast.error(error?.response?.data?.message || 'Não foi possível atualizar a ordem')
    } finally {
      setIsReordering(false)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center space-y-3">
          <div className="animate-spin h-8 w-8 border-4 border-primary border-t-transparent rounded-full mx-auto" />
          <p className="text-muted-foreground">Carregando status...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="@container/main px-4 lg:px-6">
        <StatusStatCards statuses={statuses} />
      </div>

      <div className="@container/main px-4 lg:px-6">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Status de Pedidos</h1>
          <p className="text-muted-foreground">
            Gerencie os status disponíveis para seus pedidos
          </p>
        </div>
        <Button onClick={openCreateDialog}>
          <Plus className="mr-2 h-4 w-4" />
          Novo Status
        </Button>
      </div>

        <Card className="mt-6">
          <CardHeader className="flex flex-col gap-1">
            <div className="flex items-center justify-between gap-4">
              <div>
                <CardTitle className="text-lg font-semibold">Status Cadastrados</CardTitle>
          <CardDescription>
                  Organize a ordem arrastando as linhas da tabela
          </CardDescription>
              </div>
              {isReordering && (
                <Badge variant="outline" className="text-xs">
                  Atualizando ordem...
                </Badge>
              )}
            </div>
        </CardHeader>
          <CardContent className="p-0">
            {statuses.length === 0 ? (
              <div className="flex flex-col items-center justify-center gap-2 py-16 text-center text-muted-foreground">
                <p className="text-base font-medium">Nenhum status cadastrado ainda.</p>
                <p className="text-sm">Clique em &quot;Novo Status&quot; para começar.</p>
              </div>
            ) : (
              <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                modifiers={[restrictToVerticalAxis]}
                onDragEnd={handleDragEnd}
              >
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-14" />
                        <TableHead>Status</TableHead>
                        <TableHead>Uso</TableHead>
                        <TableHead>Pedidos</TableHead>
                        <TableHead>Posição</TableHead>
                        <TableHead className="w-56 text-right">Ações</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <SortableContext
                        items={statuses.map((status) => status.uuid)}
                        strategy={verticalListSortingStrategy}
                      >
                        {statuses.map((status) => (
                          <SortableStatusRow
                  key={status.uuid}
                            status={status}
                            onEdit={openEditDialog}
                            onDelete={(uuid) => setDeletingUuid(uuid)}
                            onToggleActive={handleToggleActive}
                            disableInteractions={isReordering}
                          />
                        ))}
                      </SortableContext>
                    </TableBody>
                  </Table>
                </div>
              </DndContext>
            )}
        </CardContent>
      </Card>
      </div>

      {/* Dialog de Criação/Edição */}
      <Dialog open={dialogOpen} onOpenChange={(open) => {
        setDialogOpen(open)
        if (!open) resetForm()
      }}>
        <DialogContent className="sm:max-w-[500px]">
          <DialogHeader>
            <DialogTitle>
              {editingStatus ? 'Editar Status' : 'Novo Status'}
            </DialogTitle>
            <DialogDescription>
              Configure as informações do status do pedido
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="name">Nome *</Label>
              <Input
                id="name"
                value={formName}
                onChange={(e) => setFormName(e.target.value)}
                placeholder="Ex: Em Preparo"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Descrição</Label>
              <Textarea
                id="description"
                value={formDescription}
                onChange={(e) => setFormDescription(e.target.value)}
                placeholder="Descrição opcional do status"
                rows={3}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="color">Cor *</Label>
                <div className="flex gap-2">
                  <Input
                    id="color"
                    type="color"
                    value={formColor}
                    onChange={(e) => setFormColor(e.target.value)}
                    className="h-10 w-20"
                  />
                  <div
                    className="h-10 flex-1 rounded-md border"
                    style={{ backgroundColor: formColor }}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="icon">Ícone *</Label>
                <Input
                  id="icon"
                  value={formIcon}
                  onChange={(e) => setFormIcon(e.target.value)}
                  placeholder="Ex: package, truck, clock"
                />
                <p className="text-xs text-muted-foreground">
                  Use nomes do Lucide Icons
                </p>
              </div>
            </div>

            <div className="space-y-3 border-t pt-4">
              <div className="flex items-center justify-between">
                <div>
                  <Label htmlFor="is_initial">Status Inicial</Label>
                  <p className="text-xs text-muted-foreground">
                    Usado para novos pedidos
                  </p>
                </div>
                <Switch
                  id="is_initial"
                  checked={formIsInitial}
                  onCheckedChange={setFormIsInitial}
                />
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <Label htmlFor="is_final">Status Final</Label>
                  <p className="text-xs text-muted-foreground">
                    Indica conclusão do pedido
                  </p>
                </div>
                <Switch
                  id="is_final"
                  checked={formIsFinal}
                  onCheckedChange={setFormIsFinal}
                />
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <Label htmlFor="is_active">Ativo</Label>
                  <p className="text-xs text-muted-foreground">
                    Status disponível para uso
                  </p>
                </div>
                <Switch
                  id="is_active"
                  checked={formIsActive}
                  onCheckedChange={setFormIsActive}
                />
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
              Cancelar
            </Button>
            <Button type="button" onClick={handleSubmit}>
              <Save className="mr-2 h-4 w-4" />
              {editingStatus ? 'Salvar' : 'Criar'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Dialog de Confirmação de Exclusão */}
      <AlertDialog open={!!deletingUuid} onOpenChange={(open) => !open && setDeletingUuid(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja deletar este status? Esta ação não pode ser desfeita.
              {statuses.find(s => s.uuid === deletingUuid)?.orders_count ? (
                <span className="block mt-2 text-destructive font-semibold">
                  ⚠️ Este status possui {statuses.find(s => s.uuid === deletingUuid)?.orders_count} pedido(s) associado(s).
                </span>
              ) : null}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={async () => {
                if (deletingUuid) {
                  await handleDelete(deletingUuid)
                }
              }}
              className="bg-destructive hover:bg-destructive/90"
            >
              Deletar
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}

