import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { ProductOptionals, ProductOptional } from '../product-optionals'
import { toast } from 'sonner'

// Mock do toast
jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
  },
}))

describe('ProductOptionals Component', () => {
  const mockOptionals: ProductOptional[] = [
    { id: '1', name: 'Grande', price: 10.00 },
    { id: '2', name: 'Borda Recheada', price: 8.00 },
  ]

  const mockOnChange = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should render without optionals', () => {
    render(<ProductOptionals optionals={[]} onChange={mockOnChange} />)
    
    expect(screen.getByText(/nenhum opcional cadastrado/i)).toBeInTheDocument()
  })

  it('should display existing optionals', () => {
    render(<ProductOptionals optionals={mockOptionals} onChange={mockOnChange} />)
    
    expect(screen.getByDisplayValue('Grande')).toBeInTheDocument()
    expect(screen.getByDisplayValue('10')).toBeInTheDocument()
    expect(screen.getByDisplayValue('Borda Recheada')).toBeInTheDocument()
    expect(screen.getByDisplayValue('8')).toBeInTheDocument()
  })

  it('should add new optional', async () => {
    render(<ProductOptionals optionals={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome do opcional/i)
    const priceInput = screen.getByPlaceholderText(/0,00/i)
    const addButton = screen.getByRole('button')

    fireEvent.change(nameInput, { target: { value: 'Médio' } })
    fireEvent.change(priceInput, { target: { value: '5.00' } })
    fireEvent.click(addButton)

    await waitFor(() => {
      expect(mockOnChange).toHaveBeenCalledWith([
        expect.objectContaining({
          name: 'Médio',
          price: 5.00,
        }),
      ])
    })
  })

  it('should add optional on Enter key', async () => {
    render(<ProductOptionals optionals={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome do opcional/i)
    const priceInput = screen.getByPlaceholderText(/0,00/i)

    fireEvent.change(nameInput, { target: { value: 'Pequeno' } })
    fireEvent.change(priceInput, { target: { value: '3.00' } })
    fireEvent.keyPress(priceInput, { key: 'Enter', code: 13, charCode: 13 })

    await waitFor(() => {
      expect(mockOnChange).toHaveBeenCalled()
    })
  })

  it('should not add optional with empty name', () => {
    render(<ProductOptionals optionals={[]} onChange={mockOnChange} />)
    
    const priceInput = screen.getByPlaceholderText(/0,00/i)
    const addButton = screen.getByRole('button')

    fireEvent.change(priceInput, { target: { value: '5.00' } })
    fireEvent.click(addButton)

    expect(mockOnChange).not.toHaveBeenCalled()
  })

  it('should not add optional with empty price', () => {
    render(<ProductOptionals optionals={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome do opcional/i)
    const addButton = screen.getByRole('button')

    fireEvent.change(nameInput, { target: { value: 'Médio' } })
    fireEvent.click(addButton)

    expect(mockOnChange).not.toHaveBeenCalled()
  })

  it('should update optional name', () => {
    render(<ProductOptionals optionals={mockOptionals} onChange={mockOnChange} />)
    
    const nameInput = screen.getByDisplayValue('Grande')
    fireEvent.change(nameInput, { target: { value: 'Extra Grande' } })

    expect(mockOnChange).toHaveBeenCalledWith([
      { id: '1', name: 'Extra Grande', price: 10.00 },
      { id: '2', name: 'Borda Recheada', price: 8.00 },
    ])
  })

  it('should update optional price', () => {
    render(<ProductOptionals optionals={mockOptionals} onChange={mockOnChange} />)
    
    const priceInput = screen.getByDisplayValue('10')
    fireEvent.change(priceInput, { target: { value: '12.00' } })

    expect(mockOnChange).toHaveBeenCalledWith([
      { id: '1', name: 'Grande', price: 12.00 },
      { id: '2', name: 'Borda Recheada', price: 8.00 },
    ])
  })

  it('should remove optional', () => {
    render(<ProductOptionals optionals={mockOptionals} onChange={mockOnChange} />)
    
    const removeButtons = screen.getAllByRole('button')
    const firstRemoveButton = removeButtons.find(btn => 
      btn.querySelector('svg') && btn.className.includes('destructive')
    )

    if (firstRemoveButton) {
      fireEvent.click(firstRemoveButton)
    }

    expect(mockOnChange).toHaveBeenCalledWith([
      { id: '2', name: 'Borda Recheada', price: 8.00 },
    ])
  })

  it('should display correct count badge', () => {
    render(<ProductOptionals optionals={mockOptionals} onChange={mockOnChange} />)
    
    expect(screen.getByText('2')).toBeInTheDocument()
  })

  it('should format currency correctly', () => {
    render(<ProductOptionals optionals={mockOptionals} onChange={mockOnChange} />)
    
    expect(screen.getByText(/R\$ 10,00/i)).toBeInTheDocument()
    expect(screen.getByText(/R\$ 8,00/i)).toBeInTheDocument()
  })

  it('should be disabled when prop is true', () => {
    render(<ProductOptionals optionals={mockOptionals} onChange={mockOnChange} disabled={true} />)
    
    const nameInput = screen.getByDisplayValue('Grande')
    const priceInput = screen.getByDisplayValue('10')

    expect(nameInput).toBeDisabled()
    expect(priceInput).toBeDisabled()
  })

  it('should not render add section when disabled', () => {
    render(<ProductOptionals optionals={[]} onChange={mockOnChange} disabled={true} />)
    
    expect(screen.queryByPlaceholderText(/nome do opcional/i)).not.toBeInTheDocument()
  })

  it('should handle invalid price input', () => {
    render(<ProductOptionals optionals={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome do opcional/i)
    const priceInput = screen.getByPlaceholderText(/0,00/i)
    const addButton = screen.getByRole('button')

    fireEvent.change(nameInput, { target: { value: 'Teste' } })
    fireEvent.change(priceInput, { target: { value: 'invalid' } })
    fireEvent.click(addButton)

    expect(mockOnChange).not.toHaveBeenCalled()
  })

  it('should handle negative price input', () => {
    render(<ProductOptionals optionals={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome do opcional/i)
    const priceInput = screen.getByPlaceholderText(/0,00/i)
    const addButton = screen.getByRole('button')

    fireEvent.change(nameInput, { target: { value: 'Desconto' } })
    fireEvent.change(priceInput, { target: { value: '-5.00' } })
    fireEvent.click(addButton)

    // Não deve adicionar com preço negativo
    expect(mockOnChange).not.toHaveBeenCalled()
  })
})

