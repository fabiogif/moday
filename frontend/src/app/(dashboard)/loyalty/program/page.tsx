"use client"

import { useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Switch } from "@/components/ui/switch"
import { Label } from "@/components/ui/label"
import { Gift, Plus, Settings as SettingsIcon, Award, Users, ArrowRight } from "lucide-react"
import { useLoyaltyProgram, useLoyaltyRewards } from "@/hooks/use-loyalty"
import { LoyaltyProgramFormDialog } from "./components/loyalty-program-form-dialog"
import { toast } from "sonner"
import Link from "next/link"

export default function LoyaltyProgramPage() {
  const { data: program, loading, refetch } = useLoyaltyProgram()
  const { data: rewards } = useLoyaltyRewards()
  const [configDialogOpen, setConfigDialogOpen] = useState(false)

  const hasProgram = !!program

  const handleFormSuccess = () => {
    setConfigDialogOpen(false)
    refetch()
  }

  return (
    <div className="space-y-6 px-4 lg:px-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Programa de Fidelidade</h1>
          <p className="text-muted-foreground">
            Gerencie seu programa de pontos e recompensas
          </p>
        </div>
        {hasProgram && (
          <Button onClick={() => setConfigDialogOpen(true)}>
            <SettingsIcon className="mr-2 h-4 w-4" />
            Configurar Programa
          </Button>
        )}
      </div>

      {/* Status Cards */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Programa</CardTitle>
            <Gift className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{hasProgram ? 'Ativo' : 'Inativo'}</div>
            <p className="text-xs text-muted-foreground">
              {hasProgram ? program.name : 'Configure um programa'}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Recompensas</CardTitle>
            <Award className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{rewards?.length || 0}</div>
            <p className="text-xs text-muted-foreground">
              Disponíveis para resgate
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pontos por R$</CardTitle>
            <Gift className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {program?.points_per_currency || '-'}
            </div>
            <p className="text-xs text-muted-foreground">
              Valor de conversão
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Participantes</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">-</div>
            <p className="text-xs text-muted-foreground">
              Clientes com pontos
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Empty State */}
      {!hasProgram && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Gift className="h-16 w-16 text-muted-foreground mb-4" />
            <h2 className="text-2xl font-bold mb-2">Nenhum Programa Configurado</h2>
            <p className="text-muted-foreground text-center mb-6 max-w-md">
              Crie um programa de fidelidade para recompensar seus clientes
              e aumentar a recorrência de compras
            </p>
            <Button size="lg" onClick={() => setConfigDialogOpen(true)}>
              <Plus className="mr-2 h-5 w-5" />
              Criar Programa de Fidelidade
            </Button>
          </CardContent>
        </Card>
      )}

      {/* Program Details */}
      {hasProgram && (
        <div className="grid gap-4 md:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Regras de Acúmulo</CardTitle>
              <CardDescription>Como os clientes ganham pontos</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label>Pontos por Real Gasto</Label>
                <p className="text-2xl font-bold">{program.points_per_currency}</p>
              </div>
              {program.min_purchase_amount && (
                <div>
                  <Label>Compra Mínima</Label>
                  <p className="text-lg">R$ {program.min_purchase_amount.toFixed(2)}</p>
                </div>
              )}
              {program.points_expiry_days && (
                <div>
                  <Label>Validade dos Pontos</Label>
                  <p className="text-lg">{program.points_expiry_days} dias</p>
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0">
              <div>
                <CardTitle>Recompensas</CardTitle>
                <CardDescription>Benefícios disponíveis</CardDescription>
              </div>
              <Link href="/loyalty/rewards">
                <Button variant="outline" size="sm">
                  Gerenciar
                  <ArrowRight className="ml-2 h-4 w-4" />
                </Button>
              </Link>
            </CardHeader>
            <CardContent>
              {rewards && rewards.length > 0 ? (
                <div className="space-y-2">
                  {rewards.slice(0, 3).map((reward) => (
                    <div key={reward.uuid} className="flex justify-between items-center p-2 border rounded">
                      <div>
                        <p className="font-medium">{reward.name}</p>
                        <p className="text-sm text-muted-foreground">{reward.points_required} pontos</p>
                      </div>
                      <Award className="h-5 w-5 text-primary" />
                    </div>
                  ))}
                  {rewards.length > 3 && (
                    <p className="text-sm text-center text-muted-foreground">
                      +{rewards.length - 3} mais recompensas
                    </p>
                  )}
                </div>
              ) : (
                <div className="text-center py-4">
                  <p className="text-sm text-muted-foreground mb-3">
                    Nenhuma recompensa cadastrada
                  </p>
                  <Link href="/loyalty/rewards">
                    <Button variant="outline" size="sm">
                      <Plus className="mr-2 h-4 w-4" />
                      Adicionar Recompensas
                    </Button>
                  </Link>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      )}

      {/* Form Dialog */}
      <LoyaltyProgramFormDialog
        open={configDialogOpen}
        onOpenChange={setConfigDialogOpen}
        program={program}
        onSuccess={handleFormSuccess}
      />
    </div>
  )
}

