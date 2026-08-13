'use client'

import { useOrderNotifications } from '@/contexts/order-notifications-context'
import { Switch } from '@/components/ui/switch'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Volume2, VolumeX, Play } from 'lucide-react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { playNotificationSound } from '@/lib/notification-sound'
import { toast } from 'sonner'

export function OrderNotificationToggle() {
  const { soundEnabled, setSoundEnabled } = useOrderNotifications()

  const handleTestSound = () => {
    playNotificationSound()
    toast.success('Som de notificação reproduzido!', {
      duration: 2000,
    })
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          {soundEnabled ? (
            <Volume2 className="h-5 w-5 text-green-600" />
          ) : (
            <VolumeX className="h-5 w-5 text-muted-foreground" />
          )}
          Notificações de Pedidos
        </CardTitle>
        <CardDescription>
          Configure alertas sonoros e visuais para novos pedidos
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Toggle de Som */}
        <div className="flex items-center justify-between rounded-lg border p-4">
          <div className="space-y-0.5">
            <Label className="text-base font-medium">Som de Notificação</Label>
            <div className="text-sm text-muted-foreground">
              Tocar som quando um novo pedido for recebido
            </div>
          </div>
          <Switch
            checked={soundEnabled}
            onCheckedChange={setSoundEnabled}
          />
        </div>

        {/* Botão de Teste */}
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={handleTestSound}
            disabled={!soundEnabled}
          >
            <Play className="h-4 w-4 mr-2" />
            Testar Som
          </Button>
          <p className="text-xs text-muted-foreground">
            {soundEnabled
              ? 'Clique para ouvir o som de notificação'
              : 'Ative o som para testar'}
          </p>
        </div>

        {/* Informações adicionais */}
        <div className="rounded-lg bg-muted/50 p-3">
          <p className="text-xs text-muted-foreground">
            <strong>ℹ️ Como funciona:</strong><br />
            • As notificações aparecem automaticamente quando um novo pedido é criado<br />
            • O som toca apenas uma vez por pedido<br />
            • Você pode clicar em "Ver Pedido" para abrir diretamente<br />
            • As notificações permanecem visíveis por 10 segundos
          </p>
        </div>
      </CardContent>
    </Card>
  )
}

