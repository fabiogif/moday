"use client"

import { useState, useEffect } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import { Button } from "@/components/ui/button"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Separator } from "@/components/ui/separator"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Clock, Percent, DollarSign, Package, Truck, Save, AlertCircle, FileText, MapPin, Navigation, Plus, Pencil, Trash2 } from "lucide-react"
import { useToast } from "@/hooks/use-toast"
import { useAuth } from "@/contexts/auth-context"
import { apiClient, endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"
import { useDeliveryFeeZones, useDeliveryFeeZoneMutation, DeliveryFeeZone } from "@/hooks/use-delivery-fee-zones"
import { DeliveryFeeZoneFormDialog } from "./components/delivery-fee-zone-form-dialog"
import { DistanceBandsEditor, DistanceBand } from "./components/distance-bands-editor"
import { FEE_TYPE_OPTIONS, FeeType, formatFeeType } from "./components/fee-type"

interface DeliveryPickupSettings {
  // Retirada no Local
  pickup_enabled: boolean
  pickup_time_minutes: number
  pickup_discount_enabled: boolean
  pickup_discount_percent: number
  
  // Delivery
  delivery_enabled: boolean
  delivery_minimum_order_enabled: boolean
  delivery_minimum_order_value: number
  delivery_free_above_enabled: boolean
  delivery_free_above_value: number
}

const defaultSettings: DeliveryPickupSettings = {
  pickup_enabled: true,
  pickup_time_minutes: 35,
  pickup_discount_enabled: false,
  pickup_discount_percent: 0,
  delivery_enabled: true,
  delivery_minimum_order_enabled: true,
  delivery_minimum_order_value: 20.00,
  delivery_free_above_enabled: false,
  delivery_free_above_value: 300.00,
}

interface InvoiceDocumentSettings {
  ask_enabled: boolean
  required: boolean
}

const defaultInvoiceDocument: InvoiceDocumentSettings = {
  ask_enabled: false,
  required: false,
}

type DeliveryFeeMode = "unica" | "distancia" | "bairro"

interface SingleFeeSettings {
  fee_type: FeeType
  fee_value: number | null
  estimated_time_minutes: number
}

interface DeliveryFeeSettings {
  mode: DeliveryFeeMode
  unica: SingleFeeSettings
  distancia: { bands: DistanceBand[] }
}

const defaultDeliveryFee: DeliveryFeeSettings = {
  mode: "unica",
  unica: { fee_type: "fixed", fee_value: 8, estimated_time_minutes: 40 },
  distancia: { bands: [] },
}

export default function DeliverySettingsPage() {
  const { user } = useAuth()
  const { toast } = useToast()
  const [settings, setSettings] = useState<DeliveryPickupSettings>(defaultSettings)
  const [initialSettings, setInitialSettings] = useState<DeliveryPickupSettings>(defaultSettings)
  const [invoiceDocument, setInvoiceDocument] = useState<InvoiceDocumentSettings>(defaultInvoiceDocument)
  const [initialInvoiceDocument, setInitialInvoiceDocument] = useState<InvoiceDocumentSettings>(defaultInvoiceDocument)
  const [deliveryFee, setDeliveryFee] = useState<DeliveryFeeSettings>(defaultDeliveryFee)
  const [initialDeliveryFee, setInitialDeliveryFee] = useState<DeliveryFeeSettings>(defaultDeliveryFee)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [hasChanges, setHasChanges] = useState(false)

  // Zonas de taxa por bairro (CRUD próprio, fora do blob de settings)
  const { data: zones, refetch: refetchZones } = useDeliveryFeeZones()
  const { mutate: mutateZone } = useDeliveryFeeZoneMutation()
  const [zoneDialogOpen, setZoneDialogOpen] = useState(false)
  const [editingZone, setEditingZone] = useState<DeliveryFeeZone | null>(null)

  // Carregar configurações atuais
  useEffect(() => {
    const loadSettings = async () => {
      try {
        setLoading(true)

        // Buscar dados do tenant
        const response = await apiClient.get('/api/auth/me')

        if (response.success && response.data) {
          const userData = response.data as any
          const tenantSettings = userData.tenant?.settings || {}

          // Mesclar com defaults
          const loadedSettings = {
            ...defaultSettings,
            ...tenantSettings.delivery_pickup
          }
          const loadedInvoiceDocument = {
            ...defaultInvoiceDocument,
            ...tenantSettings.invoice_document,
          }
          const loadedDeliveryFee = {
            ...defaultDeliveryFee,
            ...tenantSettings.delivery_pickup?.delivery_fee,
            unica: {
              ...defaultDeliveryFee.unica,
              ...tenantSettings.delivery_pickup?.delivery_fee?.unica,
            },
            distancia: {
              bands: tenantSettings.delivery_pickup?.delivery_fee?.distancia?.bands ?? [],
            },
          }

          setSettings(loadedSettings)
          setInitialSettings(loadedSettings) // Salvar estado inicial
          setInvoiceDocument(loadedInvoiceDocument)
          setInitialInvoiceDocument(loadedInvoiceDocument)
          setDeliveryFee(loadedDeliveryFee)
          setInitialDeliveryFee(loadedDeliveryFee)
          setHasChanges(false) // Resetar flag de mudanças
        }
      } catch (error) {
        toast({
          title: "Erro",
          description: "Erro ao carregar configurações",
          variant: "destructive"
        })
      } finally {
        setLoading(false)
      }
    }

    loadSettings()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []) // Executar apenas uma vez ao montar

  // Detectar mudanças comparando com estado inicial
  useEffect(() => {
    const changed =
      JSON.stringify(settings) !== JSON.stringify(initialSettings) ||
      JSON.stringify(invoiceDocument) !== JSON.stringify(initialInvoiceDocument) ||
      JSON.stringify(deliveryFee) !== JSON.stringify(initialDeliveryFee)
    setHasChanges(changed)
  }, [settings, initialSettings, invoiceDocument, initialInvoiceDocument, deliveryFee, initialDeliveryFee])

  const handleSave = async () => {
    try {
      setSaving(true)

      // Buscar tenant UUID
      const userResponse = await apiClient.get<{ tenant?: { uuid?: string } }>('/api/auth/me')
      const tenantUuid = (userResponse.data as any)?.tenant?.uuid

      if (!tenantUuid) {
        throw new Error('Tenant não encontrado')
      }

      // Atualizar tenant com novas configurações
      const response = await apiClient.put(`/api/tenant/${tenantUuid}`, {
        settings: {
          delivery_pickup: {
            ...settings,
            delivery_fee: deliveryFee,
          },
          invoice_document: invoiceDocument,
        }
      })

      if (response.success) {
        toast({
          title: "Sucesso!",
          description: "Configurações de delivery e retirada salvas com sucesso",
        })
        setInitialSettings(settings) // Atualizar estado inicial
        setInitialInvoiceDocument(invoiceDocument)
        setInitialDeliveryFee(deliveryFee)
        setHasChanges(false)
      }
    } catch (error: any) {
      toast({
        title: "Erro",
        description: error.message || "Erro ao salvar configurações",
        variant: "destructive"
      })
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return <PageLoading isLoading={loading} message="Carregando configurações..." />
  }

  return (
    <div className="space-y-6 px-4 lg:px-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Delivery e Retirada</h1>
          <p className="text-muted-foreground">
            Configure como os clientes podem receber seus pedidos
          </p>
        </div>
        <Button
          onClick={handleSave}
          disabled={saving || !hasChanges}
          className="cursor-pointer"
        >
          <Save className="w-4 h-4 mr-2" />
          {saving ? "Salvando..." : "Salvar"}
        </Button>
      </div>

      {/* Alerta de Mudanças */}
      {hasChanges && (
        <Alert>
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            Você tem alterações não salvas. Clique em "Salvar" para aplicar.
          </AlertDescription>
        </Alert>
      )}

      {/* Retirada no Local */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <Package className="w-5 h-5" />
            <CardTitle>Retirada no Local</CardTitle>
          </div>
          <CardDescription>
            Configure as opções de retirada no estabelecimento
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Ativar Retirada */}
          <div className="flex items-center justify-between rounded-lg border p-4">
            <div className="space-y-0.5">
              <Label htmlFor="pickup_enabled" className="text-base">
                Permitir Retirada no Local
              </Label>
              <div className="text-sm text-muted-foreground">
                Clientes poderão escolher retirar o pedido no estabelecimento
              </div>
            </div>
            <Switch
              id="pickup_enabled"
              checked={settings.pickup_enabled}
              onCheckedChange={(checked) =>
                setSettings({ ...settings, pickup_enabled: checked })
              }
            />
          </div>

          {/* Tempo de Retirada */}
          <div className={`space-y-2 transition-opacity ${settings.pickup_enabled ? 'opacity-100' : 'opacity-50'}`}>
            <Label htmlFor="pickup_time_minutes" className="flex items-center gap-2">
              <Clock className="w-4 h-4" />
              Tempo para Retirada (minutos)
            </Label>
            <Input
              id="pickup_time_minutes"
              type="number"
              min="0"
              value={settings.pickup_time_minutes}
              onChange={(e) =>
                setSettings({
                  ...settings,
                  pickup_time_minutes: Number(e.target.value) || 0
                })
              }
              placeholder="35"
              disabled={!settings.pickup_enabled}
              className={!settings.pickup_enabled ? 'bg-muted cursor-not-allowed' : ''}
            />
            <p className="text-xs text-muted-foreground">
              Tempo estimado para o cliente retirar o pedido (padrão: 35 minutos)
            </p>
          </div>

          {/* Desconto para Retirada */}
          {settings.pickup_enabled && <Separator />}
          <div className={`space-y-4 transition-opacity ${settings.pickup_enabled ? 'opacity-100' : 'opacity-50'}`}>
            <div className="flex items-center justify-between rounded-lg border p-4">
              <div className="space-y-0.5">
                <Label htmlFor="pickup_discount_enabled" className="text-base">
                  Desconto para Retirada no Local
                </Label>
                <div className="text-sm text-muted-foreground">
                  Oferecer desconto percentual para quem retirar no local
                </div>
              </div>
              <Switch
                id="pickup_discount_enabled"
                checked={settings.pickup_discount_enabled}
                onCheckedChange={(checked) =>
                  setSettings({ ...settings, pickup_discount_enabled: checked })
                }
                disabled={!settings.pickup_enabled}
              />
            </div>

            <div className={`space-y-2 pl-6 border-l-2 transition-opacity ${
              settings.pickup_enabled && settings.pickup_discount_enabled ? 'opacity-100' : 'opacity-50'
            }`}>
              <Label htmlFor="pickup_discount_percent" className="flex items-center gap-2">
                <Percent className="w-4 h-4" />
                Percentual de Desconto (%)
              </Label>
              <Input
                id="pickup_discount_percent"
                type="number"
                min="0"
                max="100"
                step="0.01"
                value={settings.pickup_discount_percent}
                onChange={(e) =>
                  setSettings({
                    ...settings,
                    pickup_discount_percent: Number(e.target.value) || 0
                  })
                }
                placeholder="0"
                disabled={!settings.pickup_enabled || !settings.pickup_discount_enabled}
                className={!settings.pickup_enabled || !settings.pickup_discount_enabled ? 'bg-muted cursor-not-allowed' : ''}
              />
              <p className="text-xs text-muted-foreground">
                Desconto aplicado no total do pedido para retirada (ex: 10 = 10% off)
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Delivery */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <Truck className="w-5 h-5" />
            <CardTitle>Delivery (Entrega)</CardTitle>
          </div>
          <CardDescription>
            Configure as opções de entrega em domicílio
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Ativar Delivery */}
          <div className="flex items-center justify-between rounded-lg border p-4">
            <div className="space-y-0.5">
              <Label htmlFor="delivery_enabled" className="text-base">
                Permitir Delivery
              </Label>
              <div className="text-sm text-muted-foreground">
                Clientes poderão escolher receber o pedido em casa
              </div>
            </div>
            <Switch
              id="delivery_enabled"
              checked={settings.delivery_enabled}
              onCheckedChange={(checked) =>
                setSettings({ ...settings, delivery_enabled: checked })
              }
            />
          </div>

          {/* Pedido Mínimo */}
          {settings.delivery_enabled && <Separator />}
          <div className={`space-y-4 transition-opacity ${settings.delivery_enabled ? 'opacity-100' : 'opacity-50'}`}>
            <div className="flex items-center justify-between rounded-lg border p-4">
              <div className="space-y-0.5">
                <Label htmlFor="delivery_minimum_order_enabled" className="text-base">
                  Exigir Pedido Mínimo
                </Label>
                <div className="text-sm text-muted-foreground">
                  Definir valor mínimo para aceitar pedidos de delivery
                </div>
              </div>
              <Switch
                id="delivery_minimum_order_enabled"
                checked={settings.delivery_minimum_order_enabled}
                onCheckedChange={(checked) =>
                  setSettings({ ...settings, delivery_minimum_order_enabled: checked })
                }
                disabled={!settings.delivery_enabled}
              />
            </div>

            <div className={`space-y-2 pl-6 border-l-2 transition-opacity ${
              settings.delivery_enabled && settings.delivery_minimum_order_enabled ? 'opacity-100' : 'opacity-50'
            }`}>
              <Label htmlFor="delivery_minimum_order_value" className="flex items-center gap-2">
                <DollarSign className="w-4 h-4" />
                Valor Mínimo do Pedido (R$)
              </Label>
              <Input
                id="delivery_minimum_order_value"
                type="number"
                min="0"
                step="0.01"
                value={settings.delivery_minimum_order_value}
                onChange={(e) =>
                  setSettings({
                    ...settings,
                    delivery_minimum_order_value: Number(e.target.value) || 0
                  })
                }
                placeholder="20.00"
                disabled={!settings.delivery_enabled || !settings.delivery_minimum_order_enabled}
                className={!settings.delivery_enabled || !settings.delivery_minimum_order_enabled ? 'bg-muted cursor-not-allowed' : ''}
              />
              <p className="text-xs text-muted-foreground">
                Pedidos abaixo deste valor não poderão escolher delivery (padrão: R$ 20,00)
              </p>
            </div>
          </div>

          {/* Entrega Grátis */}
          {settings.delivery_enabled && <Separator />}
          <div className={`space-y-4 transition-opacity ${settings.delivery_enabled ? 'opacity-100' : 'opacity-50'}`}>
            <div className="flex items-center justify-between rounded-lg border p-4">
              <div className="space-y-0.5">
                <Label htmlFor="delivery_free_above_enabled" className="text-base">
                  Entrega Grátis Acima de
                </Label>
                <div className="text-sm text-muted-foreground">
                  Oferecer frete grátis para pedidos grandes
                </div>
              </div>
              <Switch
                id="delivery_free_above_enabled"
                checked={settings.delivery_free_above_enabled}
                onCheckedChange={(checked) =>
                  setSettings({ ...settings, delivery_free_above_enabled: checked })
                }
                disabled={!settings.delivery_enabled}
              />
            </div>

            <div className={`space-y-2 pl-6 border-l-2 transition-opacity ${
              settings.delivery_enabled && settings.delivery_free_above_enabled ? 'opacity-100' : 'opacity-50'
            }`}>
              <Label htmlFor="delivery_free_above_value" className="flex items-center gap-2">
                <DollarSign className="w-4 h-4" />
                Valor para Entrega Grátis (R$)
              </Label>
              <Input
                id="delivery_free_above_value"
                type="number"
                min="0"
                step="0.01"
                value={settings.delivery_free_above_value}
                onChange={(e) =>
                  setSettings({
                    ...settings,
                    delivery_free_above_value: Number(e.target.value) || 0
                  })
                }
                placeholder="300.00"
                disabled={!settings.delivery_enabled || !settings.delivery_free_above_enabled}
                className={!settings.delivery_enabled || !settings.delivery_free_above_enabled ? 'bg-muted cursor-not-allowed' : ''}
              />
              <p className="text-xs text-muted-foreground">
                Pedidos acima deste valor terão entrega gratuita (padrão: R$ 300,00)
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Documento na Nota (CPF/CNPJ) */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <FileText className="w-5 h-5" />
            <CardTitle>Documento na Nota</CardTitle>
          </div>
          <CardDescription>
            Configure se o cliente deve informar CPF ou CNPJ para emissão da nota
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="flex items-center justify-between rounded-lg border p-4">
            <div className="space-y-0.5">
              <Label htmlFor="invoice_ask_enabled" className="text-base">
                Perguntar CPF ou CNPJ na Nota
              </Label>
              <div className="text-sm text-muted-foreground">
                Exibir o campo de CPF/CNPJ no checkout
              </div>
            </div>
            <Switch
              id="invoice_ask_enabled"
              checked={invoiceDocument.ask_enabled}
              onCheckedChange={(checked) =>
                setInvoiceDocument({ ...invoiceDocument, ask_enabled: checked, required: checked ? invoiceDocument.required : false })
              }
            />
          </div>

          <div className={`space-y-2 pl-6 border-l-2 transition-opacity ${invoiceDocument.ask_enabled ? 'opacity-100' : 'opacity-50'}`}>
            <div className="flex items-center justify-between">
              <Label htmlFor="invoice_required" className="text-base">
                CPF ou CNPJ Obrigatório
              </Label>
              <Switch
                id="invoice_required"
                checked={invoiceDocument.required}
                onCheckedChange={(checked) => setInvoiceDocument({ ...invoiceDocument, required: checked })}
                disabled={!invoiceDocument.ask_enabled}
              />
            </div>
            <p className="text-xs text-muted-foreground">
              Se ativado, o pedido não poderá ser finalizado sem CPF ou CNPJ
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Taxa de Entrega */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <Navigation className="w-5 h-5" />
            <CardTitle>Taxa de Entrega</CardTitle>
          </div>
          <CardDescription>
            Escolha como a taxa de entrega será calculada para os pedidos
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="space-y-2">
            <Label>Modelo de Cobrança</Label>
            <Select
              value={deliveryFee.mode}
              onValueChange={(value: DeliveryFeeMode) => setDeliveryFee({ ...deliveryFee, mode: value })}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="unica">Taxa Única</SelectItem>
                <SelectItem value="distancia">Taxa por Distância</SelectItem>
                <SelectItem value="bairro">Taxa por Bairro</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {deliveryFee.mode === "unica" && (
            <div className="space-y-4 rounded-lg border p-4">
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label>Taxa</Label>
                  <Select
                    value={deliveryFee.unica.fee_type}
                    onValueChange={(value: FeeType) =>
                      setDeliveryFee({ ...deliveryFee, unica: { ...deliveryFee.unica, fee_type: value } })
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {FEE_TYPE_OPTIONS.map((option) => (
                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {deliveryFee.unica.fee_type === "fixed" && (
                  <div className="space-y-2">
                    <Label className="flex items-center gap-2">
                      <DollarSign className="w-4 h-4" />
                      Valor da Taxa (R$)
                    </Label>
                    <Input
                      type="number"
                      min="0"
                      step="0.01"
                      value={deliveryFee.unica.fee_value ?? 0}
                      onChange={(e) =>
                        setDeliveryFee({
                          ...deliveryFee,
                          unica: { ...deliveryFee.unica, fee_value: Number(e.target.value) || 0 },
                        })
                      }
                    />
                  </div>
                )}

                <div className="space-y-2">
                  <Label className="flex items-center gap-2">
                    <Clock className="w-4 h-4" />
                    Tempo Médio (min)
                  </Label>
                  <Input
                    type="number"
                    min="0"
                    value={deliveryFee.unica.estimated_time_minutes}
                    onChange={(e) =>
                      setDeliveryFee({
                        ...deliveryFee,
                        unica: { ...deliveryFee.unica, estimated_time_minutes: Number(e.target.value) || 0 },
                      })
                    }
                  />
                </div>
              </div>
            </div>
          )}

          {deliveryFee.mode === "distancia" && (
            <div className="space-y-3 rounded-lg border p-4">
              <p className="text-sm text-muted-foreground">
                Cadastre as faixas de distância (em km) e o valor cobrado em cada uma
              </p>
              <DistanceBandsEditor
                bands={deliveryFee.distancia.bands}
                onChange={(bands) => setDeliveryFee({ ...deliveryFee, distancia: { bands } })}
              />
            </div>
          )}

          {deliveryFee.mode === "bairro" && (
            <div className="space-y-3 rounded-lg border p-4">
              <div className="flex items-center justify-between">
                <p className="text-sm text-muted-foreground">
                  Cadastre a taxa de entrega para cada bairro atendido
                </p>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    setEditingZone(null)
                    setZoneDialogOpen(true)
                  }}
                >
                  <Plus className="w-4 h-4 mr-2" />
                  Adicionar Bairro
                </Button>
              </div>

              <div className="space-y-2">
                {(zones ?? []).length === 0 && (
                  <p className="text-sm text-muted-foreground py-4 text-center">
                    Nenhum bairro cadastrado ainda
                  </p>
                )}
                {(zones ?? []).map((zone) => (
                  <div key={zone.uuid} className="flex items-center justify-between rounded-lg border p-3">
                    <div className="flex items-center gap-2">
                      <MapPin className="w-4 h-4 text-muted-foreground" />
                      <div>
                        <p className="text-sm font-medium">{zone.neighborhood} — {zone.city}</p>
                        <p className="text-xs text-muted-foreground">
                          {formatFeeType(zone.fee_type, zone.fee_value)}
                          {" · "}{zone.estimated_time_minutes} min
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-1">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => {
                          setEditingZone(zone)
                          setZoneDialogOpen(true)
                        }}
                      >
                        <Pencil className="w-4 h-4" />
                      </Button>
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={async () => {
                          await mutateZone(endpoints.deliveryFeeZones.delete(zone.uuid), 'DELETE')
                          toast({ title: "Bairro removido" })
                          refetchZones()
                        }}
                      >
                        <Trash2 className="w-4 h-4 text-destructive" />
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      <DeliveryFeeZoneFormDialog
        open={zoneDialogOpen}
        onOpenChange={setZoneDialogOpen}
        zone={editingZone}
        onSuccess={() => {
          setZoneDialogOpen(false)
          refetchZones()
        }}
      />

      {/* Preview das Configurações */}
      <Card>
        <CardHeader>
          <CardTitle>Resumo das Configurações</CardTitle>
          <CardDescription>
            Veja como as configurações aparecerão para os clientes
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Retirada */}
          {settings.pickup_enabled ? (
            <div className="flex items-start gap-3 p-4 bg-muted/50 rounded-lg">
              <Package className="w-5 h-5 mt-0.5 text-primary" />
              <div className="flex-1">
                <p className="font-medium">Retirada no Local</p>
                <p className="text-sm text-muted-foreground">
                  Pronto em aproximadamente {settings.pickup_time_minutes} minutos
                </p>
                {settings.pickup_discount_enabled && settings.pickup_discount_percent > 0 && (
                  <p className="text-sm text-green-600 font-medium mt-1">
                    🎉 {settings.pickup_discount_percent}% de desconto
                  </p>
                )}
              </div>
            </div>
          ) : (
            <div className="flex items-start gap-3 p-4 bg-muted/20 rounded-lg opacity-50">
              <Package className="w-5 h-5 mt-0.5" />
              <div className="flex-1">
                <p className="font-medium">Retirada no Local</p>
                <p className="text-sm text-muted-foreground">Desabilitada</p>
              </div>
            </div>
          )}

          {/* Delivery */}
          {settings.delivery_enabled ? (
            <div className="flex items-start gap-3 p-4 bg-muted/50 rounded-lg">
              <Truck className="w-5 h-5 mt-0.5 text-primary" />
              <div className="flex-1">
                <p className="font-medium">Delivery (Entrega)</p>
                {settings.delivery_minimum_order_enabled && settings.delivery_minimum_order_value > 0 && (
                  <p className="text-sm text-muted-foreground">
                    Pedido mínimo: R$ {settings.delivery_minimum_order_value.toFixed(2)}
                  </p>
                )}
                {settings.delivery_free_above_enabled && settings.delivery_free_above_value > 0 && (
                  <p className="text-sm text-green-600 font-medium">
                    🎉 Frete grátis acima de R$ {settings.delivery_free_above_value.toFixed(2)}
                  </p>
                )}
              </div>
            </div>
          ) : (
            <div className="flex items-start gap-3 p-4 bg-muted/20 rounded-lg opacity-50">
              <Truck className="w-5 h-5 mt-0.5" />
              <div className="flex-1">
                <p className="font-medium">Delivery (Entrega)</p>
                <p className="text-sm text-muted-foreground">Desabilitado</p>
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}

