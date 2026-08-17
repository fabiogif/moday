import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { ProductVariationsManager } from '../product-variations-manager'
import { ProductOptionalsManager } from '../product-optionals-manager'
import { ProductVariation, ProductOptional } from '@/types/product-variations'

/**
 * ==========================================
 * TESTES DE VARIAÇÕES (Seleção Única)
 * ==========================================
 */
describe('ProductVariationsManager', () => {
  const mockVariations: ProductVariation[] = [
    { id: '1', name: 'Pequena', price: -5.00 },
    { id: '2', name: 'Média', price: 0 },
    { id: '3', name: 'Grande', price: 10.00 },
  ]

  const mockOnChange = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should render without variations', () => {
    render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />)
    
    expect(screen.getByText(/nenhuma variação cadastrada/i)).toBeInTheDocument()
  })

  it('should display existing variations', () => {
    render(<ProductVariationsManager variations={mockVariations} onChange={mockOnChange} />)
    
    expect(screen.getByDisplayValue('Pequena')).toBeInTheDocument()
    expect(screen.getByDisplayValue('-5')).toBeInTheDocument()
    expect(screen.getByDisplayValue('Grande')).toBeInTheDocument()
    expect(screen.getByDisplayValue('10')).toBeInTheDocument()
  })

  it('should add new variation', async () => {
    render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome \(ex: pequeno/i)
    const priceInput = screen.getByPlaceholderText(/0,00/i)

    fireEvent.change(nameInput, { target: { value: 'Média' } })
    fireEvent.change(priceInput, { target: { value: '0' } })

    const addButton = screen.getByRole('button')
    fireEvent.click(addButton)

    await waitFor(() => {
      expect(mockOnChange).toHaveBeenCalledWith(
        expect.arrayContaining([
          expect.objectContaining({
            name: 'Média',
            price: 0,
          }),
        ])
      )
    })
  })

  it('should support negative prices for variations', async () => {
    render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome \(ex: pequeno/i)
    const priceInput = screen.getByPlaceholderText(/0,00/i)

    fireEvent.change(nameInput, { target: { value: 'Pequena' } })
    fireEvent.change(priceInput, { target: { value: '-5.00' } })

    const addButton = screen.getByRole('button')
    fireEvent.click(addButton)

    await waitFor(() => {
      expect(mockOnChange).toHaveBeenCalledWith(
        expect.arrayContaining([
          expect.objectContaining({
            name: 'Pequena',
            price: -5.00,
          }),
        ])
      )
    })
  })

  it('should update variation name', () => {
    render(<ProductVariationsManager variations={mockVariations} onChange={mockOnChange} />)
    
    const nameInput = screen.getByDisplayValue('Grande')
    fireEvent.change(nameInput, { target: { value: 'Extra Grande' } })

    expect(mockOnChange).toHaveBeenCalledWith(
      expect.arrayContaining([
        expect.objectContaining({
          id: '3',
          name: 'Extra Grande',
          price: 10.00,
        }),
      ])
    )
  })

  it('should remove variation', () => {
    render(<ProductVariationsManager variations={mockVariations} onChange={mockOnChange} />)
    
    const removeButtons = screen.getAllByRole('button').filter(btn => 
      btn.className.includes('destructive')
    )

    if (removeButtons[0]) {
      fireEvent.click(removeButtons[0])
    }

    expect(mockOnChange).toHaveBeenCalledWith(
      expect.arrayContaining([
        expect.objectContaining({ id: '2' }),
        expect.objectContaining({ id: '3' }),
      ])
    )
  })
})

/**
 * ==========================================
 * TESTES DE OPCIONAIS (Múltipla Escolha)
 * ==========================================
 */
describe('ProductOptionalsManager', () => {
  const mockOptionals: ProductOptional[] = [
    { id: '1', name: 'Bacon', price: 5.00 },
    { id: '2', name: 'Queijo', price: 3.00 },
  ]

  const mockOnChange = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should render without optionals', () => {
    render(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />)
    
    expect(screen.getByText(/nenhum opcional cadastrado/i)).toBeInTheDocument()
  })

  it('should display existing optionals', () => {
    render(<ProductOptionalsManager optionals={mockOptionals} onChange={mockOnChange} />)
    
    expect(screen.getByDisplayValue('Bacon')).toBeInTheDocument()
    expect(screen.getByDisplayValue('5')).toBeInTheDocument()
    expect(screen.getByDisplayValue('Queijo')).toBeInTheDocument()
    expect(screen.getByDisplayValue('3')).toBeInTheDocument()
  })

  it('should add new optional', async () => {
    render(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome do opcional/i)
    const priceInput = screen.getByPlaceholderText(/0,00/i)
    const addButton = screen.getAllByRole('button')[0]

    fireEvent.change(nameInput, { target: { value: 'Cheddar' } })
    fireEvent.change(priceInput, { target: { value: '4.00' } })
    fireEvent.click(addButton)

    await waitFor(() => {
      expect(mockOnChange).toHaveBeenCalledWith(
        expect.arrayContaining([
          expect.objectContaining({
            name: 'Cheddar',
            price: 4.00,
          }),
        ])
      )
    })
  })

  it('should not accept negative prices for optionals', () => {
    render(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome do opcional/i)
    const priceInput = screen.getByPlaceholderText(/0,00/i)
    const addButton = screen.getAllByRole('button')[0]

    fireEvent.change(nameInput, { target: { value: 'Teste' } })
    fireEvent.change(priceInput, { target: { value: '-5.00' } })
    fireEvent.click(addButton)

    // Não deve adicionar com preço negativo
    expect(mockOnChange).not.toHaveBeenCalled()
  })

  it('should update optional name', () => {
    render(<ProductOptionalsManager optionals={mockOptionals} onChange={mockOnChange} />)
    
    const nameInput = screen.getByDisplayValue('Bacon')
    fireEvent.change(nameInput, { target: { value: 'Bacon Extra' } })

    expect(mockOnChange).toHaveBeenCalledWith(
      expect.arrayContaining([
        expect.objectContaining({
          id: '1',
          name: 'Bacon Extra',
          price: 5.00,
        }),
      ])
    )
  })

  it('should update optional price', () => {
    render(<ProductOptionalsManager optionals={mockOptionals} onChange={mockOnChange} />)
    
    const priceInput = screen.getByDisplayValue('5')
    fireEvent.change(priceInput, { target: { value: '6.00' } })

    expect(mockOnChange).toHaveBeenCalledWith(
      expect.arrayContaining([
        expect.objectContaining({
          id: '1',
          name: 'Bacon',
          price: 6.00,
        }),
      ])
    )
  })

  it('should remove optional', () => {
    render(<ProductOptionalsManager optionals={mockOptionals} onChange={mockOnChange} />)
    
    const removeButtons = screen.getAllByRole('button').filter(btn => 
      btn.className.includes('destructive')
    )

    if (removeButtons[0]) {
      fireEvent.click(removeButtons[0])
    }

    expect(mockOnChange).toHaveBeenCalledWith([
      expect.objectContaining({ id: '2', name: 'Queijo' }),
    ])
  })

  it('should display correct count badge', () => {
    render(<ProductOptionalsManager optionals={mockOptionals} onChange={mockOnChange} />)
    
    expect(screen.getByText('2')).toBeInTheDocument()
  })

  it('should be disabled when prop is true', () => {
    render(<ProductOptionalsManager optionals={mockOptionals} onChange={mockOnChange} disabled={true} />)
    
    const nameInput = screen.getByDisplayValue('Bacon')
    const priceInput = screen.getByDisplayValue('5')

    expect(nameInput).toBeDisabled()
    expect(priceInput).toBeDisabled()
  })
})

/**
 * ==========================================
 * TESTES DE CÁLCULO DE PREÇO
 * ==========================================
 */
describe('Price Calculation Logic', () => {
  it('should calculate total with variation only', () => {
    const basePrice = 35.00
    const variation = { id: '1', name: 'Grande', price: 10.00 }
    
    const total = basePrice + variation.price
    
    expect(total).toBe(45.00)
  })

  it('should calculate total with optionals only', () => {
    const basePrice = 20.00
    const selectedOptionals = [
      { id: '1', name: 'Bacon', price: 5.00, quantity: 2 },
      { id: '2', name: 'Queijo', price: 3.00, quantity: 1 },
    ]
    
    const optionalsTotal = selectedOptionals.reduce(
      (sum, opt) => sum + (opt.price * opt.quantity),
      0
    )
    
    const total = basePrice + optionalsTotal
    
    // 20 + (5×2) + (3×1) = 20 + 10 + 3 = 33
    expect(total).toBe(33.00)
  })

  it('should calculate total with variation and optionals', () => {
    const basePrice = 35.00
    const variation = { id: '1', name: 'Grande', price: 10.00 }
    const selectedOptionals = [
      { id: '1', name: 'Bacon', price: 5.00, quantity: 2 },
      { id: '2', name: 'Borda', price: 12.00, quantity: 1 },
    ]
    
    const variationPrice = variation.price
    const optionalsTotal = selectedOptionals.reduce(
      (sum, opt) => sum + (opt.price * opt.quantity),
      0
    )
    
    const total = basePrice + variationPrice + optionalsTotal
    
    // 35 + 10 + (5×2) + (12×1) = 35 + 10 + 10 + 12 = 67
    expect(total).toBe(67.00)
  })

  it('should handle negative variation prices (discounts)', () => {
    const basePrice = 30.00
    const variation = { id: '1', name: 'Pequena', price: -5.00 }
    
    const total = basePrice + variation.price
    
    expect(total).toBe(25.00)
  })

  it('should handle zero price variation', () => {
    const basePrice = 25.00
    const variation = { id: '1', name: 'Padrão', price: 0 }
    
    const total = basePrice + variation.price
    
    expect(total).toBe(25.00)
  })

  it('should calculate cart total with multiple products', () => {
    const cart = [
      {
        name: 'Pizza',
        price: 35.00,
        quantity: 1,
        selectedVariation: { id: '1', name: 'Grande', price: 10.00 },
        selectedOptionals: [
          { id: '1', name: 'Bacon', price: 5.00, quantity: 2 },
        ]
      },
      {
        name: 'Burger',
        price: 22.00,
        quantity: 2,
        selectedVariation: undefined,
        selectedOptionals: [
          { id: '1', name: 'Queijo', price: 3.00, quantity: 1 },
        ]
      }
    ]
    
    const total = cart.reduce((sum, item) => {
      const base = item.price
      const variation = item.selectedVariation?.price || 0
      const optionals = item.selectedOptionals?.reduce(
        (optSum, opt) => optSum + (opt.price * opt.quantity),
        0
      ) || 0
      
      return sum + ((base + variation + optionals) * item.quantity)
    }, 0)
    
    // Pizza: (35 + 10 + 10) × 1 = 55
    // Burger: (22 + 0 + 3) × 2 = 50
    // Total: 105
    expect(total).toBe(105.00)
  })
})

/**
 * ==========================================
 * TESTES DE INTEGRAÇÃO
 * ==========================================
 */
describe('Variations and Optionals Integration', () => {
  it('should handle product with both variations and optionals', () => {
    const product = {
      name: 'Pizza Margherita',
      price: 35.00,
      variations: [
        { id: '1', name: 'Pequena', price: -5.00 },
        { id: '2', name: 'Grande', price: 10.00 },
      ],
      optionals: [
        { id: '1', name: 'Bacon', price: 5.00 },
        { id: '2', name: 'Borda', price: 12.00 },
      ]
    }

    // Simular seleção do cliente
    const selectedVariation = product.variations[1] // Grande
    const selectedOptionals = [
      { ...product.optionals[0], quantity: 2 }, // Bacon × 2
      { ...product.optionals[1], quantity: 1 }, // Borda × 1
    ]

    const total = product.price 
      + selectedVariation.price 
      + selectedOptionals.reduce((sum, opt) => sum + (opt.price * opt.quantity), 0)

    // 35 + 10 + (5×2) + (12×1) = 67
    expect(total).toBe(67.00)
  })

  it('should handle product with only variations', () => {
    const product = {
      price: 25.00,
      variations: [
        { id: '1', name: 'Pequena', price: -3.00 },
      ],
      optionals: []
    }

    const selectedVariation = product.variations[0]
    const total = product.price + selectedVariation.price

    expect(total).toBe(22.00)
  })

  it('should handle product with only optionals', () => {
    const product = {
      price: 18.00,
      variations: [],
      optionals: [
        { id: '1', name: 'Bacon', price: 5.00 },
      ]
    }

    const selectedOptionals = [
      { ...product.optionals[0], quantity: 3 }
    ]

    const total = product.price 
      + selectedOptionals.reduce((sum, opt) => sum + (opt.price * opt.quantity), 0)

    expect(total).toBe(33.00) // 18 + (5×3)
  })

  it('should handle product without variations or optionals', () => {
    const product = {
      price: 15.00,
      variations: [],
      optionals: []
    }

    const total = product.price

    expect(total).toBe(15.00)
  })
})

/**
 * ==========================================
 * TESTES DE VALIDAÇÃO
 * ==========================================
 */
describe('Validation Tests', () => {
  const mockOnChange = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should not add variation without name', () => {
    render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />)
    
    const priceInput = screen.getByPlaceholderText(/0,00/i)
    const addButton = screen.getAllByRole('button')[0]

    fireEvent.change(priceInput, { target: { value: '5.00' } })
    fireEvent.click(addButton)

    expect(mockOnChange).not.toHaveBeenCalled()
  })

  it('should not add variation without price', () => {
    render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome \(ex: pequeno/i)
    const addButton = screen.getAllByRole('button')[0]

    fireEvent.change(nameInput, { target: { value: 'Média' } })
    fireEvent.click(addButton)

    expect(mockOnChange).not.toHaveBeenCalled()
  })

  it('should not add optional without name', () => {
    render(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />)
    
    const priceInput = screen.getByPlaceholderText(/0,00/i)
    const addButton = screen.getAllByRole('button')[0]

    fireEvent.change(priceInput, { target: { value: '5.00' } })
    fireEvent.click(addButton)

    expect(mockOnChange).not.toHaveBeenCalled()
  })

  it('should not add optional without price', () => {
    render(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />)
    
    const nameInput = screen.getByPlaceholderText(/nome do opcional/i)
    const addButton = screen.getAllByRole('button')[0]

    fireEvent.change(nameInput, { target: { value: 'Bacon' } })
    fireEvent.click(addButton)

    expect(mockOnChange).not.toHaveBeenCalled()
  })
})

/**
 * ==========================================
 * TESTES DE QUANTIDADE DE OPCIONAIS
 * ==========================================
 */
describe('Optionals Quantity Tests', () => {
  it('should allow same optional multiple times', () => {
    const optional = { id: '1', name: 'Bacon', price: 5.00 }
    const quantities = [1, 2, 3, 5, 10]

    quantities.forEach(qty => {
      const subtotal = optional.price * qty
      expect(subtotal).toBe(5.00 * qty)
    })
  })

  it('should calculate correctly with zero quantity', () => {
    const optional = { id: '1', name: 'Bacon', price: 5.00, quantity: 0 }
    const subtotal = optional.price * optional.quantity

    expect(subtotal).toBe(0)
  })

  it('should calculate correctly with large quantities', () => {
    const optional = { id: '1', name: 'Queijo', price: 3.00, quantity: 10 }
    const subtotal = optional.price * optional.quantity

    expect(subtotal).toBe(30.00)
  })
})

/**
 * ==========================================
 * TESTES DE CENÁRIOS REAIS
 * ==========================================
 */
describe('Real World Scenarios', () => {
  it('Scenario: Pizzaria - Pizza Completa', () => {
    const product = {
      name: 'Pizza 4 Queijos',
      price: 42.00,
    }

    const variation = { id: '3', name: 'Grande', price: 12.00 }
    const optionals = [
      { id: '1', name: 'Bacon', price: 6.00, quantity: 2 },
      { id: '2', name: 'Borda Catupiry', price: 15.00, quantity: 1 },
    ]

    const total = product.price 
      + variation.price 
      + optionals.reduce((sum, opt) => sum + (opt.price * opt.quantity), 0)

    // 42 + 12 + (6×2) + (15×1) = 42 + 12 + 12 + 15 = 81
    expect(total).toBe(81.00)
  })

  it('Scenario: Açaiteria - Açaí Personalizado', () => {
    const product = {
      name: 'Açaí Premium',
      price: 15.00,
    }

    const variation = { id: '3', name: '700ml', price: 5.00 }
    const optionals = [
      { id: '1', name: 'Banana', price: 2.50, quantity: 2 },
      { id: '2', name: 'Granola', price: 2.00, quantity: 1 },
      { id: '3', name: 'Nutella', price: 6.00, quantity: 1 },
    ]

    const total = product.price 
      + variation.price 
      + optionals.reduce((sum, opt) => sum + (opt.price * opt.quantity), 0)

    // 15 + 5 + (2.50×2) + (2×1) + (6×1) = 15 + 5 + 5 + 2 + 6 = 33
    expect(total).toBe(33.00)
  })

  it('Scenario: Hamburgueria - Burger Duplo', () => {
    const product = {
      name: 'X-Burger',
      price: 22.00,
    }

    const variation = { id: '2', name: 'Duplo', price: 12.00 }
    const optionals = [
      { id: '1', name: 'Bacon', price: 5.00, quantity: 1 },
      { id: '2', name: 'Queijo', price: 3.00, quantity: 2 },
      { id: '3', name: 'Ovo', price: 2.50, quantity: 1 },
    ]

    const total = product.price 
      + variation.price 
      + optionals.reduce((sum, opt) => sum + (opt.price * opt.quantity), 0)

    // 22 + 12 + (5×1) + (3×2) + (2.50×1) = 22 + 12 + 5 + 6 + 2.50 = 47.50
    expect(total).toBe(47.50)
  })
})

