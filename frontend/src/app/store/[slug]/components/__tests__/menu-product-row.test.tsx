import { render, screen, fireEvent } from '@testing-library/react'
import '@testing-library/jest-dom'
import { MenuProductRow } from '../menu-product-row'
import { ProductImagePlaceholder } from '../product-image-placeholder'
import type { Product } from '../../menu-utils'

jest.mock('next/image', () => {
  const { createElement } = jest.requireActual('react') as typeof import('react')
  const nextOnlyProps = ['fill', 'sizes', 'priority']
  return {
    __esModule: true,
    default: (props: Record<string, unknown>) =>
      createElement('img', Object.fromEntries(Object.entries(props).filter(([key]) => !nextOnlyProps.includes(key)))),
  }
})

const product: Product = {
  uuid: 'p1',
  name: 'Rolinho de Queijo',
  description: '1 unidade',
  price: 21,
  promotional_price: 10.5,
  image: '/rolinho.jpg',
  qtd_stock: 10,
  brand: 'Casa',
  categories: [{ uuid: 'c1', name: 'Entrada' }],
}

function renderRow(overrides: Partial<Product> = {}) {
  const onOpen = jest.fn()
  const onAdd = jest.fn()
  render(<MenuProductRow product={{ ...product, ...overrides }} onOpen={onOpen} onAdd={onAdd} />)
  return { onOpen, onAdd }
}

describe('MenuProductRow', () => {
  it('mostra nome, descrição, preço promocional, preço riscado e desconto', () => {
    renderRow()
    expect(screen.getByText('Rolinho de Queijo')).toBeInTheDocument()
    expect(screen.getByText('1 unidade')).toBeInTheDocument()
    expect(screen.getByText('R$ 10,50')).toBeInTheDocument()
    expect(screen.getByText('R$ 21,00')).toHaveClass('line-through')
    expect(screen.getByText('-50%')).toBeInTheDocument()
    expect(screen.getByText('de R$ 21,00 por R$ 10,50')).toHaveClass('sr-only')
  })

  it('tocar na linha abre os detalhes', () => {
    const { onOpen, onAdd } = renderRow()
    fireEvent.click(screen.getByRole('button', { name: 'Ver detalhes de Rolinho de Queijo' }))
    expect(onOpen).toHaveBeenCalledWith(expect.objectContaining({ uuid: 'p1' }))
    expect(onAdd).not.toHaveBeenCalled()
  })

  it('o botão + adiciona sem abrir os detalhes', () => {
    const { onOpen, onAdd } = renderRow()
    fireEvent.click(screen.getByRole('button', { name: 'Adicionar Rolinho de Queijo ao carrinho' }))
    expect(onAdd).toHaveBeenCalledTimes(1)
    expect(onOpen).not.toHaveBeenCalled()
  })

  it('produto com variações mostra "a partir de"', () => {
    renderRow({ promotional_price: undefined, variations: [{ id: 'g', name: 'Grande', price: 9 }] })
    expect(screen.getByText('a partir de')).toBeInTheDocument()
    expect(screen.getByText('R$ 30,00')).toBeInTheDocument()
  })

  it('produto esgotado mostra "Esgotado" e não tem botão +', () => {
    renderRow({ qtd_stock: 0 })
    expect(screen.getByText('Esgotado')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /Adicionar/ })).not.toBeInTheDocument()
  })

  it('sem imagem usa o placeholder', () => {
    renderRow({ image: '' })
    expect(screen.getByTestId('product-image-placeholder')).toBeInTheDocument()
    expect(document.querySelector('img')).toBeNull()
  })
})

describe('ProductImagePlaceholder', () => {
  it('renderiza só SVG, escondido de leitores de tela, sem imagem externa', () => {
    render(<ProductImagePlaceholder />)
    const placeholder = screen.getByTestId('product-image-placeholder')
    expect(placeholder).toHaveAttribute('aria-hidden')
    expect(placeholder.querySelectorAll('svg').length).toBeGreaterThan(0)
    expect(placeholder.querySelector('img')).toBeNull()
  })
})
