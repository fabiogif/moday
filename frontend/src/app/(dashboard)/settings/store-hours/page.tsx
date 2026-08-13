"use client"

import { useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Switch } from "@/components/ui/switch"
import { Label } from "@/components/ui/label"
import { Separator } from "@/components/ui/separator"
import { Clock, Plus, Edit, Trash2, Store, Loader2, AlertCircle } from "lucide-react"
import { useStoreHours, useStoreHourStats } from "@/hooks/use-store-hours"
import { StoreHourFormDialog } from "./components/store-hour-form-dialog"
import { toast } from "sonner"
import apiClient, { endpoints } from "@/lib/api-client"
import { Alert, AlertDescription } from "@/components/ui/alert"
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

export default function StoreHoursSettings() {
  const [isFormOpen, setIsFormOpen] = useState(false)
  const [editingHour, setEditingHour] = useState<any>(null)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [hourToDelete, setHourToDelete] = useState<any>(null)
  const [isDeleting, setIsDeleting] = useState(false)
  const [isTogglingAlwaysOpen, setIsTogglingAlwaysOpen] = useState(false)

  const { data: storeHours, loading, error, refetch } = useStoreHours()
  const { data: stats, refetch: refetchStats } = useStoreHourStats()

  const isAlwaysOpen = stats?.is_always_open || false

  const handleAddNew = () => {
    setEditingHour(null)
    setIsFormOpen(true)
  }

  const handleEdit = (hour: any) => {
    setEditingHour(hour)
    setIsFormOpen(true)
  }

  const handleDeleteClick = (hour: any) => {
    setHourToDelete(hour)
    setDeleteDialogOpen(true)
  }

  const handleDeleteConfirm = async () => {
    if (!hourToDelete) return

    setIsDeleting(true)
    try {
      await apiClient.delete(endpoints.storeHours.delete(hourToDelete.uuid))
      toast.success("Horário excluído com sucesso")
      refetch()
      refetchStats()
      setDeleteDialogOpen(false)
    } catch (error: any) {
      toast.error(error.message || "Erro ao excluir horário")
    } finally {
      setIsDeleting(false)
    }
  }

  const handleToggleAlwaysOpen = async (checked: boolean) => {
    setIsTogglingAlwaysOpen(true)
    try {
      if (checked) {
        await apiClient.post(endpoints.storeHours.setAlwaysOpen, {
          delivery_type: 'both'
        })
        toast.success("Loja configurada como sempre aberta")
      } else {
        await apiClient.post(endpoints.storeHours.removeAlwaysOpen, {})
        toast.success("Configuração 'sempre aberto' removida")
      }
      refetch()
      refetchStats()
    } catch (error: any) {
      toast.error(error.message || "Erro ao atualizar configuração")
    } finally {
      setIsTogglingAlwaysOpen(false)
    }
  }

  const handleFormSuccess = () => {
    setIsFormOpen(false)
    setEditingHour(null)
    refetch()
    refetchStats()
  }

  const groupedHours = storeHours?.reduce((acc: any, hour) => {
    if (hour.is_always_open) return acc
    
    const day = hour.day_name || 'Indefinido'
    if (!acc[day]) {
      acc[day] = []
    }
    acc[day].push(hour)
    return acc
  }, {}) || {}

  const daysOrder = ['Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado', 'Domingo']

  return (
    <div className="space-y-6 px-4 lg:px-6">
      <div>
        <h1 className="text-3xl font-bold">Horários de Funcionamento</h1>
        <p className="text-muted-foreground">
          Configure os horários em que sua loja estará disponível para receber pedidos.
        </p>
      </div>

      {/* Sempre Aberto */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Store className="h-5 w-5" />
            Configuração Rápida
          </CardTitle>
          <CardDescription>
            Configure sua loja para estar sempre aberta ou definir horários personalizados
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-between rounded-lg border p-4">
            <div className="space-y-0.5">
              <Label className="text-base">Sempre Aberto</Label>
              <div className="text-sm text-muted-foreground">
                Sua loja aceitará pedidos 24 horas por dia, todos os dias
              </div>
            </div>
            <Switch
              checked={isAlwaysOpen}
              onCheckedChange={handleToggleAlwaysOpen}
              disabled={isTogglingAlwaysOpen}
            />
          </div>

          {isAlwaysOpen && (
            <Alert>
              <Clock className="h-4 w-4" />
              <AlertDescription>
                Sua loja está configurada como sempre aberta. Os horários personalizados estão desativados.
              </AlertDescription>
            </Alert>
          )}
        </CardContent>
      </Card>

      {/* Estatísticas */}
      {stats && !isAlwaysOpen && (
        <Card>
          <CardHeader>
            <CardTitle>Resumo</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div className="space-y-2">
                <p className="text-sm text-muted-foreground">Total de Horários</p>
                <p className="text-2xl font-bold">{stats.total_hours}</p>
              </div>
              <div className="space-y-2">
                <p className="text-sm text-muted-foreground">Dias Configurados</p>
                <p className="text-2xl font-bold">{stats.days_configured}/7</p>
              </div>
              <div className="space-y-2">
                <p className="text-sm text-muted-foreground">Entrega</p>
                <Badge variant={stats.has_delivery ? "default" : "secondary"}>
                  {stats.has_delivery ? "Ativo" : "Inativo"}
                </Badge>
              </div>
              <div className="space-y-2">
                <p className="text-sm text-muted-foreground">Retirada</p>
                <Badge variant={stats.has_pickup ? "default" : "secondary"}>
                  {stats.has_pickup ? "Ativo" : "Inativo"}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Horários Personalizados */}
      {!isAlwaysOpen && (
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0">
            <div>
              <CardTitle>Horários Personalizados</CardTitle>
              <CardDescription>
                Defina horários específicos para cada dia da semana
              </CardDescription>
            </div>
            <Button onClick={handleAddNew} disabled={loading}>
              <Plus className="mr-2 h-4 w-4" />
              Adicionar Horário
            </Button>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex justify-center items-center py-8">
                <Loader2 role="status" className="h-8 w-8 animate-spin text-muted-foreground" />
              </div>
            ) : error ? (
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            ) : !storeHours || storeHours.length === 0 ? (
              <div className="text-center py-12">
                <Clock className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                <p className="text-lg font-medium mb-2">Nenhum horário configurado</p>
                <p className="text-sm text-muted-foreground mb-4">
                  Adicione horários de funcionamento para sua loja
                </p>
                <Button onClick={handleAddNew}>
                  <Plus className="mr-2 h-4 w-4" />
                  Adicionar Primeiro Horário
                </Button>
              </div>
            ) : (
              <div className="space-y-6">
                {daysOrder.map((day) => {
                  const hours = groupedHours[day]
                  if (!hours || hours.length === 0) return null

                  return (
                    <div key={day} className="space-y-3">
                      <h3 className="font-semibold text-lg">{day}</h3>
                      <div className="space-y-2">
                        {hours.map((hour: any) => (
                          <div
                            key={hour.uuid}
                            className="flex items-center justify-between p-4 border rounded-lg hover:bg-accent transition-colors"
                          >
                            <div className="flex items-center gap-4">
                              <Clock className="h-5 w-5 text-muted-foreground" />
                              <div>
                                <p className="font-medium">
                                  {hour.start_time} - {hour.end_time}
                                  {hour.start_time_2 && hour.end_time_2 && (
                                    <span className="ml-2 text-muted-foreground">
                                      • {hour.start_time_2} - {hour.end_time_2}
                                    </span>
                                  )}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                  {hour.delivery_type_label}
                                  {hour.start_time_2 && hour.end_time_2 && (
                                    <span className="ml-1">(com intervalo)</span>
                                  )}
                                </p>
                              </div>
                            </div>
                            <div className="flex items-center gap-2">
                              <Badge variant={hour.is_active ? "default" : "secondary"}>
                                {hour.is_active ? "Ativo" : "Inativo"}
                              </Badge>
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => handleEdit(hour)}
                              >
                                <Edit className="h-4 w-4" />
                              </Button>
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => handleDeleteClick(hour)}
                              >
                                <Trash2 className="h-4 w-4" />
                              </Button>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  )
                })}
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Form Dialog */}
      <StoreHourFormDialog
        open={isFormOpen}
        onOpenChange={setIsFormOpen}
        hour={editingHour}
        onSuccess={handleFormSuccess}
      />

      {/* Delete Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir Horário</AlertDialogTitle>
            <AlertDialogDescription asChild>
              <div>
                Tem certeza que deseja excluir este horário de funcionamento?
                Esta ação não pode ser desfeita.
              </div>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={isDeleting}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteConfirm}
              disabled={isDeleting}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {isDeleting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}

