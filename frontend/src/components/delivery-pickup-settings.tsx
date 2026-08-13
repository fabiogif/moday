"use client"

import { useState, useEffect } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import { Button } from "@/components/ui/button"
import { Clock, Percent, DollarSign, Package, Truck } from "lucide-react"
import { useToast } from "@/hooks/use-toast"

export interface DeliveryPickupSettings {
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

interface DeliveryPickupSettingsProps {
  initialSettings?: Partial<DeliveryPickupSettings>
  onSave: (settings: DeliveryPickupSettings) => Promise<void>
  loading?: boolean
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

export function DeliveryPickupSettings({
  initialSettings,
  onSave,
  loading = false
}: DeliveryPickupSettingsProps) {
  const { toast } = useToast()
  const [settings, setSettings] = useState<DeliveryPickupSettings>({
    ...defaultSettings,
    ...initialSettings
  })
  const [saving, setSaving] = useState(false)

  // Atualizar quando initialSettings mudar
  useEffect(() => {
    if (initialSettings) {
      setSettings({
        ...defaultSettings,
        ...initialSettings
      })
    }
  }, [initialSettings])

  const handleSave = async () => {
    try {
      setSaving(true)
      await onSave(settings)
      toast({
        title: "Sucesso!",
        description: "Configurações salvas com sucesso",
      })
    } catch (error) {
      toast({
        title: "Erro",
        description: "Erro ao salvar configurações",
        variant: "destructive"
      })
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="space-y-6">
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
          <div className="flex items-center justify-between">
            <div className="space-y-0.5">
              <Label htmlFor="pickup_enabled">Permitir Retirada no Local</Label>
              <p className="text-sm text-muted-foreground">
                Clientes poderão escolher retirar o pedido no local
              </p>
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
          {settings.pickup_enabled && (
            <div className="space-y-2 pl-6 border-l-2">
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
                    pickup_time_minutes: Number(e.target.value)
                  })
                }
                placeholder="35"
              />
              <p className="text-xs text-muted-foreground">
                Tempo estimado para preparar o pedido para retirada
              </p>
            </div>
          )}

          {/* Desconto para Retirada */}
          {settings.pickup_enabled && (
            <>
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="pickup_discount_enabled">
                    Desconto para Retirada no Local
                  </Label>
                  <p className="text-sm text-muted-foreground">
                    Oferecer desconto para quem retirar no local
                  </p>
                </div>
                <Switch
                  id="pickup_discount_enabled"
                  checked={settings.pickup_discount_enabled}
                  onCheckedChange={(checked) =>
                    setSettings({ ...settings, pickup_discount_enabled: checked })
                  }
                />
              </div>

              {settings.pickup_discount_enabled && (
                <div className="space-y-2 pl-6 border-l-2">
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
                        pickup_discount_percent: Number(e.target.value)
                      })
                    }
                    placeholder="0"
                  />
                  <p className="text-xs text-muted-foreground">
                    Desconto aplicado no total do pedido para retirada
                  </p>
                </div>
              )}
            </>
          )}
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
          <div className="flex items-center justify-between">
            <div className="space-y-0.5">
              <Label htmlFor="delivery_enabled">Permitir Delivery</Label>
              <p className="text-sm text-muted-foreground">
                Clientes poderão escolher receber em casa
              </p>
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
          {settings.delivery_enabled && (
            <>
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="delivery_minimum_order_enabled">
                    Exigir Pedido Mínimo
                  </Label>
                  <p className="text-sm text-muted-foreground">
                    Definir valor mínimo para delivery
                  </p>
                </div>
                <Switch
                  id="delivery_minimum_order_enabled"
                  checked={settings.delivery_minimum_order_enabled}
                  onCheckedChange={(checked) =>
                    setSettings({ ...settings, delivery_minimum_order_enabled: checked })
                  }
                />
              </div>

              {settings.delivery_minimum_order_enabled && (
                <div className="space-y-2 pl-6 border-l-2">
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
                        delivery_minimum_order_value: Number(e.target.value)
                      })
                    }
                    placeholder="20.00"
                  />
                  <p className="text-xs text-muted-foreground">
                    Pedidos abaixo deste valor não poderão escolher delivery
                  </p>
                </div>
              )}
            </>
          )}

          {/* Entrega Grátis */}
          {settings.delivery_enabled && (
            <>
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="delivery_free_above_enabled">
                    Entrega Grátis Acima de
                  </Label>
                  <p className="text-sm text-muted-foreground">
                    Oferecer frete grátis para pedidos grandes
                  </p>
                </div>
                <Switch
                  id="delivery_free_above_enabled"
                  checked={settings.delivery_free_above_enabled}
                  onCheckedChange={(checked) =>
                    setSettings({ ...settings, delivery_free_above_enabled: checked })
                  }
                />
              </div>

              {settings.delivery_free_above_enabled && (
                <div className="space-y-2 pl-6 border-l-2">
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
                        delivery_free_above_value: Number(e.target.value)
                      })
                    }
                    placeholder="300.00"
                  />
                  <p className="text-xs text-muted-foreground">
                    Pedidos acima deste valor terão entrega gratuita
                  </p>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>

      {/* Botão de Salvar */}
      <div className="flex justify-end">
        <Button
          onClick={handleSave}
          disabled={saving || loading}
          className="cursor-pointer"
          size="lg"
        >
          {saving ? "Salvando..." : "Salvar Configurações"}
        </Button>
      </div>
    </div>
  )
}

