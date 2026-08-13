'use client'

import { useState, useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Event, EventFormData } from '@/hooks/use-events'
import { Calendar, Bell, MapPin, Clock, Palette, AlertCircle, Info } from 'lucide-react'
import { format, addMinutes } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'
import { extractValidationErrors } from '@/lib/error-formatter'

interface EventFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  event?: Event | null
  clients: any[]
  onSubmit: (data: EventFormData) => Promise<void>
  isLoading?: boolean
  defaultDate?: Date
}

const EVENT_COLORS = [
  { label: 'Azul', value: '#3b82f6' },
  { label: 'Verde', value: '#10b981' },
  { label: 'Vermelho', value: '#ef4444' },
  { label: 'Amarelo', value: '#f59e0b' },
  { label: 'Roxo', value: '#8b5cf6' },
  { label: 'Rosa', value: '#ec4899' },
  { label: 'Laranja', value: '#f97316' },
  { label: 'Ciano', value: '#06b6d4' },
  { label: 'Índigo', value: '#6366f1' },
  { label: 'Cinza', value: '#6b7280' },
]

export function EventFormDialog({
  open,
  onOpenChange,
  event,
  clients,
  onSubmit,
  isLoading,
  defaultDate,
}: EventFormDialogProps) {
  const [selectedClients, setSelectedClients] = useState<number[]>([])
  const [selectedChannels, setSelectedChannels] = useState<string[]>([])
  const [selectedColor, setSelectedColor] = useState(EVENT_COLORS[0].value)
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
    setValue,
    watch,
    clearErrors,
  } = useForm<EventFormData>({
    mode: 'onBlur', // Validar quando sair do campo
  })

  const eventType = watch('type')
  const startDate = watch('start_date')

  useEffect(() => {
    if (event) {
      setValue('title', event.title)
      setValue('type', event.type)
      setValue('start_date', event.start_date?.substring(0, 16)) // YYYY-MM-DDTHH:MM
      setValue('duration_minutes', event.duration_minutes)
      setValue('location', event.location || '')
      setValue('description', event.description)
      setSelectedColor(event.color || EVENT_COLORS[0].value)
      setSelectedClients(event.clients?.map(c => c.id) || [])
      setBackendErrors({})
    } else {
      reset()
      setSelectedClients([])
      setSelectedChannels([])
      setSelectedColor(EVENT_COLORS[0].value)
      setBackendErrors({})
      
      // Se houver data padrão, definir no formulário
      if (defaultDate) {
        const dateStr = format(defaultDate, "yyyy-MM-dd'T'HH:mm")
        setValue('start_date', dateStr)
      } else {
        // Data padrão: próxima hora
        const nextHour = addMinutes(new Date(), 60)
        nextHour.setMinutes(0, 0, 0)
        const dateStr = format(nextHour, "yyyy-MM-dd'T'HH:mm")
        setValue('start_date', dateStr)
      }
      
      // Duração padrão: 60 minutos
      setValue('duration_minutes', 60)
    }
  }, [event, defaultDate, setValue, reset, open])

  const handleFormSubmit = async (data: any) => {
    try {
      // Limpar erros anteriores
      setBackendErrors({})
      
      const formData: EventFormData = {
        ...data,
        color: selectedColor,
        client_ids: selectedClients,
        notification_channels: selectedChannels.length > 0 ? selectedChannels as any : undefined,
      }

      await onSubmit(formData)
      
      // Sucesso - resetar formulário
      reset()
      setSelectedClients([])
      setSelectedChannels([])
      setSelectedColor(EVENT_COLORS[0].value)
      setBackendErrors({})
    } catch (error: any) {
      // Extrair erros de validação do backend
      const validationErrors = extractValidationErrors(error)
      setBackendErrors(validationErrors)
      
      // Mostrar toast com resumo dos erros
      const errorCount = Object.keys(validationErrors).filter(k => k !== '_general').length
      if (validationErrors._general) {
        toast.error(validationErrors._general)
      } else if (errorCount > 0) {
        const firstError = Object.values(validationErrors)[0]
        toast.error(firstError, {
          description: errorCount > 1 ? `${errorCount - 1} outro(s) erro(s) de validação` : undefined
        })
      } else {
        toast.error('Erro ao salvar evento', {
          description: 'Por favor, verifique os campos e tente novamente'
        })
      }
      
      // Scroll para o topo do modal para ver os erros
      const dialogContent = document.querySelector('[role="dialog"]')
      if (dialogContent) {
        dialogContent.scrollTop = 0
      }
    }
  }

  const toggleClient = (clientId: number) => {
    setSelectedClients(prev =>
      prev.includes(clientId)
        ? prev.filter(id => id !== clientId)
        : [...prev, clientId]
    )
    // Limpar erro de clientes ao selecionar
    if (backendErrors.client_ids) {
      setBackendErrors(prev => {
        const newErrors = { ...prev }
        delete newErrors.client_ids
        return newErrors
      })
    }
  }

  const toggleChannel = (channel: string) => {
    setSelectedChannels(prev =>
      prev.includes(channel)
        ? prev.filter(c => c !== channel)
        : [...prev, channel]
    )
  }

  const hasError = (field: string) => {
    return errors[field as keyof EventFormData] || backendErrors[field]
  }

  const getErrorMessage = (field: string) => {
    const frontendError = errors[field as keyof EventFormData]?.message
    const backendError = backendErrors[field]
    return frontendError || backendError
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-2xl font-bold">
            {event ? 'Editar Evento' : 'Novo Evento'}
          </DialogTitle>
          <DialogDescription>
            Preencha os dados do evento. Campos marcados com * são obrigatórios.
          </DialogDescription>
        </DialogHeader>

        {/* Alerta de erros gerais */}
        {(backendErrors._general || Object.keys(backendErrors).length > 3) && (
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>
              {backendErrors._general || 'Por favor, corrija os erros abaixo antes de continuar.'}
            </AlertDescription>
          </Alert>
        )}

        <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">
          {/* Título */}
          <div className="space-y-2">
            <Label htmlFor="title" className="text-sm font-medium">
              Título do Evento *
            </Label>
            <Input
              id="title"
              {...register('title', { 
                required: 'O título do evento é obrigatório',
                maxLength: { value: 255, message: 'O título não pode ter mais de 255 caracteres' }
              })}
              placeholder="Ex: Black Friday 2025"
              className={cn(hasError('title') && 'border-destructive focus-visible:ring-destructive')}
              onFocus={() => {
                clearErrors('title')
                if (backendErrors.title) {
                  setBackendErrors(prev => {
                    const newErrors = { ...prev }
                    delete newErrors.title
                    return newErrors
                  })
                }
              }}
            />
            {hasError('title') && (
              <p className="text-sm text-destructive flex items-center gap-1">
                <AlertCircle className="h-3 w-3" />
                {getErrorMessage('title')}
              </p>
            )}
          </div>

          {/* Tipo e Cor */}
          <div className="grid md:grid-cols-2 gap-4">
            {/* Tipo */}
            <div className="space-y-2">
              <Label htmlFor="type" className="text-sm font-medium">
                Tipo *
              </Label>
              <Select
                value={eventType}
                onValueChange={(value) => {
                  setValue('type', value as any)
                  clearErrors('type')
                  if (backendErrors.type) {
                    setBackendErrors(prev => {
                      const newErrors = { ...prev }
                      delete newErrors.type
                      return newErrors
                    })
                  }
                }}
              >
                <SelectTrigger className={cn(hasError('type') && 'border-destructive')}>
                  <SelectValue placeholder="Selecione o tipo" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="promocao">🎉 Promoção</SelectItem>
                  <SelectItem value="aviso">📢 Aviso</SelectItem>
                  <SelectItem value="outro">📅 Outro</SelectItem>
                </SelectContent>
              </Select>
              {hasError('type') && (
                <p className="text-sm text-destructive flex items-center gap-1">
                  <AlertCircle className="h-3 w-3" />
                  {getErrorMessage('type')}
                </p>
              )}
            </div>

            {/* Cor */}
            <div className="space-y-2">
              <Label className="text-sm font-medium flex items-center gap-1">
                <Palette className="h-4 w-4" />
                Cor do Evento *
              </Label>
              <div className="grid grid-cols-5 gap-2 p-3 border rounded-md bg-muted/20">
                {EVENT_COLORS.map((color) => (
                  <button
                    key={color.value}
                    type="button"
                    onClick={() => setSelectedColor(color.value)}
                    className={cn(
                      'h-10 rounded-md border-2 transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
                      selectedColor === color.value
                        ? 'border-primary ring-2 ring-primary ring-offset-2 scale-110'
                        : 'border-transparent hover:border-muted-foreground'
                    )}
                    style={{ backgroundColor: color.value }}
                    title={color.label}
                    aria-label={`Selecionar cor ${color.label}`}
                  />
                ))}
              </div>
              <p className="text-xs text-muted-foreground flex items-center gap-1">
                <Info className="h-3 w-3" />
                Cor selecionada: <span className="font-medium">{EVENT_COLORS.find(c => c.value === selectedColor)?.label}</span>
              </p>
            </div>
          </div>

          {/* Data/Hora e Duração */}
          <div className="grid md:grid-cols-2 gap-4">
            {/* Data e Hora */}
            <div className="space-y-2">
              <Label htmlFor="start_date" className="text-sm font-medium flex items-center gap-1">
                <Calendar className="h-4 w-4" />
                Data e Hora *
              </Label>
              <Input
                id="start_date"
                type="datetime-local"
                min={format(new Date(), "yyyy-MM-dd'T'HH:mm")}
                {...register('start_date', { 
                  required: 'A data e hora são obrigatórias',
                  validate: (value) => {
                    if (!value) return 'A data e hora são obrigatórias'
                    const selectedDate = new Date(value)
                    const now = new Date()
                    // Permitir data até 1 minuto no passado (margem para latência)
                    if (selectedDate < new Date(now.getTime() - 60000)) {
                      return 'A data e hora devem ser iguais ou posteriores ao momento atual'
                    }
                    return true
                  }
                })}
                className={cn(hasError('start_date') && 'border-destructive focus-visible:ring-destructive')}
                onFocus={() => {
                  clearErrors('start_date')
                  if (backendErrors.start_date) {
                    setBackendErrors(prev => {
                      const newErrors = { ...prev }
                      delete newErrors.start_date
                      return newErrors
                    })
                  }
                }}
              />
              {hasError('start_date') && (
                <p className="text-sm text-destructive flex items-center gap-1">
                  <AlertCircle className="h-3 w-3" />
                  {getErrorMessage('start_date')}
                </p>
              )}
              {startDate && !hasError('start_date') && (
                <p className="text-xs text-muted-foreground">
                  {format(new Date(startDate), "EEEE, d 'de' MMMM 'às' HH:mm", { locale: ptBR })}
                </p>
              )}
            </div>

            {/* Duração */}
            <div className="space-y-2">
              <Label htmlFor="duration_minutes" className="text-sm font-medium flex items-center gap-1">
                <Clock className="h-4 w-4" />
                Duração (minutos) *
              </Label>
              <Input
                id="duration_minutes"
                type="number"
                min="1"
                max="1440"
                {...register('duration_minutes', {
                  required: 'A duração é obrigatória',
                  min: { value: 1, message: 'A duração mínima é de 1 minuto' },
                  max: { value: 1440, message: 'A duração máxima é de 24 horas (1440 minutos)' },
                  valueAsNumber: true,
                })}
                placeholder="Ex: 60"
                className={cn(hasError('duration_minutes') && 'border-destructive focus-visible:ring-destructive')}
                onFocus={() => {
                  clearErrors('duration_minutes')
                  if (backendErrors.duration_minutes) {
                    setBackendErrors(prev => {
                      const newErrors = { ...prev }
                      delete newErrors.duration_minutes
                      return newErrors
                    })
                  }
                }}
              />
              {hasError('duration_minutes') && (
                <p className="text-sm text-destructive flex items-center gap-1">
                  <AlertCircle className="h-3 w-3" />
                  {getErrorMessage('duration_minutes')}
                </p>
              )}
              {!hasError('duration_minutes') && (
                <p className="text-xs text-muted-foreground">
                  Sugestões: 30min, 60min (1h), 120min (2h)
                </p>
              )}
            </div>
          </div>

          {/* Local */}
          <div className="space-y-2">
            <Label htmlFor="location" className="text-sm font-medium flex items-center gap-1">
              <MapPin className="h-4 w-4" />
              Local <span className="text-muted-foreground">(opcional)</span>
            </Label>
            <Input
              id="location"
              {...register('location', {
                maxLength: { value: 255, message: 'O local não pode ter mais de 255 caracteres' }
              })}
              placeholder="Ex: Loja Física, Online, Rua das Flores 123"
              className={cn(hasError('location') && 'border-destructive focus-visible:ring-destructive')}
            />
            {hasError('location') && (
              <p className="text-sm text-destructive flex items-center gap-1">
                <AlertCircle className="h-3 w-3" />
                {getErrorMessage('location')}
              </p>
            )}
          </div>

          {/* Descrição */}
          <div className="space-y-2">
            <Label htmlFor="description" className="text-sm font-medium">
              Descrição *
            </Label>
            <Textarea
              id="description"
              {...register('description', {
                required: 'A descrição é obrigatória',
                minLength: { value: 10, message: 'A descrição deve ter pelo menos 10 caracteres' },
                maxLength: { value: 2000, message: 'A descrição não pode ter mais de 2000 caracteres' },
              })}
              placeholder="Descreva os detalhes do evento, promoções, informações importantes..."
              rows={4}
              className={cn(hasError('description') && 'border-destructive focus-visible:ring-destructive')}
              onFocus={() => {
                clearErrors('description')
                if (backendErrors.description) {
                  setBackendErrors(prev => {
                    const newErrors = { ...prev }
                    delete newErrors.description
                    return newErrors
                  })
                }
              }}
            />
            {hasError('description') && (
              <p className="text-sm text-destructive flex items-center gap-1">
                <AlertCircle className="h-3 w-3" />
                {getErrorMessage('description')}
              </p>
            )}
          </div>

          {/* Seleção de Clientes */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label className="text-sm font-medium">
                Clientes * <span className="text-muted-foreground">(Selecione pelo menos 1)</span>
              </Label>
              {clients.length > 0 && (
                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="select-all-clients"
                    checked={selectedClients.length === clients.length}
                    onCheckedChange={(checked) => {
                      if (checked) {
                        setSelectedClients(clients.map(c => c.id))
                      } else {
                        setSelectedClients([])
                      }
                      // Limpar erro de clientes ao selecionar
                      if (backendErrors.client_ids) {
                        setBackendErrors(prev => {
                          const newErrors = { ...prev }
                          delete newErrors.client_ids
                          return newErrors
                        })
                      }
                    }}
                  />
                  <label
                    htmlFor="select-all-clients"
                    className="text-xs font-medium cursor-pointer text-muted-foreground hover:text-foreground transition-colors"
                  >
                    Selecionar todos ({clients.length})
                  </label>
                </div>
              )}
            </div>
            <div className={cn(
              "border rounded-md p-4 max-h-48 overflow-y-auto space-y-2",
              hasError('client_ids') && 'border-destructive'
            )}>
              {clients.length === 0 ? (
                <div className="text-center py-4">
                  <p className="text-sm text-muted-foreground">Nenhum cliente cadastrado</p>
                  <p className="text-xs text-muted-foreground mt-1">
                    Cadastre clientes antes de criar eventos
                  </p>
                </div>
              ) : (
                clients.map((client) => (
                  <div 
                    key={client.id} 
                    className={cn(
                      "flex items-center space-x-3 p-2 rounded hover:bg-accent transition-colors",
                      selectedClients.includes(client.id) && "bg-accent"
                    )}
                  >
                    <Checkbox
                      id={`client-${client.id}`}
                      checked={selectedClients.includes(client.id)}
                      onCheckedChange={() => toggleClient(client.id)}
                    />
                    <label
                      htmlFor={`client-${client.id}`}
                      className="text-sm font-medium leading-none cursor-pointer flex-1"
                    >
                      <div>{client.name}</div>
                      <div className="text-xs text-muted-foreground">{client.email}</div>
                    </label>
                  </div>
                ))
              )}
            </div>
            {selectedClients.length > 0 && (
              <p className="text-xs text-green-600 flex items-center gap-1">
                ✓ {selectedClients.length} cliente(s) selecionado(s)
                {selectedClients.length === clients.length && ' - Todos'}
              </p>
            )}
            {hasError('client_ids') && (
              <p className="text-sm text-destructive flex items-center gap-1">
                <AlertCircle className="h-3 w-3" />
                {getErrorMessage('client_ids')}
              </p>
            )}
          </div>

          {/* Canais de Notificação */}
          <div className="space-y-2">
            <Label className="text-sm font-medium flex items-center gap-1">
              <Bell className="h-4 w-4" />
              Enviar Notificações <span className="text-muted-foreground">(opcional)</span>
            </Label>
            <div className="flex flex-wrap gap-4 p-3 border rounded-md bg-muted/20">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="channel-email"
                  checked={selectedChannels.includes('email')}
                  onCheckedChange={() => toggleChannel('email')}
                />
                <label htmlFor="channel-email" className="text-sm cursor-pointer font-medium">
                  📧 E-mail
                </label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="channel-whatsapp"
                  checked={selectedChannels.includes('whatsapp')}
                  onCheckedChange={() => toggleChannel('whatsapp')}
                />
                <label htmlFor="channel-whatsapp" className="text-sm cursor-pointer font-medium">
                  💬 WhatsApp
                </label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="channel-sms"
                  checked={selectedChannels.includes('sms')}
                  onCheckedChange={() => toggleChannel('sms')}
                />
                <label htmlFor="channel-sms" className="text-sm cursor-pointer font-medium">
                  📱 SMS
                </label>
              </div>
            </div>
            {selectedChannels.length > 0 && (
              <Alert>
                <Info className="h-4 w-4" />
                <AlertDescription className="text-xs">
                  As notificações serão enviadas imediatamente após salvar o evento para os {selectedClients.length} cliente(s) selecionado(s).
                </AlertDescription>
              </Alert>
            )}
          </div>

          <DialogFooter className="gap-2 sm:gap-0">
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isLoading}
            >
              Cancelar
            </Button>
            <Button 
              type="submit" 
              disabled={isLoading || selectedClients.length === 0 || clients.length === 0}
              className="min-w-[120px]"
            >
              {isLoading ? (
                <>
                  <Clock className="mr-2 h-4 w-4 animate-spin" />
                  Salvando...
                </>
              ) : (
                event ? 'Atualizar Evento' : 'Criar Evento'
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
