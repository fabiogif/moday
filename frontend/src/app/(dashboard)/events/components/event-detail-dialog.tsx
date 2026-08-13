'use client'

import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Event } from '@/hooks/use-events'
import { Calendar, Clock, MapPin, Users, Mail, MessageSquare, Smartphone, Edit, Trash2 } from 'lucide-react'
import { format, parseISO } from 'date-fns'
import { ptBR } from 'date-fns/locale'

interface EventDetailDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  event: Event | null
  onEdit?: (event: Event) => void
  onDelete?: (event: Event) => void
  isDeleting?: boolean
}

export function EventDetailDialog({
  open,
  onOpenChange,
  event,
  onEdit,
  onDelete,
  isDeleting,
}: EventDetailDialogProps) {
  if (!event) return null

  const notificationChannels = []
  if (event.notifications_sent) {
    // Assumindo que há informações sobre quais canais foram usados
    notificationChannels.push('Email', 'WhatsApp', 'SMS')
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <DialogTitle className="text-2xl">{event.title}</DialogTitle>
              <div className="flex items-center gap-2 mt-2">
                <Badge
                  className="text-xs"
                  style={{
                    backgroundColor: event.color || '#3b82f6',
                    color: '#ffffff',
                  }}
                >
                  {event.type_label}
                </Badge>
                <Badge variant={event.is_active ? 'default' : 'secondary'}>
                  {event.is_active ? 'Ativo' : 'Inativo'}
                </Badge>
              </div>
            </div>
          </div>
        </DialogHeader>

        <div className="space-y-4">
          {/* Data e Hora */}
          <div className="flex items-start gap-3">
            <Calendar className="h-5 w-5 text-muted-foreground mt-0.5" />
            <div>
              <p className="font-medium">Data e Hora</p>
              <p className="text-sm text-muted-foreground">
                {format(parseISO(event.start_date), "EEEE, d 'de' MMMM 'de' yyyy 'às' HH:mm", {
                  locale: ptBR,
                })}
              </p>
            </div>
          </div>

          {/* Duração */}
          <div className="flex items-start gap-3">
            <Clock className="h-5 w-5 text-muted-foreground mt-0.5" />
            <div>
              <p className="font-medium">Duração</p>
              <p className="text-sm text-muted-foreground">
                {event.duration_minutes} minutos
                {event.end_date && (
                  <> • Término às {format(parseISO(event.end_date), 'HH:mm')}</>
                )}
              </p>
            </div>
          </div>

          {/* Local */}
          {event.location && (
            <div className="flex items-start gap-3">
              <MapPin className="h-5 w-5 text-muted-foreground mt-0.5" />
              <div>
                <p className="font-medium">Local</p>
                <p className="text-sm text-muted-foreground">{event.location}</p>
              </div>
            </div>
          )}

          {/* Descrição */}
          <div className="space-y-2">
            <p className="font-medium">Descrição</p>
            <p className="text-sm text-muted-foreground whitespace-pre-wrap">
              {event.description}
            </p>
          </div>

          {/* Clientes */}
          <div className="flex items-start gap-3">
            <Users className="h-5 w-5 text-muted-foreground mt-0.5" />
            <div className="flex-1">
              <p className="font-medium mb-2">
                Clientes ({event.clients_count})
              </p>
              {event.clients && event.clients.length > 0 ? (
                <div className="space-y-1 max-h-32 overflow-y-auto">
                  {event.clients.map((client: any) => (
                    <div
                      key={client.id}
                      className="text-sm text-muted-foreground border rounded p-2"
                    >
                      <div className="font-medium text-foreground">{client.name}</div>
                      <div className="text-xs">{client.email}</div>
                      {client.phone && <div className="text-xs">{client.phone}</div>}
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">
                  Nenhum cliente associado
                </p>
              )}
            </div>
          </div>

          {/* Status de Notificações */}
          <div className="border-t pt-4">
            <p className="font-medium mb-2">Status de Notificações</p>
            {event.notifications_sent ? (
              <div className="space-y-2">
                <Badge variant="default" className="bg-green-500">
                  ✓ Notificações Enviadas
                </Badge>
                <div className="flex flex-wrap gap-2 mt-2">
                  {event.clients?.some((c: any) => c.pivot?.email_sent) && (
                    <div className="flex items-center gap-1 text-sm text-muted-foreground">
                      <Mail className="h-4 w-4" />
                      <span>E-mail</span>
                    </div>
                  )}
                  {event.clients?.some((c: any) => c.pivot?.whatsapp_sent) && (
                    <div className="flex items-center gap-1 text-sm text-muted-foreground">
                      <MessageSquare className="h-4 w-4" />
                      <span>WhatsApp</span>
                    </div>
                  )}
                  {event.clients?.some((c: any) => c.pivot?.sms_sent) && (
                    <div className="flex items-center gap-1 text-sm text-muted-foreground">
                      <Smartphone className="h-4 w-4" />
                      <span>SMS</span>
                    </div>
                  )}
                </div>
              </div>
            ) : (
              <Badge variant="secondary">
                Nenhuma notificação enviada
              </Badge>
            )}
          </div>
        </div>

        <DialogFooter className="flex justify-between sm:justify-between">
          <div className="flex gap-2">
            {onEdit && (
              <Button
                variant="outline"
                onClick={() => {
                  onEdit(event)
                  onOpenChange(false)
                }}
              >
                <Edit className="mr-2 h-4 w-4" />
                Editar
              </Button>
            )}
            {onDelete && (
              <Button
                variant="destructive"
                onClick={() => onDelete(event)}
                disabled={isDeleting}
              >
                <Trash2 className="mr-2 h-4 w-4" />
                {isDeleting ? 'Excluindo...' : 'Excluir'}
              </Button>
            )}
          </div>
          <Button variant="default" onClick={() => onOpenChange(false)}>
            Fechar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
