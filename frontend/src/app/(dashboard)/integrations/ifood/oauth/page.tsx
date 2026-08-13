"use client"

import { useMemo, useState } from "react"
import { apiClient, endpoints } from "@/lib/api-client"
import { useAuth } from "@/contexts/auth-context"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Separator } from "@/components/ui/separator"
import { Badge } from "@/components/ui/badge"
import { Loader2, ShieldCheck, ExternalLink } from "lucide-react"
import Link from "next/link"

interface UserCodeResponse {
  user_code: string
  verification_url: string
  verification_url_complete: string
  expires_in: number
  expires_at: string
}

export default function IfoodOAuthPage() {
  const { user } = useAuth()
  const tenantId = useMemo(() => user?.tenant_id ?? null, [user])

  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [data, setData] = useState<UserCodeResponse | null>(null)

  const handleRequestUserCode = async () => {
    if (!tenantId) {
      setError(
        "Não encontramos um tenant associado ao usuário. Faça login novamente ou selecione a empresa antes de continuar."
      )
      return
    }

    setLoading(true)
    setError(null)
    setSuccessMessage(null)

    try {
      const response = await apiClient.post<UserCodeResponse>(
        endpoints.integrations.ifood.requestUserCode,
        {
          tenant_id: tenantId,
        }
      )

      setData(response.data)
      setSuccessMessage(
        "Código gerado com sucesso. Peça ao merchant para concluir a autorização no portal do iFood."
      )
    } catch (err: any) {
      setError(err?.message ?? "Não foi possível solicitar o código de autorização.")
      setData(null)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="space-y-6 px-4 lg:px-6">
      <div className="flex flex-col gap-2">
        <h1 className="text-3xl font-bold flex items-center gap-2">
          <ShieldCheck className="h-7 w-7" />
          Autorização OAuth iFood
        </h1>
        <p className="text-muted-foreground">
          Inicie o fluxo de autorização via OAuth para permitir que o sistema
          acesse recursos protegidos do iFood em nome do merchant. Os dados
          exibidos aqui são consultados diretamente da API oficial do iFood.
        </p>
      </div>

      <Alert>
        <AlertTitle>Fonte externa confiável</AlertTitle>
        <AlertDescription>
          O código e links abaixo são fornecidos pela API iFood. Oriente o
          operador a acessar o endereço completo, inserir o código e conceder
          as permissões solicitadas.
        </AlertDescription>
      </Alert>

      <Card>
        <CardHeader>
          <CardTitle>Solicitar código de autorização</CardTitle>
          <CardDescription>
            Gere um novo <code>userCode</code> válido por 10 minutos. O código e
            as URLs são armazenados com segurança para continuidade do fluxo
            OAuth.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button onClick={handleRequestUserCode} disabled={loading}>
              {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Gerar código do iFood
            </Button>
            <Badge variant="outline">Requer credenciais iFood válidas</Badge>
          </div>

          {error && (
            <p className="text-sm font-medium text-destructive">{error}</p>
          )}

          {successMessage && (
            <p className="text-sm text-muted-foreground">{successMessage}</p>
          )}

          {data && (
            <>
              <Separator />
              <div className="space-y-4">
                <div>
                  <h3 className="text-lg font-semibold">Passos para o merchant</h3>
                  <ol className="list-decimal list-inside text-sm space-y-1 text-muted-foreground">
                    <li>Acesse o link abaixo (portal do iFood).</li>
                    <li>Insira o código apresentado.</li>
                    <li>Conclua a autorização concedendo as permissões solicitadas.</li>
                  </ol>
                </div>

                <div className="rounded-md border p-4 space-y-3">
                  <div>
                    <span className="text-xs uppercase text-muted-foreground">
                      Código
                    </span>
                    <p className="text-2xl font-bold tracking-wide">
                      {data.user_code}
                    </p>
                  </div>

                  <div className="flex flex-col gap-2">
                    <span className="text-xs uppercase text-muted-foreground">
                      URL completa
                    </span>
                    <Link
                      href={data.verification_url_complete}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                    >
                      {data.verification_url_complete}
                      <ExternalLink className="h-4 w-4" />
                    </Link>
                  </div>

                  <div className="flex flex-col gap-2 text-sm text-muted-foreground">
                    <span>
                      Expira em aproximadamente {Math.floor(data.expires_in / 60)} minutos.
                    </span>
                    <span>Expiração (UTC): {new Date(data.expires_at).toLocaleString()}</span>
                  </div>
                </div>
              </div>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  )
}

