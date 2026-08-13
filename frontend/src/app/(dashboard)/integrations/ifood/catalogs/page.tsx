"use client"

import { useEffect, useMemo, useState } from "react"
import { apiClient, endpoints } from "@/lib/api-client"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Loader2, UtensilsCrossed } from "lucide-react"
import { useAuth } from "@/contexts/auth-context"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

interface IfoodCatalog {
  catalogId: string
  context?: string[]
  status?: string
  modifiedAt?: number
}

interface IfoodOption {
  id: string
  name: string
  price?: {
    value: number
  }
  status?: string
}

interface IfoodOptionGroup {
  id: string
  name: string
  min?: number
  max?: number
  options?: IfoodOption[]
}

interface IfoodItem {
  id: string
  name: string
  description?: string
  status?: string
  externalCode?: string
  price?: {
    value?: number
    originalValue?: number
  }
  optionGroups?: IfoodOptionGroup[]
}

interface IfoodCategory {
  id: string
  name: string
  status?: string
  items?: IfoodItem[]
}

type CatalogCategories = Record<string, IfoodCategory[]>

export default function IfoodCatalogsPage() {
  const { user } = useAuth()
  const [merchantId, setMerchantId] = useState("")
  const [catalogs, setCatalogs] = useState<IfoodCatalog[]>([])
  const [categoriesByCatalog, setCategoriesByCatalog] =
    useState<CatalogCategories>({})
  const [loadingCatalogs, setLoadingCatalogs] = useState(false)
  const [loadingCategories, setLoadingCategories] = useState<string | null>(
    null
  )
  const [error, setError] = useState<string | null>(null)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [unsellableItemsByCatalog, setUnsellableItemsByCatalog] = useState<
    Record<string, any[]>
  >({})
  const [loadingUnsellable, setLoadingUnsellable] = useState<string | null>(null)
  const [groupsByCatalog, setGroupsByCatalog] = useState<Record<string, any[]>>(
    {}
  )
  const [loadingGroups, setLoadingGroups] = useState<string | null>(null)
  const [groupId, setGroupId] = useState("")
  const [sellableItems, setSellableItems] = useState<any[]>([])
  const [loadingSellable, setLoadingSellable] = useState(false)
  const [versionInfo, setVersionInfo] = useState<any | null>(null)
  const [loadingVersion, setLoadingVersion] = useState(false)
  const [snapshots, setSnapshots] = useState<any[]>([])
  const [snapshotsMeta, setSnapshotsMeta] = useState<any | null>(null)
  const [loadingSnapshots, setLoadingSnapshots] = useState(false)

  const ifoodEndpoints = useMemo(
    () =>
      endpoints.integrations.ifood as typeof endpoints.integrations.ifood & {
        groups: (catalogId: string) => string
        unsellableItems: (catalogId: string) => string
        sellableItems: (groupId: string) => string
        catalogVersion: string
        snapshots: string
      },
    []
  )

  const availableGroups = useMemo(() => {
    const entries = Object.values(groupsByCatalog).flat() as any[]
    const unique = new Map<string, any>()
    entries.forEach((group) => {
      if (group?.id && !unique.has(group.id)) {
        unique.set(group.id, group)
      }
    })
    return Array.from(unique.values())
  }, [groupsByCatalog])

  const tenantId = useMemo(() => {
    return user?.tenant_id ?? null
  }, [user])

  useEffect(() => {
    setSuccessMessage(null)
  }, [merchantId])

  const fetchCatalogs = async () => {
    if (!merchantId.trim()) {
      setError("Informe o ID do merchant iFood para listar os catálogos.")
      return
    }

    if (!tenantId) {
      setError(
        "Nenhum tenant vinculado ao usuário autenticado. Entre novamente ou selecione uma empresa."
      )
      return
    }

    setError(null)
    setSuccessMessage(null)
    setLoadingCatalogs(true)

    try {
      const response = await apiClient.get<IfoodCatalog[]>(
        ifoodEndpoints.catalogs,
        { merchant_id: merchantId.trim(), tenant_id: tenantId }
      )

      setCatalogs(response.data || [])
      setCategoriesByCatalog({})
      setUnsellableItemsByCatalog({})
      setGroupsByCatalog({})
      setSellableItems([])
      setGroupId("")
      setVersionInfo(null)
      setSuccessMessage(
        "Catálogos carregados diretamente da API iFood em tempo real."
      )
    } catch (err: any) {
      setCatalogs([])
      setError(err?.message || "Não foi possível carregar os catálogos.")
    } finally {
      setLoadingCatalogs(false)
    }
  }

  const fetchUnsellableItems = async (catalogId: string) => {
    if (!merchantId.trim() || !tenantId) {
      setError(
        "Informe o merchant iFood e garanta que há um tenant selecionado antes de listar itens indisponíveis."
      )
      return
    }

    setLoadingUnsellable(catalogId)
    setError(null)

    try {
      const response = await apiClient.get<any[]>(
        ifoodEndpoints.unsellableItems(catalogId),
        {
          merchant_id: merchantId.trim(),
          tenant_id: tenantId,
        }
      )

      setUnsellableItemsByCatalog((prev) => ({
        ...prev,
        [catalogId]: response.data || [],
      }))
    } catch (err: any) {
      setError(
        err?.message || "Falha ao buscar itens indisponíveis deste catálogo."
      )
    } finally {
      setLoadingUnsellable(null)
    }
  }

  const fetchGroups = async (catalogId: string) => {
    if (!merchantId.trim() || !tenantId) {
      setError(
        "Informe o merchant iFood e garanta que há um tenant selecionado antes de listar grupos."
      )
      return
    }

    setLoadingGroups(catalogId)
    setError(null)

    try {
      const response = await apiClient.get<any[]>(
        ifoodEndpoints.groups(catalogId),
        {
          merchant_id: merchantId.trim(),
          tenant_id: tenantId,
        }
      )

      setGroupsByCatalog((prev) => ({
        ...prev,
        [catalogId]: response.data || [],
      }))

      if (response.data?.length) {
        setSuccessMessage("Grupos do catálogo carregados com sucesso.")
      }
    } catch (err: any) {
      setError(err?.message || "Falha ao buscar grupos deste catálogo.")
    } finally {
      setLoadingGroups(null)
    }
  }

  const fetchSellableItems = async () => {
    if (!merchantId.trim() || !tenantId) {
      setError(
        "Informe o merchant iFood e garanta que há um tenant selecionado antes de listar itens vendáveis."
      )
      return
    }

    if (!groupId.trim()) {
      setError("Informe o identificador do groupId retornado pelo iFood.")
      return
    }

    setLoadingSellable(true)
    setError(null)

    try {
      const response = await apiClient.get<any[]>(
        ifoodEndpoints.sellableItems(groupId.trim()),
        {
          merchant_id: merchantId.trim(),
          tenant_id: tenantId,
        }
      )

      setSellableItems(response.data || [])
      if (response.data?.length) {
        setSuccessMessage("Itens vendáveis recuperados com sucesso.")
      }
    } catch (err: any) {
      setError(
        err?.message || "Falha ao buscar itens vendáveis para o groupId informado."
      )
      setSellableItems([])
    } finally {
      setLoadingSellable(false)
    }
  }

  const fetchCatalogVersion = async () => {
    if (!merchantId.trim() || !tenantId) {
      setError(
        "Informe o merchant iFood e garanta que há um tenant selecionado antes de consultar a versão."
      )
      return
    }

    setLoadingVersion(true)
    setError(null)

    try {
      const response = await apiClient.get<any>(
        ifoodEndpoints.catalogVersion,
        {
          merchant_id: merchantId.trim(),
          tenant_id: tenantId,
        }
      )

      setVersionInfo(response.data || null)
      setSuccessMessage("Versão do catálogo carregada com sucesso.")
    } catch (err: any) {
      setError(err?.message || "Não foi possível consultar a versão do catálogo.")
      setVersionInfo(null)
    } finally {
      setLoadingVersion(false)
    }
  }

  const fetchSnapshots = async (page = 1) => {
    if (!merchantId.trim() || !tenantId) {
      setError(
        "Informe o merchant iFood e garanta que há um tenant selecionado antes de consultar o histórico."
      )
      return
    }

    setLoadingSnapshots(true)
    setError(null)

    try {
      const response = await apiClient.get<{ data: any[]; meta: any }>(
        ifoodEndpoints.snapshots,
        {
          merchant_id: merchantId.trim(),
          tenant_id: tenantId,
          per_page: 5,
          page,
        }
      )

      setSnapshots(response.data?.data ?? [])
      setSnapshotsMeta(response.data?.meta ?? null)
    } catch (err: any) {
      setError(err?.message || "Não foi possível carregar o histórico de snapshots.")
      setSnapshots([])
      setSnapshotsMeta(null)
    } finally {
      setLoadingSnapshots(false)
    }
  }

  const fetchCategories = async (catalogId: string) => {
    if (!merchantId.trim()) return
    if (!tenantId) {
      setError(
        "Nenhum tenant vinculado ao usuário autenticado. Entre novamente ou selecione uma empresa."
      )
      return
    }

    setLoadingCategories(catalogId)
    setError(null)

    try {
      const response = await apiClient.get<IfoodCategory[]>(
        ifoodEndpoints.categories(catalogId),
        {
          merchant_id: merchantId.trim(),
          include_items: true,
          tenant_id: tenantId,
        }
      )

      setCategoriesByCatalog((prev) => ({
        ...prev,
        [catalogId]: response.data || [],
      }))
    } catch (err: any) {
      setError(
        err?.message || "Não foi possível obter as categorias deste catálogo."
      )
    } finally {
      setLoadingCategories(null)
    }
  }

  const renderOptionGroups = (optionGroups?: IfoodOptionGroup[]) => {
    if (!optionGroups || optionGroups.length === 0) {
      return (
        <p className="text-sm text-muted-foreground pl-2">
          Nenhuma opção adicional cadastrada para este item.
        </p>
      )
    }

    return (
      <div className="space-y-2">
        {optionGroups.map((group) => (
          <div key={group.id} className="rounded-md border p-3">
            <div className="flex items-center justify-between">
              <span className="font-medium">{group.name}</span>
              <span className="text-xs text-muted-foreground">
                Seleção mínima {group.min ?? 0} · máxima {group.max ?? 0}
              </span>
            </div>
            <ul className="mt-2 space-y-1">
              {group.options?.map((option) => (
                <li
                  key={option.id}
                  className="flex items-center justify-between text-sm"
                >
                  <div className="flex items-center gap-2">
                    <span>{option.name}</span>
                    {option.status && (
                      <Badge variant="outline">{option.status}</Badge>
                    )}
                  </div>
                  {option.price?.value !== undefined && (
                    <span className="text-xs text-muted-foreground">
                      + R$ {(option.price.value / 100).toFixed(2)}
                    </span>
                  )}
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
    )
  }

  return (
    <div className="space-y-6 px-4 lg:px-6">
      <div className="flex flex-col gap-2">
        <h1 className="text-3xl font-bold flex items-center gap-2">
          <UtensilsCrossed className="h-7 w-7" />
          Catálogos iFood
        </h1>
        <p className="text-muted-foreground">
          Consulte catálogos, categorias e itens cadastrados diretamente na API
          oficial do iFood. As informações exibidas abaixo são consultadas em
          tempo real.
        </p>
      </div>

      <Alert>
        <AlertTitle>Integração com fonte externa</AlertTitle>
        <AlertDescription>
          Os dados desta tela são fornecidos pela API pública do iFood. Um
          token válido precisa estar configurado e o ID do merchant deve ser
          informado para realizar as consultas.
        </AlertDescription>
      </Alert>

      <Card>
        <CardHeader>
          <CardTitle>Identificação do Merchant</CardTitle>
          <CardDescription>
            Informe o identificador único da loja no iFood (merchantId) para
            carregar os catálogos disponíveis.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Input
              placeholder="Ex.: 6b487a27-c4fc-4f26-b05e-3967c2331882"
              value={merchantId}
              onChange={(event) => setMerchantId(event.target.value)}
              className="flex-1"
            />
            <Button onClick={fetchCatalogs} disabled={loadingCatalogs}>
              {loadingCatalogs && (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              )}
              Buscar Catálogos
            </Button>
          </div>
          {error && (
            <p className="text-sm text-destructive font-medium">{error}</p>
          )}
          {successMessage && (
            <p className="text-sm text-muted-foreground">{successMessage}</p>
          )}
        </CardContent>
      </Card>

      {catalogs.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Catálogos encontrados</CardTitle>
            <CardDescription>
              Foram encontrados {catalogs.length} catálogo(s) ativos no merchant
              informado. Clique em &quot;Ver categorias&quot; para explorar os
              itens de cada catálogo.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex flex-wrap gap-2 items-center">
              <Badge variant="outline">Fonte: API iFood</Badge>
              <Button
                variant="outline"
                size="sm"
                onClick={fetchCatalogVersion}
                disabled={loadingVersion}
              >
                {loadingVersion && (
                  <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" />
                )}
                Consultar versão
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => fetchSnapshots()}
                disabled={loadingSnapshots}
              >
                {loadingSnapshots && (
                  <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" />
                )}
                Histórico de snapshots
              </Button>
              {versionInfo && (
                <span className="text-sm text-muted-foreground">
                  Versão:{" "}
                  <span className="font-medium text-foreground">
                    {versionInfo.version ?? "n/d"}
                  </span>
                  {versionInfo.lastUpdate && (
                    <>
                      {" "}
                      • Atualizado em{" "}
                      {new Date(versionInfo.lastUpdate).toLocaleString()}
                    </>
                  )}
                </span>
              )}
            </div>
            <div className="space-y-4">
              {catalogs.map((catalog) => (
                <div
                  key={catalog.catalogId}
                  className="rounded-lg border p-4 space-y-3"
                >
                  <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                      <h3 className="font-semibold text-lg">
                        Catálogo {catalog.catalogId}
                      </h3>
                      <div className="flex flex-wrap gap-2 mt-1">
                        {catalog.context?.map((context) => (
                          <Badge key={context} variant="secondary">
                            {context}
                          </Badge>
                        ))}
                        {catalog.status && (
                          <Badge
                            variant={
                              catalog.status === "AVAILABLE"
                                ? "default"
                                : "outline"
                            }
                          >
                            {catalog.status}
                          </Badge>
                        )}
                      </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                      <Button
                        variant="outline"
                        onClick={() => fetchCategories(catalog.catalogId)}
                        disabled={loadingCategories === catalog.catalogId}
                      >
                        {loadingCategories === catalog.catalogId && (
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        )}
                        Ver categorias
                      </Button>
                      <Button
                        variant="outline"
                        onClick={() => fetchUnsellableItems(catalog.catalogId)}
                        disabled={loadingUnsellable === catalog.catalogId}
                      >
                        {loadingUnsellable === catalog.catalogId && (
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        )}
                        Itens indisponíveis
                      </Button>
                      <Button
                        variant="outline"
                        onClick={() => fetchGroups(catalog.catalogId)}
                        disabled={loadingGroups === catalog.catalogId}
                      >
                        {loadingGroups === catalog.catalogId && (
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        )}
                        Listar grupos
                      </Button>
                    </div>
                  </div>

                  {categoriesByCatalog[catalog.catalogId] && (
                    <div className="space-y-4">
                      <Separator />
                      <div className="space-y-4">
                        {categoriesByCatalog[catalog.catalogId].map(
                          (category) => (
                            <div
                              key={category.id}
                              className="rounded-md border bg-muted/30 p-4 space-y-3"
                            >
                              <div className="flex items-center justify-between">
                                <div>
                                  <h4 className="text-base font-semibold">
                                    {category.name}
                                  </h4>
                                  {category.status && (
                                    <Badge variant="outline" className="mt-1">
                                      {category.status}
                                    </Badge>
                                  )}
                                </div>
                                <Badge variant="secondary">
                                  {category.items?.length || 0} itens
                                </Badge>
                              </div>

                              {category.items && category.items.length > 0 ? (
                                <div className="rounded-md border bg-background">
                                  <Table>
                                    <TableHeader>
                                      <TableRow>
                                        <TableHead>Item</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Código Externo</TableHead>
                                        <TableHead>Preço</TableHead>
                                      </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                      {category.items.map((item) => (
                                        <TableRow key={item.id}>
                                          <TableCell className="min-w-[200px]">
                                            <div className="flex flex-col">
                                              <span className="font-medium">
                                                {item.name}
                                              </span>
                                              {item.description && (
                                                <span className="text-xs text-muted-foreground">
                                                  {item.description}
                                                </span>
                                              )}
                                              {item.optionGroups &&
                                                item.optionGroups.length > 0 && (
                                                  <div className="mt-3 space-y-2">
                                                    <Badge variant="outline">
                                                      Complementos disponíveis
                                                    </Badge>
                                                    {renderOptionGroups(
                                                      item.optionGroups
                                                    )}
                                                  </div>
                                                )}
                                            </div>
                                          </TableCell>
                                          <TableCell>
                                            {item.status ? (
                                              <Badge variant="outline">
                                                {item.status}
                                              </Badge>
                                            ) : (
                                              "-"
                                            )}
                                          </TableCell>
                                          <TableCell>
                                            {item.externalCode || "-"}
                                          </TableCell>
                                          <TableCell>
                                            {item.price?.value !== undefined
                                              ? `R$ ${(item.price.value / 100).toFixed(2)}`
                                              : "-"}
                                          </TableCell>
                                        </TableRow>
                                      ))}
                                    </TableBody>
                                  </Table>
                                </div>
                              ) : (
                                <p className="text-sm text-muted-foreground">
                                  Nenhum item cadastrado nesta categoria.
                                </p>
                              )}

                            </div>
                          )
                        )}
                      </div>
                    </div>
                  )}

                  {unsellableItemsByCatalog[catalog.catalogId] && (
                    <div className="rounded-md border border-dashed p-4 space-y-3">
                      <h4 className="font-semibold text-sm">
                        Itens indisponíveis ({unsellableItemsByCatalog[catalog.catalogId].length})
                      </h4>
                      {unsellableItemsByCatalog[catalog.catalogId].length > 0 ? (
                        <ul className="space-y-2 text-sm text-muted-foreground">
                          {unsellableItemsByCatalog[catalog.catalogId].map((item) => (
                            <li key={item.id} className="flex flex-col">
                              <span className="text-foreground font-medium">
                                {item.name ?? item.id}
                              </span>
                              <span className="text-xs">
                                ID: {item.id}
                                {item.externalCode ? ` • Externo: ${item.externalCode}` : ""}
                              </span>
                              {item.reason && (
                                <span className="text-xs">Motivo: {item.reason}</span>
                              )}
                            </li>
                          ))}
                        </ul>
                      ) : (
                        <p className="text-sm text-muted-foreground">
                          Nenhum item indisponível retornado pelo iFood para este catálogo.
                        </p>
                      )}
                    </div>
                  )}

                  {groupsByCatalog[catalog.catalogId] && (
                    <div className="rounded-md border border-dashed p-4 space-y-3">
                      <h4 className="font-semibold text-sm">
                        Grupos disponíveis ({groupsByCatalog[catalog.catalogId].length})
                      </h4>
                      {groupsByCatalog[catalog.catalogId].length > 0 ? (
                        <div className="space-y-2 text-sm text-muted-foreground">
                          {groupsByCatalog[catalog.catalogId].map((group) => (
                            <div
                              key={group.id}
                              className="flex flex-col gap-1 rounded border bg-muted/20 p-3"
                            >
                              <div className="flex items-center justify-between">
                                <span className="text-foreground font-medium">
                                  {group.name ?? group.id}
                                </span>
                                <Button
                                  size="sm"
                                  variant="secondary"
                                  onClick={() => setGroupId(group.id ?? "")}
                                >
                                  Usar groupId
                                </Button>
                              </div>
                              <div className="text-xs space-y-1">
                                <p>ID: {group.id}</p>
                                {group.items_count !== null && (
                                  <p>Itens associados: {group.items_count ?? 0}</p>
                                )}
                              </div>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <p className="text-sm text-muted-foreground">
                          Nenhum grupo retornado pelo iFood para este catálogo.
                        </p>
                      )}
                    </div>
                  )}
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Itens vendáveis por grupo</CardTitle>
          <CardDescription>
            Consulte os itens atualmente disponibilizados para venda em um grupo específico do catálogo (groupId).
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-col gap-3">
            {availableGroups.length > 0 && (
              <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                <Select value={groupId} onValueChange={setGroupId}>
                  <SelectTrigger className="sm:w-72">
                    <SelectValue placeholder="Selecione um grupo carregado" />
                  </SelectTrigger>
                  <SelectContent>
                    {availableGroups.map((group) => (
                      <SelectItem key={group.id} value={group.id}>
                        {group.name ?? group.id}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <span className="text-xs text-muted-foreground">
                  Os grupos exibidos são retornados pelo catálogo iFood.
                </span>
              </div>
            )}
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
              <Input
                placeholder="Informe (ou confirme) o groupId retornado pelo iFood"
                value={groupId}
                onChange={(event) => setGroupId(event.target.value)}
                className="flex-1"
              />
              <Button onClick={fetchSellableItems} disabled={loadingSellable}>
                {loadingSellable && (
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                )}
                Listar itens vendáveis
              </Button>
            </div>
          </div>

          {sellableItems.length > 0 ? (
            <div className="space-y-2">
              <p className="text-sm text-muted-foreground">
                Total retornado: {sellableItems.length}
              </p>
              <div className="grid gap-2 md:grid-cols-2">
                {sellableItems.map((item) => (
                  <div key={item.id} className="rounded-md border bg-muted/20 p-4 space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="font-medium text-foreground">
                        {item.name ?? item.id}
                      </span>
                      <Badge variant="secondary">Vendável</Badge>
                    </div>
                    <div className="text-xs text-muted-foreground space-y-1">
                      <p>ID: {item.id}</p>
                      {item.externalCode && <p>Código externo: {item.externalCode}</p>}
                      {item.price?.value !== undefined && (
                        <p>Preço: R$ {(item.price.value / 100).toFixed(2)}</p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">
              Nenhum item vendável carregado. Informe um groupId e execute a busca.
            </p>
          )}
        </CardContent>
      </Card>

      {snapshots.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Histórico de snapshots</CardTitle>
            <CardDescription>
              Registros armazenados automaticamente após cada consulta aos endpoints do catálogo iFood.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="overflow-x-auto rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tipo</TableHead>
                    <TableHead>Catalog</TableHead>
                    <TableHead>Grupo</TableHead>
                    <TableHead>Capturado em</TableHead>
                    <TableHead>Dados (preview)</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {snapshots.map((snapshot) => (
                    <TableRow key={snapshot.id}>
                      <TableCell>
                        <Badge variant="outline">{snapshot.snapshot_type}</Badge>
                      </TableCell>
                      <TableCell className="text-xs">
                        {snapshot.catalog_id ?? "-"}
                      </TableCell>
                      <TableCell className="text-xs">
                        {snapshot.group_id ?? "-"}
                      </TableCell>
                      <TableCell className="text-xs">
                        {snapshot.captured_at
                          ? new Date(snapshot.captured_at).toLocaleString()
                          : "-"}
                      </TableCell>
                      <TableCell className="text-xs max-w-[280px] whitespace-pre-wrap">
                        {snapshot.payload
                          ? JSON.stringify(snapshot.payload).slice(0, 200)
                          : "-"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>

            {snapshotsMeta && (
              <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>
                  Página {snapshotsMeta.current_page} de {snapshotsMeta.last_page} • Total:{" "}
                  {snapshotsMeta.total}
                </span>
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    disabled={snapshotsMeta.current_page <= 1}
                    onClick={() => fetchSnapshots(snapshotsMeta.current_page - 1)}
                  >
                    Anterior
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    disabled={snapshotsMeta.current_page >= snapshotsMeta.last_page}
                    onClick={() => fetchSnapshots(snapshotsMeta.current_page + 1)}
                  >
                    Próxima
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  )
}

