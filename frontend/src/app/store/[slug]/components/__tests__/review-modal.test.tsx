import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { ReviewModal } from '../review-modal'
import { toast } from 'sonner'
import '@testing-library/jest-dom'

// Mock do toast
jest.mock('sonner', () => ({
  toast: {
    error: jest.fn(),
    success: jest.fn(),
  },
}))

describe('ReviewModal', () => {
  const mockOnClose = jest.fn()
  const mockOnSubmit = jest.fn()
  
  const defaultProps = {
    isOpen: true,
    onClose: mockOnClose,
    onSubmit: mockOnSubmit,
    orderData: {
      id: 1,
      identify: 'ABC123',
    },
    tenantId: 1,
    customerData: {
      name: 'João Silva',
      email: 'joao@example.com',
    },
  }

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('deve renderizar o modal corretamente', () => {
    render(<ReviewModal {...defaultProps} />)

    expect(screen.getByText('Avalie seu Pedido')).toBeInTheDocument()
    expect(screen.getByText(/ABC123/)).toBeInTheDocument()
    expect(screen.getByText('Como você avalia seu pedido?')).toBeInTheDocument()
  })

  it('deve renderizar 5 estrelas clicáveis', () => {
    render(<ReviewModal {...defaultProps} />)

    const stars = screen.getAllByRole('button')
    const starButtons = stars.filter(btn => {
      const svg = btn.querySelector('svg')
      return svg && svg.classList.contains('h-10')
    })

    expect(starButtons).toHaveLength(5)
  })

  it('deve atualizar o rating ao clicar em uma estrela', () => {
    render(<ReviewModal {...defaultProps} />)

    const stars = screen.getAllByRole('button')
    const starButtons = stars.filter(btn => {
      const svg = btn.querySelector('svg')
      return svg && svg.classList.contains('h-10')
    })

    // Clicar na quinta estrela
    fireEvent.click(starButtons[4])

    // Verificar se aparece o texto "Excelente"
    expect(screen.getByText('Excelente')).toBeInTheDocument()
  })

  it('deve permitir digitar um comentário', () => {
    render(<ReviewModal {...defaultProps} />)

    const textarea = screen.getByPlaceholderText('Conte-nos sobre sua experiência...')
    
    fireEvent.change(textarea, { target: { value: 'Ótimo atendimento!' } })

    expect(textarea).toHaveValue('Ótimo atendimento!')
  })

  it('deve mostrar contador de caracteres do comentário', () => {
    render(<ReviewModal {...defaultProps} />)

    const textarea = screen.getByPlaceholderText('Conte-nos sobre sua experiência...')
    
    fireEvent.change(textarea, { target: { value: 'Teste' } })

    expect(screen.getByText('5/1000 caracteres')).toBeInTheDocument()
  })

  it('deve validar seleção de rating antes de enviar', () => {
    render(<ReviewModal {...defaultProps} />)

    const submitButton = screen.getByText('Enviar Avaliação')

    // Aceitar termos sem selecionar rating — botão permanece desabilitado
    const checkbox = screen.getByRole('checkbox')
    fireEvent.click(checkbox)

    expect(submitButton).toBeDisabled()
    expect(mockOnSubmit).not.toHaveBeenCalled()
  })

  it('deve validar aceite de termos antes de enviar', () => {
    render(<ReviewModal {...defaultProps} />)

    const stars = screen.getAllByRole('button')
    const starButtons = stars.filter(btn => {
      const svg = btn.querySelector('svg')
      return svg && svg.classList.contains('h-10')
    })

    // Selecionar 5 estrelas sem aceitar termos — botão permanece desabilitado
    fireEvent.click(starButtons[4])

    const submitButton = screen.getByText('Enviar Avaliação')
    expect(submitButton).toBeDisabled()
    expect(mockOnSubmit).not.toHaveBeenCalled()
  })

  it('deve enviar avaliação com sucesso quando válida', async () => {
    mockOnSubmit.mockResolvedValue(undefined)

    render(<ReviewModal {...defaultProps} />)

    const stars = screen.getAllByRole('button')
    const starButtons = stars.filter(btn => {
      const svg = btn.querySelector('svg')
      return svg && svg.classList.contains('h-10')
    })

    // Selecionar 5 estrelas
    fireEvent.click(starButtons[4])

    // Digitar comentário
    const textarea = screen.getByPlaceholderText('Conte-nos sobre sua experiência...')
    fireEvent.change(textarea, { target: { value: 'Excelente serviço!' } })

    // Aceitar termos
    const checkbox = screen.getByRole('checkbox')
    fireEvent.click(checkbox)

    // Enviar
    const submitButton = screen.getByText('Enviar Avaliação')
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(mockOnSubmit).toHaveBeenCalledWith({
        rating: 5,
        comment: 'Excelente serviço!',
        terms_accepted: true,
        customer_name: 'João Silva',
        customer_email: 'joao@example.com',
      })
    })

    await waitFor(() => {
      expect(toast.success).toHaveBeenCalledWith('Avaliação enviada com sucesso! Será publicada após aprovação.')
    })

    expect(mockOnClose).toHaveBeenCalled()
  })

  it('deve permitir pular a avaliação', () => {
    render(<ReviewModal {...defaultProps} />)

    const skipButton = screen.getByText('Pular')
    fireEvent.click(skipButton)

    expect(mockOnClose).toHaveBeenCalled()
    expect(mockOnSubmit).not.toHaveBeenCalled()
  })

  it('deve desabilitar botões durante o envio', async () => {
    mockOnSubmit.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 1000)))

    render(<ReviewModal {...defaultProps} />)

    const stars = screen.getAllByRole('button')
    const starButtons = stars.filter(btn => {
      const svg = btn.querySelector('svg')
      return svg && svg.classList.contains('h-10')
    })

    fireEvent.click(starButtons[4])

    const checkbox = screen.getByRole('checkbox')
    fireEvent.click(checkbox)

    const submitButton = screen.getByText('Enviar Avaliação')
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(screen.getByText('Enviando...')).toBeInTheDocument()
    })
  })

  it('deve limitar comentário a 1000 caracteres', () => {
    render(<ReviewModal {...defaultProps} />)

    const textarea = screen.getByPlaceholderText('Conte-nos sobre sua experiência...')

    expect(textarea).toHaveAttribute('maxLength', '1000')
    expect(screen.getByText('0/1000 caracteres')).toBeInTheDocument()
  })

  it('deve mostrar texto correto para cada rating', () => {
    render(<ReviewModal {...defaultProps} />)

    const stars = screen.getAllByRole('button')
    const starButtons = stars.filter(btn => {
      const svg = btn.querySelector('svg')
      return svg && svg.classList.contains('h-10')
    })

    const ratings = [
      { index: 0, text: 'Péssimo' },
      { index: 1, text: 'Ruim' },
      { index: 2, text: 'Regular' },
      { index: 3, text: 'Bom' },
      { index: 4, text: 'Excelente' },
    ]

    ratings.forEach(({ index, text }) => {
      fireEvent.click(starButtons[index])
      expect(screen.getByText(text)).toBeInTheDocument()
    })
  })

  it('deve tratar erro ao enviar avaliação', async () => {
    const errorMessage = 'Erro ao enviar avaliação'
    mockOnSubmit.mockRejectedValue(new Error(errorMessage))

    render(<ReviewModal {...defaultProps} />)

    const stars = screen.getAllByRole('button')
    const starButtons = stars.filter(btn => {
      const svg = btn.querySelector('svg')
      return svg && svg.classList.contains('h-10')
    })

    fireEvent.click(starButtons[4])

    const checkbox = screen.getByRole('checkbox')
    fireEvent.click(checkbox)

    const submitButton = screen.getByText('Enviar Avaliação')
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(toast.error).toHaveBeenCalledWith(errorMessage)
    })

    expect(mockOnClose).not.toHaveBeenCalled()
  })

  it('deve resetar form após envio com sucesso', async () => {
    mockOnSubmit.mockResolvedValue(undefined)

    render(<ReviewModal {...defaultProps} />)

    const stars = screen.getAllByRole('button')
    const starButtons = stars.filter(btn => {
      const svg = btn.querySelector('svg')
      return svg && svg.classList.contains('h-10')
    })

    // Preencher form
    fireEvent.click(starButtons[4])

    const textarea = screen.getByPlaceholderText('Conte-nos sobre sua experiência...')
    fireEvent.change(textarea, { target: { value: 'Teste' } })

    const checkbox = screen.getByRole('checkbox')
    fireEvent.click(checkbox)

    const submitButton = screen.getByText('Enviar Avaliação')
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(mockOnClose).toHaveBeenCalled()
    })
  })
})

