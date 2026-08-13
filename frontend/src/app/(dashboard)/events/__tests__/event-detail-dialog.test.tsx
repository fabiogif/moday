import React from 'react'
import { render, screen } from '@testing-library/react'
import '@testing-library/jest-dom'
import { EventDetailDialog } from '../components/event-detail-dialog'

const mockEvent = {
  id: 1,
  uuid: 'event-uuid-1',
  title: 'Black Friday 2025',
  type: 'promocao' as const,
  type_label: 'Promoção',
  start_date: '2025-11-25T09:00:00.000Z',
  start_date_formatted: '25/11/2025 09:00',
  end_date: '2025-11-25T17:00:00.000Z',
  end_date_formatted: '25/11/2025 17:00',
  duration_minutes: 480,
  location: 'Loja Física',
  description: 'Mega promoção de Black Friday com até 70% de desconto!',
  is_active: true,
  notifications_sent: true,
  clients_count: 3,
  created_at: '2025-10-30T10:00:00.000Z',
  updated_at: '2025-10-30T10:00:00.000Z',
  clients: [
    { id: 1, name: 'Cliente A', email: 'clientea@example.com' },
    { id: 2, name: 'Cliente B', email: 'clienteb@example.com' },
    { id: 3, name: 'Cliente C', email: 'clientec@example.com' },
  ],
}

describe('EventDetailDialog', () => {
  const mockOnOpenChange = jest.fn()

  it('should not render when event is null', () => {
    const { container } = render(
      <EventDetailDialog open={true} onOpenChange={mockOnOpenChange} event={null} />
    )

    expect(container).toBeEmptyDOMElement()
  })

  it('should render event title and type', () => {
    render(
      <EventDetailDialog open={true} onOpenChange={mockOnOpenChange} event={mockEvent} />
    )

    expect(screen.getByText('Black Friday 2025')).toBeInTheDocument()
    expect(screen.getByText('Promoção')).toBeInTheDocument()
  })

  it('should display event date and time', () => {
    render(
      <EventDetailDialog open={true} onOpenChange={mockOnOpenChange} event={mockEvent} />
    )

    expect(screen.getByText('Data e Hora')).toBeInTheDocument()
    expect(screen.getByText(/de 2025/i)).toBeInTheDocument()
  })

  it('should display event duration', () => {
    render(
      <EventDetailDialog open={true} onOpenChange={mockOnOpenChange} event={mockEvent} />
    )

    expect(screen.getByText('Duração')).toBeInTheDocument()
    expect(screen.getByText(/480 minutos/)).toBeInTheDocument()
  })

  it('should display event location when available', () => {
    render(
      <EventDetailDialog open={true} onOpenChange={mockOnOpenChange} event={mockEvent} />
    )

    expect(screen.getByText('Local')).toBeInTheDocument()
    expect(screen.getByText('Loja Física')).toBeInTheDocument()
  })

  it('should not display location section when location is null', () => {
    const eventWithoutLocation = { ...mockEvent, location: undefined }
    
    render(
      <EventDetailDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        event={eventWithoutLocation}
      />
    )

    expect(screen.queryByText('Local')).not.toBeInTheDocument()
  })

  it('should display event description', () => {
    render(
      <EventDetailDialog open={true} onOpenChange={mockOnOpenChange} event={mockEvent} />
    )

    expect(screen.getByText('Descrição')).toBeInTheDocument()
    expect(screen.getByText(/Mega promoção de Black Friday/)).toBeInTheDocument()
  })

  it('should display clients count and list', () => {
    render(
      <EventDetailDialog open={true} onOpenChange={mockOnOpenChange} event={mockEvent} />
    )

    expect(screen.getByText('Clientes (3)')).toBeInTheDocument()
    expect(screen.getByText('Cliente A')).toBeInTheDocument()
    expect(screen.getByText('Cliente B')).toBeInTheDocument()
    expect(screen.getByText('Cliente C')).toBeInTheDocument()
    expect(screen.getByText('clientea@example.com')).toBeInTheDocument()
  })

  it('should show "Enviadas" badge when notifications sent', () => {
    render(
      <EventDetailDialog open={true} onOpenChange={mockOnOpenChange} event={mockEvent} />
    )

    expect(screen.getByText('Status de Notificações')).toBeInTheDocument()
    expect(screen.getByText('✓ Notificações Enviadas')).toBeInTheDocument()
  })

  it('should show "Não enviadas" badge when notifications not sent', () => {
    const eventWithoutNotifications = { ...mockEvent, notifications_sent: false }
    
    render(
      <EventDetailDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        event={eventWithoutNotifications}
      />
    )

    expect(screen.getByText('Nenhuma notificação enviada')).toBeInTheDocument()
  })

  it('should show "Inativo" badge when event is not active', () => {
    const inactiveEvent = { ...mockEvent, is_active: false }
    
    render(
      <EventDetailDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        event={inactiveEvent}
      />
    )

    expect(screen.getByText('Inativo')).toBeInTheDocument()
  })

  it('should list all clients when more than 5 clients', () => {
    const eventWithManyClients = {
      ...mockEvent,
      clients_count: 7,
      clients: [
        ...mockEvent.clients!,
        { id: 4, name: 'Cliente D', email: 'd@example.com' },
        { id: 5, name: 'Cliente E', email: 'e@example.com' },
        { id: 6, name: 'Cliente F', email: 'f@example.com' },
        { id: 7, name: 'Cliente G', email: 'g@example.com' },
      ],
    }

    render(
      <EventDetailDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        event={eventWithManyClients}
      />
    )

    expect(screen.getByText('Clientes (7)')).toBeInTheDocument()
    expect(screen.getByText('Cliente G')).toBeInTheDocument()
  })
})

