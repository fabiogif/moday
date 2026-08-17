"use client"

import { useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Award, Plus, Edit, Trash2, Loader2, AlertCircle, Gift, Package } from "lucide-react"
import { useLoyaltyProgram, useLoyaltyRewards } from "@/hooks/use-loyalty"
import { LoyaltyRewardFormDialog } from "./components/loyalty-reward-form-dialog"
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

export default function LoyaltyRewardsPage() {
  const [isFormOpen, setIsFormOpen] = useState(false)
  const [editingReward, setEditingReward] = useState<any>(null)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [rewardToDelete, setRewardToDelete] = useState<any>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  const { data: program } = useLoyaltyProgram()
  const { data: rewards, loading, error, refetch } = useLoyaltyRewards()

  const hasProgram = !!program

  const handleAddNew = () => {
    if (!hasProgram) {
      toast.error("Configure um programa de fidelidade primeiro")
      return
    }
    setEditingReward(null)
    setIsFormOpen(true)
  }

  const handleEdit = (reward: any) => {
    setEditingReward(reward)
    setIsFormOpen(true)
  }

  const handleDeleteClick = (reward: any) => {
    setRewardToDelete(reward)
    setDeleteDialogOpen(true)
  }

  const handleDeleteConfirm = async () => {
    if (!rewardToDelete) return

    setIsDeleting(true)
    try {
      await apiClient.delete(endpoints.loyalty.deleteReward(rewardToDelete.uuid))
      toast.success("Recompensa excluída com sucesso")
      refetch()
      setDeleteDialogOpen(false)
    } catch (error: any) {
      toast.error(error.message || "Erro ao excluir recompensa")
    } finally {
      setIsDeleting(false)
    }
  }

  const handleFormSuccess = () => {
    setIsFormOpen(false)
    setEditingReward(null)
    refetch()
  }

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'discount_percentage':
      case 'discount_fixed':
        return <Gift className="h-5 w-5 text-green-500" />
      case 'free_product':
        return <Package className="h-5 w-5 text-blue-500" />
      case 'free_shipping':
        return <Award className="h-5 w-5 text-purple-500" />
      default:
        return <Award className="h-5 w-5 text-muted-foreground" />
    }
  }

  const formatValue = (reward: any) => {
    switch (reward.type) {
      case 'discount_percentage':
        return `${reward.discount_value}% de desconto`
      case 'discount_fixed':
        return `R$ ${reward.discount_value?.toFixed(2)} de desconto`
      case 'free_product':
        return reward.product?.name || 'Produto grátis'
      case 'free_shipping':
        return 'Frete grátis'
      default:
        return reward.type_label
    }
  }

  return (
    <div className="space-y-6 px-4 lg:px-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Recompensas</h1>
          <p className="text-muted-foreground">
            Gerencie as recompensas que seus clientes podem resgatar com pontos
          </p>
        </div>
        <Button onClick={handleAddNew} disabled={!hasProgram}>
          <Plus className="mr-2 h-4 w-4" />
          Nova Recompensa
        </Button>
      </div>

      {/* Alert se não tiver programa */}
      {!hasProgram && (
        <Alert>
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            Configure um programa de fidelidade antes de criar recompensas.{" "}
            <a href="/loyalty/program" className="underline font-medium">
              Ir para Configuração
            </a>
          </AlertDescription>
        </Alert>
      )}

      {/* Estatísticas */}
      {hasProgram && rewards && (
        <div className="grid gap-4 md:grid-cols-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total</CardTitle>
              <Award className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{rewards.length}</div>
              <p className="text-xs text-muted-foreground">Recompensas cadastradas</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Ativas</CardTitle>
              <Award className="h-4 w-4 text-green-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {rewards.filter((r) => r.is_active && r.is_available).length}
              </div>
              <p className="text-xs text-muted-foreground">Disponíveis</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Pontos Mín.</CardTitle>
              <Gift className="h-4 w-4 text-blue-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {rewards.length > 0 ? Math.min(...rewards.map((r) => r.points_required)) : '-'}
              </div>
              <p className="text-xs text-muted-foreground">Menor recompensa</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Pontos Máx.</CardTitle>
              <Gift className="h-4 w-4 text-purple-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {rewards.length > 0 ? Math.max(...rewards.map((r) => r.points_required)) : '-'}
              </div>
              <p className="text-xs text-muted-foreground">Maior recompensa</p>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Lista de Recompensas */}
      <Card>
        <CardHeader>
          <CardTitle>Recompensas Cadastradas</CardTitle>
          <CardDescription>
            Lista de todas as recompensas disponíveis no programa
          </CardDescription>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex justify-center items-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : error ? (
            <Alert variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          ) : !rewards || rewards.length === 0 ? (
            <div className="text-center py-12">
              <Award className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
              <p className="text-lg font-medium mb-2">Nenhuma recompensa cadastrada</p>
              <p className="text-sm text-muted-foreground mb-4">
                Crie recompensas para seus clientes resgatarem com pontos
              </p>
              <Button onClick={handleAddNew} disabled={!hasProgram}>
                <Plus className="mr-2 h-4 w-4" />
                Adicionar Primeira Recompensa
              </Button>
            </div>
          ) : (
            <div className="space-y-3">
              {rewards.map((reward) => (
                <div
                  key={reward.uuid}
                  className="flex items-center justify-between p-4 border rounded-lg hover:bg-accent transition-colors"
                >
                  <div className="flex items-center gap-4">
                    {getTypeIcon(reward.type)}
                    <div>
                      <p className="font-medium">{reward.name}</p>
                      <div className="flex items-center gap-2 mt-1">
                        <p className="text-sm text-muted-foreground">
                          {formatValue(reward)}
                        </p>
                        <span className="text-muted-foreground">•</span>
                        <p className="text-sm font-medium text-primary">
                          {reward.points_required} pontos
                        </p>
                      </div>
                      {reward.stock_quantity !== null && (
                        <p className="text-xs text-muted-foreground mt-1">
                          Estoque: {reward.stock_quantity} unidades
                        </p>
                      )}
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant={reward.is_active && reward.is_available ? "default" : "secondary"}>
                      {reward.is_active && reward.is_available ? "Disponível" : "Indisponível"}
                    </Badge>
                    {!reward.has_stock && (
                      <Badge variant="destructive">Esgotado</Badge>
                    )}
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleEdit(reward)}
                    >
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDeleteClick(reward)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Form Dialog */}
      <LoyaltyRewardFormDialog
        open={isFormOpen}
        onOpenChange={setIsFormOpen}
        reward={editingReward}
        onSuccess={handleFormSuccess}
      />

      {/* Delete Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir Recompensa</AlertDialogTitle>
            <AlertDialogDescription asChild>
              <div>
                Tem certeza que deseja excluir esta recompensa?
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

