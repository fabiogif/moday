import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProductVariationsManager } from '../product-variations-manager';
import { ProductOptionalsManager } from '../product-optionals-manager';
import { ProductVariation, ProductOptional } from '@/types/product-variations';

// Mock dos componentes UI do shadcn
jest.mock('@/components/ui/input', () => ({
  Input: jest.fn(({ value, onChange, ...props }) => (
    <input 
      data-testid={props['data-testid'] || 'input'} 
      value={value || ''} 
      onChange={onChange} 
      {...props} 
    />
  )),
}));

jest.mock('@/components/ui/button', () => ({
  Button: jest.fn(({ children, onClick, disabled, ...props }) => (
    <button 
      data-testid={props['data-testid'] || 'button'} 
      onClick={onClick}
      disabled={disabled}
      {...props}
    >
      {children}
    </button>
  )),
}));

jest.mock('@/components/ui/card', () => ({
  Card: jest.fn(({ children, ...props }) => <div data-testid="card" {...props}>{children}</div>),
  CardContent: jest.fn(({ children }) => <div data-testid="card-content">{children}</div>),
  CardHeader: jest.fn(({ children }) => <div data-testid="card-header">{children}</div>),
  CardTitle: jest.fn(({ children }) => <h2 data-testid="card-title">{children}</h2>),
  CardDescription: jest.fn(({ children }) => <p data-testid="card-description">{children}</p>),
}));

jest.mock('@/components/ui/label', () => ({
  Label: jest.fn(({ children }) => <label data-testid="label">{children}</label>),
}));

jest.mock('@/components/ui/badge', () => ({
  Badge: jest.fn(({ children, ...props }) => <span data-testid="badge" {...props}>{children}</span>),
}));

describe('ProductVariationsManager - CRUD Completo', () => {
  const mockOnChange = jest.fn();

  beforeEach(() => {
    mockOnChange.mockClear();
  });

  // ============================================
  // TESTES DE CRIAÇÃO (CREATE)
  // ============================================

  describe('Criar Variações', () => {
    it('pode adicionar uma nova variação', async () => {
      const user = userEvent.setup();
      render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />);

      const nameInput = screen.getAllByRole('textbox')[0];
      const priceInput = screen.getAllByRole('spinbutton')[0];
      const addButton = screen.getByRole('button');

      await user.type(nameInput, 'Pequeno');
      await user.type(priceInput, '-5.00');
      await user.click(addButton);

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith(
          expect.arrayContaining([
            expect.objectContaining({ name: 'Pequeno', price: -5.00 })
          ])
        );
      });
    });

    it('pode adicionar múltiplas variações sequencialmente', async () => {
      const user = userEvent.setup();
      const { rerender } = render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />);

      // Adicionar primeira variação
      const nameInput = screen.getAllByRole('textbox')[0];
      const priceInput = screen.getAllByRole('spinbutton')[0];
      const addButton = screen.getByRole('button');

      await user.type(nameInput, 'Pequeno');
      await user.type(priceInput, '-5.00');
      await user.click(addButton);

      // Simular state update
      const firstVariation: ProductVariation = { id: '1', name: 'Pequeno', price: -5.00 };
      rerender(<ProductVariationsManager variations={[firstVariation]} onChange={mockOnChange} />);

      // Adicionar segunda variação
      await user.type(nameInput, 'Grande');
      await user.type(priceInput, '10.00');
      await user.click(addButton);

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenLastCalledWith(
          expect.arrayContaining([
            expect.objectContaining({ name: 'Grande', price: 10.00 })
          ])
        );
      });
    });

    it('não adiciona variação com nome vazio', async () => {
      const user = userEvent.setup();
      render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />);

      const priceInput = screen.getAllByRole('spinbutton')[0];
      const addButton = screen.getByRole('button');

      await user.type(priceInput, '5.00');
      await user.click(addButton);

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('não adiciona variação com preço vazio', async () => {
      const user = userEvent.setup();
      render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />);

      const nameInput = screen.getAllByRole('textbox')[0];
      const addButton = screen.getByRole('button');

      await user.type(nameInput, 'Médio');
      await user.click(addButton);

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('aceita variações com preço negativo (desconto)', async () => {
      const user = userEvent.setup();
      render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />);

      const nameInput = screen.getAllByRole('textbox')[0];
      const priceInput = screen.getAllByRole('spinbutton')[0];
      const addButton = screen.getByRole('button');

      await user.type(nameInput, 'Pequeno');
      await user.type(priceInput, '-10.00');
      await user.click(addButton);

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith(
          expect.arrayContaining([
            expect.objectContaining({ price: -10.00 })
          ])
        );
      });
    });

    it('aceita variações com preço zero', async () => {
      const user = userEvent.setup();
      render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />);

      const nameInput = screen.getAllByRole('textbox')[0];
      const priceInput = screen.getAllByRole('spinbutton')[0];
      const addButton = screen.getByRole('button');

      await user.type(nameInput, 'Médio');
      await user.type(priceInput, '0');
      await user.click(addButton);

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith(
          expect.arrayContaining([
            expect.objectContaining({ price: 0 })
          ])
        );
      });
    });
  });

  // ============================================
  // TESTES DE LEITURA (READ)
  // ============================================

  describe('Listar Variações', () => {
    it('exibe lista vazia corretamente', () => {
      render(<ProductVariationsManager variations={[]} onChange={mockOnChange} />);
      expect(screen.getByText('Nenhuma variação cadastrada')).toBeInTheDocument();
    });

    it('exibe variações existentes', () => {
      const initialVariations: ProductVariation[] = [
        { id: '1', name: 'Pequeno', price: -5 },
        { id: '2', name: 'Grande', price: 10 },
      ];
      render(<ProductVariationsManager variations={initialVariations} onChange={mockOnChange} />);

      expect(screen.getByDisplayValue('Pequeno')).toBeInTheDocument();
      expect(screen.getByDisplayValue('-5')).toBeInTheDocument();
      expect(screen.getByDisplayValue('Grande')).toBeInTheDocument();
      expect(screen.getByDisplayValue('10')).toBeInTheDocument();
    });

    it('exibe contador de variações', () => {
      const initialVariations: ProductVariation[] = [
        { id: '1', name: 'P', price: -5 },
        { id: '2', name: 'M', price: 0 },
        { id: '3', name: 'G', price: 10 },
      ];
      render(<ProductVariationsManager variations={initialVariations} onChange={mockOnChange} />);

      expect(screen.getByText('3')).toBeInTheDocument();
    });
  });

  // ============================================
  // TESTES DE ATUALIZAÇÃO (UPDATE)
  // ============================================

  describe('Editar Variações', () => {
    it('pode editar nome de variação existente', async () => {
      const initialVariations: ProductVariation[] = [
        { id: '1', name: 'Pequeno', price: -5 },
      ];
      render(<ProductVariationsManager variations={initialVariations} onChange={mockOnChange} />);

      const nameInput = screen.getByDisplayValue('Pequeno');
      fireEvent.change(nameInput, { target: { value: 'Extra Pequeno' } });

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith([
          { id: '1', name: 'Extra Pequeno', price: -5 }
        ]);
      });
    });

    it('pode editar preço de variação existente', async () => {
      const initialVariations: ProductVariation[] = [
        { id: '1', name: 'Pequeno', price: -5 },
      ];
      render(<ProductVariationsManager variations={initialVariations} onChange={mockOnChange} />);

      const priceInput = screen.getByDisplayValue('-5');
      fireEvent.change(priceInput, { target: { value: '-8' } });

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith([
          { id: '1', name: 'Pequeno', price: -8 }
        ]);
      });
    });

    it('pode editar múltiplas variações', async () => {
      const user = userEvent.setup();
      const initialVariations: ProductVariation[] = [
        { id: '1', name: 'Pequeno', price: -5 },
        { id: '2', name: 'Grande', price: 10 },
      ];
      render(<ProductVariationsManager variations={initialVariations} onChange={mockOnChange} />);

      const firstNameInput = screen.getByDisplayValue('Pequeno');
      await user.clear(firstNameInput);
      await user.type(firstNameInput, 'Mini');

      const secondPriceInput = screen.getByDisplayValue('10');
      await user.clear(secondPriceInput);
      await user.type(secondPriceInput, '15');

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalled();
      });
    });
  });

  // ============================================
  // TESTES DE EXCLUSÃO (DELETE)
  // ============================================

  describe('Excluir Variações', () => {
    it('pode remover uma variação', async () => {
      const user = userEvent.setup();
      const initialVariations: ProductVariation[] = [
        { id: '1', name: 'Pequeno', price: -5 },
        { id: '2', name: 'Grande', price: 10 },
      ];
      render(<ProductVariationsManager variations={initialVariations} onChange={mockOnChange} />);

      const removeButtons = screen.getAllByRole('button').filter(btn => 
        btn.textContent === '' && btn.className.includes('destructive')
      );

      await user.click(removeButtons[0]);

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith([
          { id: '2', name: 'Grande', price: 10 }
        ]);
      });
    });

    it('pode remover todas as variações', async () => {
      const user = userEvent.setup();
      const initialVariations: ProductVariation[] = [
        { id: '1', name: 'Pequeno', price: -5 },
      ];
      const { rerender } = render(<ProductVariationsManager variations={initialVariations} onChange={mockOnChange} />);

      const removeButton = screen.getAllByRole('button').find(btn => 
        btn.textContent === '' && btn.className.includes('destructive')
      );

      await user.click(removeButton!);

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith([]);
      });

      rerender(<ProductVariationsManager variations={[]} onChange={mockOnChange} />);
      expect(screen.getByText('Nenhuma variação cadastrada')).toBeInTheDocument();
    });
  });
});

// ============================================
// TESTES DE OPCIONAIS
// ============================================

describe('ProductOptionalsManager - CRUD Completo', () => {
  const mockOnChange = jest.fn();

  beforeEach(() => {
    mockOnChange.mockClear();
  });

  describe('Criar Opcionais', () => {
    it('pode adicionar um novo opcional', async () => {
      const user = userEvent.setup();
      render(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />);

      const nameInput = screen.getAllByRole('textbox')[0];
      const priceInput = screen.getAllByRole('spinbutton')[0];
      const addButton = screen.getByRole('button');

      await user.type(nameInput, 'Bacon Extra');
      await user.type(priceInput, '5.00');
      await user.click(addButton);

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith(
          expect.arrayContaining([
            expect.objectContaining({ name: 'Bacon Extra', price: 5.00 })
          ])
        );
      });
    });

    it('não adiciona opcional com nome vazio', async () => {
      const user = userEvent.setup();
      render(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />);

      const priceInput = screen.getAllByRole('spinbutton')[0];
      const addButton = screen.getByRole('button');

      await user.type(priceInput, '5.00');
      await user.click(addButton);

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('não adiciona opcional com preço negativo', async () => {
      const user = userEvent.setup();
      render(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />);

      const nameInput = screen.getAllByRole('textbox')[0];
      const priceInput = screen.getAllByRole('spinbutton')[0];
      const addButton = screen.getByRole('button');

      await user.type(nameInput, 'Item');
      await user.type(priceInput, '-5.00');
      await user.click(addButton);

      // Opcionais não aceitam preço negativo
      expect(mockOnChange).not.toHaveBeenCalled();
    });
  });

  describe('Listar Opcionais', () => {
    it('exibe lista vazia corretamente', () => {
      render(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />);
      expect(screen.getByText('Nenhum opcional cadastrado')).toBeInTheDocument();
    });

    it('exibe opcionais existentes', () => {
      const initialOptionals: ProductOptional[] = [
        { id: '1', name: 'Bacon', price: 5 },
        { id: '2', name: 'Queijo', price: 3 },
      ];
      render(<ProductOptionalsManager optionals={initialOptionals} onChange={mockOnChange} />);

      expect(screen.getByDisplayValue('Bacon')).toBeInTheDocument();
      expect(screen.getByDisplayValue('5')).toBeInTheDocument();
      expect(screen.getByDisplayValue('Queijo')).toBeInTheDocument();
      expect(screen.getByDisplayValue('3')).toBeInTheDocument();
    });
  });

  describe('Editar Opcionais', () => {
    it('pode editar nome de opcional existente', async () => {
      const initialOptionals: ProductOptional[] = [
        { id: '1', name: 'Bacon', price: 5 },
      ];
      render(<ProductOptionalsManager optionals={initialOptionals} onChange={mockOnChange} />);

      const nameInput = screen.getByDisplayValue('Bacon');
      fireEvent.change(nameInput, { target: { value: 'Bacon Premium' } });

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith([
          { id: '1', name: 'Bacon Premium', price: 5 }
        ]);
      });
    });

    it('pode editar preço de opcional existente', async () => {
      const initialOptionals: ProductOptional[] = [
        { id: '1', name: 'Bacon', price: 5 },
      ];
      render(<ProductOptionalsManager optionals={initialOptionals} onChange={mockOnChange} />);

      const priceInput = screen.getByDisplayValue('5');
      fireEvent.change(priceInput, { target: { value: '7' } });

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith([
          { id: '1', name: 'Bacon', price: 7 }
        ]);
      });
    });
  });

  describe('Excluir Opcionais', () => {
    it('pode remover um opcional', async () => {
      const user = userEvent.setup();
      const initialOptionals: ProductOptional[] = [
        { id: '1', name: 'Bacon', price: 5 },
        { id: '2', name: 'Queijo', price: 3 },
      ];
      render(<ProductOptionalsManager optionals={initialOptionals} onChange={mockOnChange} />);

      const removeButtons = screen.getAllByRole('button').filter(btn => 
        btn.textContent === '' && btn.className.includes('destructive')
      );

      await user.click(removeButtons[0]);

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith([
          { id: '2', name: 'Queijo', price: 3 }
        ]);
      });
    });

    it('pode remover todos os opcionais', async () => {
      const user = userEvent.setup();
      const initialOptionals: ProductOptional[] = [
        { id: '1', name: 'Bacon', price: 5 },
      ];
      const { rerender } = render(<ProductOptionalsManager optionals={initialOptionals} onChange={mockOnChange} />);

      const removeButton = screen.getAllByRole('button').find(btn => 
        btn.textContent === '' && btn.className.includes('destructive')
      );

      await user.click(removeButton!);

      await waitFor(() => {
        expect(mockOnChange).toHaveBeenCalledWith([]);
      });

      rerender(<ProductOptionalsManager optionals={[]} onChange={mockOnChange} />);
      expect(screen.getByText('Nenhum opcional cadastrado')).toBeInTheDocument();
    });
  });
});

// ============================================
// TESTES INTEGRADOS
// ============================================

describe('Variações e Opcionais - Cenários Reais', () => {
  it('Cenário: Pizza com tamanhos e complementos', () => {
    const variationsMock = jest.fn();
    const optionalsMock = jest.fn();

    const variations: ProductVariation[] = [
      { id: 'v1', name: 'Pequena (4 fatias)', price: -5.00 },
      { id: 'v2', name: 'Média (6 fatias)', price: 0.00 },
      { id: 'v3', name: 'Grande (8 fatias)', price: 10.00 },
    ];

    const optionals: ProductOptional[] = [
      { id: 'o1', name: 'Borda Recheada - Catupiry', price: 12.00 },
      { id: 'o2', name: 'Borda Recheada - Cheddar', price: 10.00 },
      { id: 'o3', name: 'Bacon Extra', price: 8.00 },
    ];

    const { container } = render(
      <>
        <ProductVariationsManager variations={variations} onChange={variationsMock} />
        <ProductOptionalsManager optionals={optionals} onChange={optionalsMock} />
      </>
    );

    // Verificar se todos os itens aparecem
    expect(screen.getByDisplayValue('Pequena (4 fatias)')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Grande (8 fatias)')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Borda Recheada - Catupiry')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Bacon Extra')).toBeInTheDocument();
  });

  it('Cenário: Hambúrguer com tipo e adicionais', () => {
    const variations: ProductVariation[] = [
      { id: 'v1', name: 'Simples', price: 0.00 },
      { id: 'v2', name: 'Duplo', price: 12.00 },
    ];

    const optionals: ProductOptional[] = [
      { id: 'o1', name: 'Bacon', price: 5.00 },
      { id: 'o2', name: 'Queijo Extra', price: 3.00 },
      { id: 'o3', name: 'Ovo', price: 2.50 },
    ];

    render(
      <>
        <ProductVariationsManager variations={variations} onChange={jest.fn()} />
        <ProductOptionalsManager optionals={optionals} onChange={jest.fn()} />
      </>
    );

    expect(screen.getByDisplayValue('Simples')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Duplo')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Bacon')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Ovo')).toBeInTheDocument();
  });
});

