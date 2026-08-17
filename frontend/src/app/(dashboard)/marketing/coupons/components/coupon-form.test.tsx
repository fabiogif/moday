import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { CouponForm } from './coupon-form'
import { apiClient } from '@/lib/api-client'
import '@testing-library/jest-dom'

jest.mock('@/lib/api-client', () => {
  const actual = jest.requireActual('@/lib/api-client')
  return {
    ...actual,
    apiClient: {
      post: jest.fn(),
      get: jest.fn(),
    },
  }
})

jest.mock('sonner', () => ({
  toast: {
    error: jest.fn(),
    success: jest.fn(),
  },
}))

const mockApiPost = apiClient.post as jest.MockedFunction<typeof apiClient.post>

describe('CouponForm', () => {
  const mockOnSubmit = jest.fn()
  const mockOnCancel = jest.fn()

  beforeEach(() => {
    mockOnSubmit.mockClear()
    mockOnCancel.mockClear()
    mockApiPost.mockResolvedValue({ success: true, data: { valid: true, step: 0 } } as any)
  })

  it('should render only step 1 fields in create mode', () => {
    render(
      <CouponForm
        mode="create"
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    )

    expect(screen.getByLabelText(/Código/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Nome/i)).toBeInTheDocument()
    expect(screen.queryByText(/Tipo de desconto/i)).not.toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Continuar/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Cancelar/i })).toBeInTheDocument()
  })

  it('should not advance without required fields', async () => {
    const user = userEvent.setup()
    render(
      <CouponForm
        mode="create"
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    )

    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByText(/Informe um código para o cupom/i)).toBeInTheDocument()
    expect(screen.queryByText(/Tipo de desconto/i)).not.toBeInTheDocument()
    expect(mockApiPost).not.toHaveBeenCalled()
  })

  it('should advance through steps and submit after backend validation', async () => {
    const user = userEvent.setup()
    render(
      <CouponForm
        mode="create"
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    )

    await user.type(screen.getByLabelText(/Código/i), 'TESTE25')
    await user.type(screen.getByLabelText(/Nome/i), 'Cupom de Teste')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByText(/Tipo de desconto/i)).toBeInTheDocument()
    expect(mockApiPost).toHaveBeenCalledWith(
      '/api/marketing/coupons/validate',
      expect.objectContaining({ step: 0, code: 'TESTE25', name: 'Cupom de Teste' })
    )

    const valueInput = screen.getByLabelText(/Valor/i)
    await user.clear(valueInput)
    await user.type(valueInput, '25')
    mockApiPost.mockResolvedValueOnce({ success: true, data: { valid: true, step: 1 } } as any)
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByText(/Cupom ativo/i)).toBeInTheDocument()
    mockApiPost.mockResolvedValueOnce({ success: true, data: { valid: true, step: 2 } } as any)
    await user.click(screen.getByRole('button', { name: /Criar cupom/i }))

    await waitFor(() => {
      expect(mockOnSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          values: expect.objectContaining({
            code: 'TESTE25',
            name: 'Cupom de Teste',
            discount_type: 'percentage',
            discount_value: '25',
          }),
        })
      )
    })
  })

  it('should block advance when backend returns validation error', async () => {
    const user = userEvent.setup()
    mockApiPost.mockRejectedValueOnce({
      message: 'Dados inválidos',
      errors: { code: ['Já existe um cupom com este código. Escolha outro código.'] },
    })

    render(
      <CouponForm
        mode="create"
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    )

    await user.type(screen.getByLabelText(/Código/i), 'DUPLICADO')
    await user.type(screen.getByLabelText(/Nome/i), 'Cupom')
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(
      await screen.findByText(/Já existe um cupom com este código/i)
    ).toBeInTheDocument()
    expect(screen.queryByText(/Tipo de desconto/i)).not.toBeInTheDocument()

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Continuar/i })).toBeDisabled()
    })
  })
})
