"use client"

import { useState, useEffect } from "react"
import { useSearchParams, useRouter } from "next/navigation"
import { format } from "date-fns"
import { ptBR } from "date-fns/locale"
import {
  ArrowLeft,
  Calendar,
  Users,
  FileText,
  Hash,
  Clock,
  MapPin,
  Info,
  CheckCircle,
  XCircle,
} from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import { PageLoading } from "@/components/ui/loading-progress"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { TableData } from "../types"

export default function TableDetailsPage() {
  const searchParams = useSearchParams()
  const router = useRouter()
  const tableId = searchParams.get('id') || ''
  
  const [table, setTable] = useState<TableData | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const { data: tableData, loading: apiLoading, error: apiError } = useAuthenticatedApi<TableData>(
    tableId ? endpoints.tables.show(tableId) : ''
  )

  useEffect(() => {
    if (tableData) {
      setTable(tableData)
      setLoading(false)
    }
    if (apiError) {
      setError(apiError)
      setLoading(false)
    }
    if (!apiLoading && !tableData && !apiError) {
      setLoading(false)
    }
  }, [tableData, apiLoading, apiError])

  const formatDate = (dateString: string) => {
    try {
      return format(new Date(dateString), "dd/MM/yyyy 'às' HH:mm", { locale: ptBR })
    } catch {
      return dateString
    }
  }

  const getStatusInfo = (status?: string) => {
    switch (status) {
      case "Disponível":
        return { color: "text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20", icon: CheckCircle }
      case "Ocupada":
        return { color: "text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20", icon: XCircle }
      case "Reservada":
        return { color: "text-orange-600 bg-orange-50 dark:text-orange-400 dark:bg-orange-900/20", icon: Clock }
      case "Manutenção":
        return { color: "text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20", icon: XCircle }
      default:
        return { color: "text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20", icon: Info }
    }
  }

  if (!tableId) {
    return (
      <div className="flex flex-col items-center justify-center h-96 space-y-4">
        <div className="text-center">
          <h2 className="text-lg font-semibold mb-2">ID da mesa não informado</h2>
          <p className="text-muted-foreground">Por favor, selecione uma mesa para ver os detalhes.</p>
        </div>
        <Button onClick={() => router.push('/tables')}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          Voltar para Mesas
        </Button>
      </div>
    )
  }

  if (loading || apiLoading) {
    return (
      <PageLoading 
        isLoading={true}
        message="Carregando detalhes da mesa..."
      />
    )
  }

  if (error || apiError) {
    return (
      <div className="flex flex-col items-center justify-center h-96 space-y-4">
        <div className="text-destructive text-center">
          <h2 className="text-lg font-semibold mb-2">Erro ao carregar mesa</h2>
          <p>{error || apiError}</p>
        </div>
        <Button onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
      </div>
    )
  }

  if (!table) {
    return (
      <div className="flex flex-col items-center justify-center h-96 space-y-4">
        <div className="text-center">
          <h2 className="text-lg font-semibold mb-2">Mesa não encontrada</h2>
          <p className="text-muted-foreground">A mesa solicitada não existe ou foi removida.</p>
        </div>
        <Button onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
      </div>
    )
  }

  const statusInfo = getStatusInfo(table.status)
  const StatusIcon = statusInfo.icon

  return (
    <div className="flex flex-col gap-6 p-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.back()}
          >
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">
              {table.name || table.identify}
            </h1>
            <p className="text-muted-foreground">
              Detalhes da mesa
            </p>
          </div>
        </div>
      </div>

      {/* Cards de Informações */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {/* Informações Básicas */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Informações Básicas</CardTitle>
            <Info className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div>
                <p className="text-sm text-muted-foreground">Nome da Mesa</p>
                <p className="text-lg font-semibold">{table.name || 'Sem nome'}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Identificador</p>
                <div className="flex items-center gap-2">
                  <Hash className="h-4 w-4 text-muted-foreground" />
                  <p className="font-medium">{table.identify}</p>
                </div>
              </div>
              {table.uuid && (
                <div>
                  <p className="text-sm text-muted-foreground">UUID</p>
                  <p className="text-xs text-muted-foreground font-mono">{table.uuid}</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Capacidade e Status */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Capacidade e Status</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div>
                <p className="text-sm text-muted-foreground">Capacidade</p>
                <div className="flex items-center gap-2">
                  <Users className="h-4 w-4 text-muted-foreground" />
                  <p className="text-lg font-semibold">{table.capacity} pessoas</p>
                </div>
              </div>
              {table.status && (
                <div>
                  <p className="text-sm text-muted-foreground">Status</p>
                  <Badge className={statusInfo.color}>
                    <StatusIcon className="mr-1 h-3 w-3" />
                    {table.status}
                  </Badge>
                </div>
              )}
              {table.location && (
                <div>
                  <p className="text-sm text-muted-foreground">Localização</p>
                  <div className="flex items-center gap-2">
                    <MapPin className="h-4 w-4 text-muted-foreground" />
                    <p className="font-medium">{table.location}</p>
                  </div>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Datas */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Informações de Data</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div>
                <p className="text-sm text-muted-foreground">Criado em</p>
                <div className="flex items-center gap-2">
                  <Calendar className="h-4 w-4 text-muted-foreground" />
                  <p className="font-medium">
                    {table.created_at_formatted || formatDate(table.created_at)}
                  </p>
                </div>
              </div>
              {table.updated_at && (
                <div>
                  <p className="text-sm text-muted-foreground">Última atualização</p>
                  <div className="flex items-center gap-2">
                    <Clock className="h-4 w-4 text-muted-foreground" />
                    <p className="font-medium">{formatDate(table.updated_at)}</p>
                  </div>
                </div>
              )}
              {table.isActive !== undefined && (
                <div>
                  <p className="text-sm text-muted-foreground">Ativa</p>
                  <Badge variant={table.isActive ? "default" : "secondary"}>
                    {table.isActive ? (
                      <CheckCircle className="mr-1 h-3 w-3" />
                    ) : (
                      <XCircle className="mr-1 h-3 w-3" />
                    )}
                    {table.isActive ? "Sim" : "Não"}
                  </Badge>
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Descrição */}
      {table.description && (
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Descrição</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <p className="text-sm leading-relaxed">{table.description}</p>
          </CardContent>
        </Card>
      )}

      {/* Informações Adicionais */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Resumo da Mesa</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-muted-foreground">ID:</span>
                <span className="font-medium">{table.id}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Identificador:</span>
                <span className="font-medium">{table.identify}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Nome:</span>
                <span className="font-medium">{table.name || 'Não informado'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Capacidade:</span>
                <span className="font-medium">{table.capacity} pessoas</span>
              </div>
              {table.status && (
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Status:</span>
                  <Badge className={statusInfo.color} variant="outline">
                    {table.status}
                  </Badge>
                </div>
              )}
              {table.location && (
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Localização:</span>
                  <span className="font-medium">{table.location}</span>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Card de Status Visual */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Status Atual</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex flex-col items-center space-y-4">
              <div className={`w-24 h-24 rounded-full flex items-center justify-center ${statusInfo.color}`}>
                <StatusIcon className="w-12 h-12" />
              </div>
              <div className="text-center">
                <p className="text-lg font-semibold">{table.status || 'Status não definido'}</p>
                <p className="text-sm text-muted-foreground">
                  {table.status === 'Disponível' && 'Mesa pronta para uso'}
                  {table.status === 'Ocupada' && 'Mesa atualmente em uso'}
                  {table.status === 'Reservada' && 'Mesa reservada para cliente'}
                  {table.status === 'Manutenção' && 'Mesa em manutenção'}
                  {!table.status && 'Status não informado'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}