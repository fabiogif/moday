import { render, screen, fireEvent, waitFor, within } from '@testing-library/react'
import '@testing-library/jest-dom'
import { StoreHero } from '../store-hero'
import { CouponStrip } from '../coupon-strip'
import { MenuOffers, MenuTopSellers } from '../menu-showcases'
import { CartBar, getCartBarMessage } from '../cart-bar'
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

jest.mock('sonner', () => ({
  toast: { success: jest.fn(), error: jest.fn() },
}))

const { toast } = jest.requireMock('sonner') as { toast: { success: jest.Mock; error: jest.Mock } }

const writeText = jest.fn(() => Promise.resolve())

beforeEach(() => {
  jest.clearAllMocks()
  Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true })
  Object.defineProperty(navigator, 'share', { value: undefined, configurable: true })
})

function renderHero(overrides: Partial<React.ComponentProps<typeof StoreHero>> = {}) {
  const props: React.ComponentProps<typeof StoreHero> = {
    name: 'Moday Restaurante',
    logoUrl: '/logo.png',
    coverImageUrl: null,
    rating: { average: 4.8, total: 22 },
    hoursSlot: <span>Aberto</span>,
    deliveryEnabled: true,
    freeDeliveryAbove: 80,
    pickup: { enabled: true, minutes: 15, discountPercent: 10 },
    ordersHref: '/store/moday/track',
    onSearchClick: jest.fn(),
    onRatingClick: jest.fn(),
    onInfoClick: jest.fn(),
    ...overrides,
  }
  render(<StoreHero {...props} />)
  return props
}

describe('StoreHero', () => {
  it('mostra nome, nota, horário, entrega grátis e retirada', () => {
    renderHero()
    expect(screen.getByRole('heading', { level: 1, name: 'Moday Restaurante' })).toBeInTheDocument()
    expect(screen.getByText('4,8')).toBeInTheDocument()
    expect(screen.getByText('(22)')).toBeInTheDocument()
    expect(screen.getByText('Aberto')).toBeInTheDocument()
    expect(screen.getByText('Grátis acima de R$ 80,00')).toBeInTheDocument()
    expect(screen.getByText('~15 min')).toBeInTheDocument()
    expect(screen.getByText('-10%')).toBeInTheDocument()
  })

  it('mostra o atalho "Meus pedidos" para a consulta de pedidos', () => {
    renderHero()
    expect(screen.getByRole('link', { name: 'Meus pedidos' })).toHaveAttribute('href', '/store/moday/track')
  })

  it('tocar na nota leva às avaliações', () => {
    const props = renderHero()
    fireEvent.click(screen.getByRole('button', { name: /^Nota 4,8 de 5, 22 avaliações/ }))
    expect(props.onRatingClick).toHaveBeenCalled()
  })

  it('sem avaliações não mostra nota', () => {
    renderHero({ rating: { average: 0, total: 0 } })
    expect(screen.queryByRole('button', { name: /^Nota/ })).not.toBeInTheDocument()
  })

  it('sem valor de entrega grátis mostra "Calculada no endereço"; sem entrega mostra "Só retirada"', () => {
    renderHero({ freeDeliveryAbove: undefined })
    expect(screen.getByText('Calculada no endereço')).toBeInTheDocument()
  })

  it('loja só com retirada', () => {
    renderHero({ deliveryEnabled: false })
    expect(screen.getByText('Só retirada')).toBeInTheDocument()
  })

  it('sem compartilhamento nativo copia o link e confirma', async () => {
    renderHero()
    fireEvent.click(screen.getByRole('button', { name: 'Compartilhar loja' }))
    await waitFor(() => expect(writeText).toHaveBeenCalledWith(window.location.href))
    expect(toast.success).toHaveBeenCalledWith('Link da loja copiado!')
  })

  it('com compartilhamento nativo usa o menu do aparelho e ignora cancelamento', async () => {
    const share = jest.fn(() => Promise.reject(Object.assign(new Error('cancel'), { name: 'AbortError' })))
    Object.defineProperty(navigator, 'share', { value: share, configurable: true })
    renderHero()
    fireEvent.click(screen.getByRole('button', { name: 'Compartilhar loja' }))
    await waitFor(() => expect(share).toHaveBeenCalledWith({ title: 'Moday Restaurante', url: window.location.href }))
    expect(writeText).not.toHaveBeenCalled()
    expect(toast.error).not.toHaveBeenCalled()
  })
})

describe('CouponStrip', () => {
  it('tocar no cupom copia o código e confirma', async () => {
    render(<CouponStrip coupons={[{ title: 'Primeira compra', highlight: '10% OFF', code: 'MODAY5' }]} />)
    fireEvent.click(screen.getByRole('button', { name: /^Copiar cupom MODAY5/ }))
    await waitFor(() => expect(writeText).toHaveBeenCalledWith('MODAY5'))
    expect(toast.success).toHaveBeenCalledWith('Cupom MODAY5 copiado!')
  })

  it('sem cupons não renderiza nada', () => {
    const { container } = render(<CouponStrip coupons={[]} />)
    expect(container).toBeEmptyDOMElement()
  })
})

const baseProduct: Product = {
  uuid: 'p',
  name: 'Pizza Grande',
  description: '',
  price: 60,
  promotional_price: 30,
  image: '',
  qtd_stock: 5,
  brand: 'Casa',
  categories: [],
}

describe('MenuOffers', () => {
  it('mostra desconto, "a partir de" e o + adiciona', () => {
    const onAdd = jest.fn()
    const onOpen = jest.fn()
    render(
      <MenuOffers
        products={[{ ...baseProduct, variations: [{ id: 'g', name: 'G', price: 5 }] }]}
        onOpen={onOpen}
        onAdd={onAdd}
      />,
    )
    const offers = screen.getByRole('region', { name: 'Ofertas' })
    expect(within(offers).getByText('-50%')).toBeInTheDocument()
    expect(within(offers).getByText('a partir de')).toBeInTheDocument()
    expect(within(offers).getByText('R$ 35,00')).toBeInTheDocument()
    fireEvent.click(within(offers).getByRole('button', { name: 'Adicionar Pizza Grande ao carrinho' }))
    expect(onAdd).toHaveBeenCalled()
    expect(onOpen).not.toHaveBeenCalled()
  })

  it('sem ofertas não renderiza', () => {
    const { container } = render(<MenuOffers products={[]} onOpen={jest.fn()} onAdd={jest.fn()} />)
    expect(container).toBeEmptyDOMElement()
  })
})

describe('MenuTopSellers', () => {
  it('numera o ranking na ordem recebida', () => {
    render(
      <MenuTopSellers
        products={[{ ...baseProduct, uuid: 'a', name: 'Combo Família' }, { ...baseProduct, uuid: 'b', name: 'Yakisoba' }]}
        onOpen={jest.fn()}
      />,
    )
    expect(screen.getByRole('button', { name: /^1º mais pedido: Combo Família/ })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /^2º mais pedido: Yakisoba/ })).toBeInTheDocument()
  })
})

describe('CartBar', () => {
  it('prioriza entrega grátis e depois economia, sem mensagem de pedido mínimo', () => {
    expect(getCartBarMessage(50, 10, 80)).toBe('Faltam R$ 30,00 para entrega grátis')
    expect(getCartBarMessage(90, 10, 80)).toBe('Você economiza R$ 10,00')
    expect(getCartBarMessage(90, 0, 80)).toBeNull()
    expect(getCartBarMessage(20, 0)).toBeNull()
  })

  it('mostra total, itens e abre o carrinho', () => {
    const onOpenCart = jest.fn()
    render(<CartBar logoUrl={null} total={38.25} itemCount={1} savings={12.75} onOpenCart={onOpenCart} />)
    expect(screen.getByText('R$ 38,25')).toBeInTheDocument()
    expect(screen.getByText(/1 item/)).toBeInTheDocument()
    expect(screen.getByRole('status')).toHaveTextContent('Você economiza R$ 12,75')
    fireEvent.click(screen.getByRole('button', { name: 'Ver carrinho' }))
    expect(onOpenCart).toHaveBeenCalled()
  })
})
