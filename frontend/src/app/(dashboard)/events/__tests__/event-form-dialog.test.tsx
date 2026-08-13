import React from 'react'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import { EventFormDialog } from '../components/event-form-dialog'

const mockClients = [
  { id: 1, name: 'Cliente A', email: 'clientea@example.com' },
  { id: 2, name: 'Cliente B', email: 'clienteb@example.com' },
  { id: 3, name: 'Cliente C', email: 'clientec@example.com' },
]

const mockEvent = {
  id: 1,
  uuid: 'event-uuid-1',
  title: 'Evento Teste',
  type: 'promocao' as const,
  type_label: 'Promoção',
  start_date: '2025-11-25T09:00:00.000Z',
  start_date_formatted: '25/11/2025 09:00',
  end_date: '2025-11-25T17:00:00.000Z',
  end_date_formatted: '25/11/2025 17:00',
  duration_minutes: 480,
  location: 'Loja Física',
  description: 'Descrição do evento',
  is_active: true,
  notifications_sent: false,
  clients_count: 2,
  created_at: '2025-10-30T10:00:00.000Z',
  updated_at: '2025-10-30T10:00:00.000Z',
  clients: [mockClients[0], mockClients[1]],
}

describe('EventFormDialog', () => {
  const mockOnSubmit = jest.fn()
  const mockOnOpenChange = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should render dialog when open', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    expect(screen.getByText('Novo Evento')).toBeInTheDocument()
  })

  it('should not render when closed', () => {
    render(
      <EventFormDialog
        open={false}
        onOpenChange={mockOnOpenChange}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    expect(screen.queryByText('Novo Evento')).not.toBeInTheDocument()
  })

  it('should show "Editar Evento" title when editing', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        event={mockEvent}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    expect(screen.getByText('Editar Evento')).toBeInTheDocument()
  })

  it('should render all required form fields', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    expect(screen.getByLabelText(/título do evento/i)).toBeInTheDocument()
    expect(screen.getByText(/tipo \*/i)).toBeInTheDocument() // Select não tem labelledby direto
    expect(screen.getByLabelText(/data e hora/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/duração/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/local/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/descrição/i)).toBeInTheDocument()
  })

  it('should display client selection checkboxes', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    expect(screen.getByText('Cliente A')).toBeInTheDocument()
    expect(screen.getByText('clientea@example.com')).toBeInTheDocument()
    expect(screen.getByText('Cliente B')).toBeInTheDocument()
    expect(screen.getByText('clienteb@example.com')).toBeInTheDocument()
    expect(screen.getByText('Cliente C')).toBeInTheDocument()
    expect(screen.getByText('clientec@example.com')).toBeInTheDocument()
  })

  it('should display notification channel options', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    expect(screen.getByLabelText(/e-mail/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/whatsapp/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/sms/i)).toBeInTheDocument()
  })

  it('should indicate client selection is required when none selected', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    expect(screen.getByText(/Selecione pelo menos 1/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /criar evento/i })).toBeDisabled()
  })

  it('should disable submit button when no clients selected', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    const submitButton = screen.getByRole('button', { name: /criar evento/i })
    expect(submitButton).toBeDisabled()
  })

  it('should show message when no clients available', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        clients={[]}
        onSubmit={mockOnSubmit}
      />
    )

    expect(screen.getByText('Nenhum cliente cadastrado')).toBeInTheDocument()
  })

  it('should populate form fields when editing event', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        event={mockEvent}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    const titleInput = screen.getByLabelText(/título do evento/i) as HTMLInputElement
    expect(titleInput.value).toBe('Evento Teste')

    const descriptionInput = screen.getByLabelText(/descrição/i) as HTMLTextAreaElement
    expect(descriptionInput.value).toBe('Descrição do evento')
  })

  it('should have cancel button that closes dialog', () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    const cancelButton = screen.getByRole('button', { name: /cancelar/i })
    fireEvent.click(cancelButton)

    expect(mockOnOpenChange).toHaveBeenCalledWith(false)
  })

  it('should show notification info when channels selected', async () => {
    render(
      <EventFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        clients={mockClients}
        onSubmit={mockOnSubmit}
      />
    )

    const emailCheckbox = screen.getByLabelText(/e-mail/i)
    fireEvent.click(emailCheckbox)

    await waitFor(() => {
      expect(screen.getByText(/as notificações serão enviadas imediatamente/i)).toBeInTheDocument()
    })
  })
})

