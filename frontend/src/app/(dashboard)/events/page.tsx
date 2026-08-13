'use client'

import { useState, useMemo } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { useEvents, useEventStats, useEventMutation, Event } from '@/hooks/use-events'
import { useAuthenticatedApi } from '@/hooks/use-authenticated-api'
import { endpoints } from '@/lib/api-client'
import { EventFormDialog } from './components/event-form-dialog'
import { EventDetailDialog } from './components/event-detail-dialog'
import { 
  Plus, CalendarDays, TrendingUp, Clock, Bell, Loader2, 
  ChevronLeft, ChevronRight, Search, X, Trash2 
} from 'lucide-react'
import { 
  format, startOfMonth, endOfMonth, eachDayOfInterval, 
  isSameMonth, isSameDay, parseISO, addMonths, subMonths,
  startOfWeek, endOfWeek, isToday, isBefore, startOfDay
} from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'

export default function EventsPage() {
  const [selectedMonth, setSelectedMonth] = useState<Date>(new Date())
  const [selectedDate, setSelectedDate] = useState<Date | undefined>()
  const [formDialogOpen, setFormDialogOpen] = useState(false)
  const [detailDialogOpen, setDetailDialogOpen] = useState(false)
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null)
  const [eventToDelete, setEventToDelete] = useState<Event | null>(null)
  const [searchQuery, setSearchQuery] = useState('')

  // Buscar eventos do mês selecionado
  const startDate = format(startOfMonth(selectedMonth), 'yyyy-MM-dd')
  const endDate = format(endOfMonth(selectedMonth), 'yyyy-MM-dd')
  
  const { data: events, loading: eventsLoading, refetch: refetchEvents } = useEvents(startDate, endDate)
  const { data: stats, refetch: refetchStats } = useEventStats()
  const { data: clients } = useAuthenticatedApi<any[]>(endpoints.clients.list, { immediate: true })
  const { mutate, loading: mutating } = useEventMutation()

  // Filtrar eventos pela busca
  const filteredEvents = useMemo(() => {
    if (!events) return []
    if (!searchQuery.trim()) return events
    
    const query = searchQuery.toLowerCase()
    return events.filter(event => 
      event.title.toLowerCase().includes(query) ||
      event.description.toLowerCase().includes(query) ||
      event.location?.toLowerCase().includes(query)
    )
  }, [events, searchQuery])

  // Agrupar eventos por data
  const eventsByDate = useMemo(() => {
    return (filteredEvents || []).reduce((acc, event) => {
      const dateKey = format(parseISO(event.start_date), 'yyyy-MM-dd')
      if (!acc[dateKey]) acc[dateKey] = []
      acc[dateKey].push(event)
      return acc
    }, {} as Record<string, Event[]>)
  }, [filteredEvents])

  // Gerar dias do calendário (incluindo dias adjacentes para preencher semanas)
  const calendarDays = useMemo(() => {
    const start = startOfWeek(startOfMonth(selectedMonth), { locale: ptBR })
    const end = endOfWeek(endOfMonth(selectedMonth), { locale: ptBR })
    return eachDayOfInterval({ start, end })
  }, [selectedMonth])

  const handleCreateEvent = (date?: Date) => {
    setSelectedEvent(null)
    setSelectedDate(date)
    setFormDialogOpen(true)
  }

  const handleEditEvent = (event: Event) => {
    setSelectedEvent(event)
    setFormDialogOpen(true)
  }

  const handleViewEvent = (event: Event) => {
    setSelectedEvent(event)
    setDetailDialogOpen(true)
  }

  const handleSubmit = async (data: any) => {
    try {
      if (selectedEvent) {
        await mutate(endpoints.events.update(selectedEvent.uuid), 'PUT', data)
        toast.success('Evento atualizado com sucesso!')
      } else {
        await mutate(endpoints.events.create, 'POST', data)
        toast.success('Evento criado com sucesso!')
      }
      
      // Sucesso: fechar modal imediatamente
      setFormDialogOpen(false)
      setSelectedEvent(null)
      setSelectedDate(undefined)
      
      // Recarregar dados em paralelo

      await Promise.all([
        refetchEvents(),
        refetchStats(),
      ])

    } catch (error: any) {
      // Erro já foi tratado no EventFormDialog
      // Apenas re-lançar para que o formulário possa processar

      throw error
    }
  }

  const handleDeleteClick = (event: Event) => {
    setEventToDelete(event)
    setDeleteDialogOpen(true)
  }

  const handleConfirmDelete = async () => {
    if (!eventToDelete) return

    try {
      await mutate(endpoints.events.delete(eventToDelete.uuid), 'DELETE')
      toast.success('Evento excluído com sucesso!')
      setDeleteDialogOpen(false)
      setDetailDialogOpen(false)
      setEventToDelete(null)
      
      // Recarregar eventos e estatísticas
      await Promise.all([
        refetchEvents(),
        refetchStats(),
      ])
    } catch (error: any) {
      toast.error(error.message || 'Erro ao excluir evento')
    }
  }

  const previousMonth = () => setSelectedMonth(subMonths(selectedMonth, 1))
  const nextMonth = () => setSelectedMonth(addMonths(selectedMonth, 1))
  const goToToday = () => setSelectedMonth(new Date())

  const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']

  return (
    <div className="flex flex-col gap-4">
      {/* Estatísticas */}
      {stats && (
        <div className="@container/main px-4 lg:px-6">
          <div className="grid gap-4 md:grid-cols-5">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total</CardTitle>
                <CalendarDays className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.total}</div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Ativos</CardTitle>
                <TrendingUp className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.active}</div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Próximos</CardTitle>
                <Clock className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.upcoming}</div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Passados</CardTitle>
                <CalendarDays className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.past}</div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Notificados</CardTitle>
                <Bell className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.notifications_sent}</div>
              </CardContent>
            </Card>
          </div>
        </div>
      )}

      {/* Header e Campo de Busca */}
      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Eventos</h1>
            <p className="text-muted-foreground">
              Gerencie eventos, promoções e avisos para seus clientes
            </p>
          </div>
          <Button onClick={() => handleCreateEvent()} size="lg">
            <Plus className="mr-2 h-5 w-5" />
            Novo Evento
          </Button>
        </div>

        {/* Campo de Busca */}
        <Card>
          <CardContent className="pt-6">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Buscar eventos por nome, descrição ou local..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10 pr-10"
              />
              {searchQuery && (
                <button
                  onClick={() => setSearchQuery('')}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                  <X className="h-4 w-4" />
                </button>
              )}
            </div>
            {searchQuery && filteredEvents && (
              <p className="text-sm text-muted-foreground mt-2">
                {filteredEvents.length} evento(s) encontrado(s)
              </p>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Calendário Estilo Google Calendar */}
      <div className="@container/main px-4 lg:px-6">
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <CardTitle className="text-2xl">
                  {format(selectedMonth, 'MMMM yyyy', { locale: ptBR })}
                </CardTitle>
                <Button variant="outline" size="sm" onClick={goToToday}>
                  Hoje
                </Button>
              </div>
              <div className="flex items-center gap-2">
                <Button variant="outline" size="icon" onClick={previousMonth}>
                  <ChevronLeft className="h-4 w-4" />
                </Button>
                <Button variant="outline" size="icon" onClick={nextMonth}>
                  <ChevronRight className="h-4 w-4" />
                </Button>
              </div>
            </div>
            <CardDescription>
              Clique em um dia para adicionar evento ou em um evento para ver detalhes
            </CardDescription>
          </CardHeader>
          <CardContent>
            {eventsLoading ? (
              <div className="flex items-center justify-center h-96">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
              </div>
            ) : (
              <div className="border rounded-lg overflow-hidden">
                {/* Cabeçalho dos dias da semana */}
                <div className="grid grid-cols-7 bg-muted/50">
                {weekDays.map((day) => (
                  <div
                    key={day}
                    className="p-2 text-center text-sm font-medium border-r last:border-r-0"
                  >
                    {day}
                  </div>
                ))}
              </div>

              {/* Grade de dias */}
              <div className="grid grid-cols-7">
                {calendarDays.map((day, idx) => {
                  const dateKey = format(day, 'yyyy-MM-dd')
                  const dayEvents = eventsByDate[dateKey] || []
                  const isCurrentMonth = isSameMonth(day, selectedMonth)
                  const isCurrentDay = isToday(day)
                  const isPastDay = isBefore(startOfDay(day), startOfDay(new Date()))
                  const isDisabled = isPastDay

                  return (
                    <div
                      key={idx}
                      className={cn(
                        'min-h-[120px] border-r border-b p-2 transition-colors',
                        !isDisabled && 'hover:bg-accent/50 cursor-pointer',
                        !isCurrentMonth && 'bg-muted/20 text-muted-foreground',
                        isDisabled && 'bg-muted/40 cursor-not-allowed opacity-50',
                        'last:border-r-0'
                      )}
                      onClick={() => !isDisabled && handleCreateEvent(day)}
                      title={isDisabled ? 'Data passada - não é possível criar eventos' : 'Clique para criar evento'}
                    >
                      {/* Número do dia */}
                      <div className="flex items-center justify-between mb-1">
                        <span
                          className={cn(
                            'text-sm font-medium flex items-center justify-center w-7 h-7 rounded-full',
                            isCurrentDay && 'bg-primary text-primary-foreground'
                          )}
                        >
                          {format(day, 'd')}
                        </span>
                        {dayEvents.length > 3 && (
                          <Badge variant="secondary" className="text-xs h-5 px-1">
                            +{dayEvents.length - 3}
                          </Badge>
                        )}
                      </div>

                      {/* Lista de eventos (máx 3 visíveis) */}
                      <div className="space-y-1">
                        {dayEvents.slice(0, 3).map((event) => (
                          <div
                            key={event.id}
                            onClick={(e) => {
                              e.stopPropagation()
                              handleViewEvent(event)
                            }}
                            className={cn(
                              'text-xs p-1.5 rounded truncate transition-all',
                              'hover:shadow-md hover:scale-105 cursor-pointer'
                            )}
                            style={{
                              backgroundColor: event.color || '#3b82f6',
                              color: '#ffffff',
                            }}
                            title={event.title}
                          >
                            <div className="flex items-center gap-1">
                              <Clock className="h-3 w-3 shrink-0" />
                              <span className="font-medium truncate">
                                {format(parseISO(event.start_date), 'HH:mm')} {event.title}
                              </span>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  )
                })}
              </div>
            </div>
          )}
          </CardContent>
        </Card>
      </div>

      {/* Dialogs */}
      <EventFormDialog
        open={formDialogOpen}
        onOpenChange={setFormDialogOpen}
        event={selectedEvent}
        clients={clients || []}
        onSubmit={handleSubmit}
        isLoading={mutating}
        defaultDate={selectedDate}
      />

      <EventDetailDialog
        open={detailDialogOpen}
        onOpenChange={setDetailDialogOpen}
        event={selectedEvent}
        onEdit={handleEditEvent}
        onDelete={handleDeleteClick}
        isDeleting={mutating}
      />

      {/* AlertDialog de Confirmação de Exclusão */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle className="flex items-center gap-2">
              <Trash2 className="h-5 w-5 text-destructive" />
              Excluir Evento
            </AlertDialogTitle>
            <AlertDialogDescription className="space-y-2">
              <p>
                Tem certeza que deseja excluir o evento <strong>"{eventToDelete?.title}"</strong>?
              </p>
              {eventToDelete && (
                <div className="mt-3 p-3 bg-muted rounded-md text-sm space-y-1">
                  <p className="flex items-center gap-2">
                    <CalendarDays className="h-4 w-4" />
                    <span>
                      {format(parseISO(eventToDelete.start_date), "d 'de' MMMM 'de' yyyy 'às' HH:mm", { locale: ptBR })}
                    </span>
                  </p>
                  <p className="flex items-center gap-2">
                    <Bell className="h-4 w-4" />
                    <span>{eventToDelete.clients_count} cliente(s) associado(s)</span>
                  </p>
                </div>
              )}
              <p className="text-destructive font-medium mt-3">
                Esta ação não pode ser desfeita.
              </p>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={mutating}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleConfirmDelete}
              disabled={mutating}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {mutating ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Excluindo...
                </>
              ) : (
                <>
                  <Trash2 className="mr-2 h-4 w-4" />
                  Excluir Evento
                </>
              )}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
