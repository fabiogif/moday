"use client"

import { useState, useEffect } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Button } from "@/components/ui/button"
import { Save, ShieldCheck } from "lucide-react"
import { useToast } from "@/hooks/use-toast"
import { apiClient } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"

export default function SecuritySettings() {
  const { toast } = useToast()
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [tenantUuid, setTenantUuid] = useState<string | null>(null)
  const [requireEmailVerification, setRequireEmailVerification] = useState(true)
  const [initialValue, setInitialValue] = useState(true)

  useEffect(() => {
    const loadSettings = async () => {
      try {
        setLoading(true)
        const response = await apiClient.get('/api/auth/me')

        if (response.success && response.data) {
          const userData = response.data as any
          const tenantSettings = userData.tenant?.settings || {}
          const value = tenantSettings.require_email_verification ?? true

          setTenantUuid(userData.tenant?.uuid ?? null)
          setRequireEmailVerification(value)
          setInitialValue(value)
        }
      } catch (error) {
        toast({
          title: "Erro",
          description: "Erro ao carregar configurações de segurança",
          variant: "destructive",
        })
      } finally {
        setLoading(false)
      }
    }

    loadSettings()
  }, [])

  const hasChanges = requireEmailVerification !== initialValue

  const handleSave = async () => {
    if (!tenantUuid) {
      toast({
        title: "Erro",
        description: "Empresa não encontrada",
        variant: "destructive",
      })
      return
    }

    try {
      setSaving(true)

      const response = await apiClient.put(`/api/tenant/${tenantUuid}`, {
        settings: {
          require_email_verification: requireEmailVerification,
        },
      })

      if (response.success) {
        toast({
          title: "Sucesso!",
          description: "Configurações de segurança salvas com sucesso",
        })
        setInitialValue(requireEmailVerification)
      }
    } catch (error) {
      toast({
        title: "Erro",
        description: "Erro ao salvar configurações de segurança",
        variant: "destructive",
      })
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return <PageLoading />
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Segurança</h1>
        <p className="text-muted-foreground">
          Controle as exigências de segurança para os usuários da sua empresa
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <ShieldCheck className="h-5 w-5" />
            Confirmação de e-mail
          </CardTitle>
          <CardDescription>
            Quando ativado, novos usuários da sua empresa precisam confirmar o
            e-mail (código enviado por e-mail) antes de usar o painel.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-between rounded-lg border p-4">
            <div className="space-y-0.5">
              <Label>Exigir confirmação de e-mail</Label>
              <p className="text-sm text-muted-foreground">
                Desative se preferir liberar o acesso dos usuários sem esse passo extra.
              </p>
            </div>
            <Switch
              checked={requireEmailVerification}
              onCheckedChange={setRequireEmailVerification}
            />
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end">
        <Button onClick={handleSave} disabled={saving || !hasChanges}>
          <Save className="mr-2 h-4 w-4" />
          {saving ? "Salvando..." : "Salvar alterações"}
        </Button>
      </div>
    </div>
  )
}
