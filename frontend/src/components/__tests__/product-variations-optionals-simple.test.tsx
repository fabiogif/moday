import React from 'react';
import { render, screen } from '@testing-library/react';
import { ProductVariationsManager } from '../product-variations-manager';
import { ProductOptionalsManager } from '../product-optionals-manager';
import { ProductVariation, ProductOptional } from '@/types/product-variations';

// Mock dos componentes UI
jest.mock('@/components/ui/input', () => ({
  Input: ({ value, ...props }: any) => <input value={value || ''} {...props} />,
}));

jest.mock('@/components/ui/button', () => ({
  Button: ({ children, ...props }: any) => <button {...props}>{children}</button>,
}));

jest.mock('@/components/ui/card', () => ({
  Card: ({ children, ...props }: any) => <div data-testid="card" {...props}>{children}</div>,
  CardContent: ({ children }: any) => <div>{children}</div>,
  CardHeader: ({ children }: any) => <div>{children}</div>,
  CardTitle: ({ children }: any) => <h2>{children}</h2>,
  CardDescription: ({ children }: any) => <p>{children}</p>,
}));

jest.mock('@/components/ui/label', () => ({
  Label: ({ children }: any) => <label>{children}</label>,
}));

jest.mock('@/components/ui/badge', () => ({
  Badge: ({ children }: any) => <span>{children}</span>,
}));

describe('ProductVariationsManager - Testes Simples', () => {
  it('renderiza sem variações', () => {
    render(<ProductVariationsManager variations={[]} onChange={jest.fn()} />);
    expect(screen.getByText('Nenhuma variação cadastrada')).toBeInTheDocument();
  });

  it('renderiza com variações', () => {
    const variations: ProductVariation[] = [
      { id: '1', name: 'Pequeno', price: -5 },
      { id: '2', name: 'Grande', price: 10 },
    ];
    
    render(<ProductVariationsManager variations={variations} onChange={jest.fn()} />);
    
    expect(screen.getByDisplayValue('Pequeno')).toBeInTheDocument();
    expect(screen.getByDisplayValue('-5')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Grande')).toBeInTheDocument();
    expect(screen.getByDisplayValue('10')).toBeInTheDocument();
  });

  it('exibe contador de variações', () => {
    const variations: ProductVariation[] = [
      { id: '1', name: 'P', price: -5 },
      { id: '2', name: 'M', price: 0 },
      { id: '3', name: 'G', price: 10 },
    ];
    
    render(<ProductVariationsManager variations={variations} onChange={jest.fn()} />);
    
    expect(screen.getByText('3')).toBeInTheDocument();
  });

  it('exibe texto de ajuda correto', () => {
    render(<ProductVariationsManager variations={[]} onChange={jest.fn()} />);
    
    expect(screen.getByText(/Variações são opções exclusivas/i)).toBeInTheDocument();
  });

  it('exibe campo de adicionar nova variação', () => {
    render(<ProductVariationsManager variations={[]} onChange={jest.fn()} />);
    
    expect(screen.getByPlaceholderText(/Nome/i)).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/0,00/i)).toBeInTheDocument();
  });
});

describe('ProductOptionalsManager - Testes Simples', () => {
  it('renderiza sem opcionais', () => {
    render(<ProductOptionalsManager optionals={[]} onChange={jest.fn()} />);
    expect(screen.getByText('Nenhum opcional cadastrado')).toBeInTheDocument();
  });

  it('renderiza com opcionais', () => {
    const optionals: ProductOptional[] = [
      { id: '1', name: 'Bacon', price: 5 },
      { id: '2', name: 'Queijo', price: 3 },
    ];
    
    render(<ProductOptionalsManager optionals={optionals} onChange={jest.fn()} />);
    
    expect(screen.getByDisplayValue('Bacon')).toBeInTheDocument();
    expect(screen.getByDisplayValue('5')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Queijo')).toBeInTheDocument();
    expect(screen.getByDisplayValue('3')).toBeInTheDocument();
  });

  it('exibe contador de opcionais', () => {
    const optionals: ProductOptional[] = [
      { id: '1', name: 'Bacon', price: 5 },
      { id: '2', name: 'Queijo', price: 3 },
    ];
    
    render(<ProductOptionalsManager optionals={optionals} onChange={jest.fn()} />);
    
    expect(screen.getByText('2')).toBeInTheDocument();
  });

  it('exibe texto de ajuda correto', () => {
    render(<ProductOptionalsManager optionals={[]} onChange={jest.fn()} />);
    
    expect(screen.getByText(/Opcionais são adicionais/i)).toBeInTheDocument();
  });

  it('aceita inputs controlados sem warnings', () => {
    const optionals: ProductOptional[] = [
      { id: '1', name: 'Bacon', price: 5 },
    ];
    
    const { container } = render(<ProductOptionalsManager optionals={optionals} onChange={jest.fn()} />);
    
    const inputs = container.querySelectorAll('input');
    inputs.forEach(input => {
      expect(input.value).not.toBe(undefined);
    });
  });
});

describe('Cenários Reais', () => {
  it('Pizza com variações e opcionais', () => {
    const variations: ProductVariation[] = [
      { id: 'v1', name: 'Pequena (4 fatias)', price: -5.00 },
      { id: 'v2', name: 'Média (6 fatias)', price: 0.00 },
      { id: 'v3', name: 'Grande (8 fatias)', price: 10.00 },
    ];

    const optionals: ProductOptional[] = [
      { id: 'o1', name: 'Borda Catupiry', price: 12.00 },
      { id: 'o2', name: 'Borda Cheddar', price: 10.00 },
    ];

    render(
      <>
        <ProductVariationsManager variations={variations} onChange={jest.fn()} />
        <ProductOptionalsManager optionals={optionals} onChange={jest.fn()} />
      </>
    );

    expect(screen.getByDisplayValue('Pequena (4 fatias)')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Grande (8 fatias)')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Borda Catupiry')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Borda Cheddar')).toBeInTheDocument();
  });

  it('Hambúrguer com tipo e complementos', () => {
    const variations: ProductVariation[] = [
      { id: 'v1', name: 'Simples', price: 0.00 },
      { id: 'v2', name: 'Duplo', price: 12.00 },
    ];

    const optionals: ProductOptional[] = [
      { id: 'o1', name: 'Bacon', price: 5.00 },
      { id: 'o2', name: 'Queijo', price: 3.00 },
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

