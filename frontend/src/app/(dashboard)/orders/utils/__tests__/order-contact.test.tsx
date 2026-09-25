import { render, screen } from '@testing-library/react'
import '@testing-library/jest-dom'
import { orderContact } from '../order-contact'
import { generateOrderReceiptHtml, getClientPhone } from '../order-receipt'
import { OrderCard } from '../../board/components/order-card'
import type { Order } from '../../types'
import type { BoardOrder } from '../../board/types'

const cadastro = { id: 1, name: 'Cadastro Antigo', email: 'cadastro@example.com', phone: '71999999999' }

describe('orderContact', () => {
  it('prefere o contato do pedido (campos planos da API) ao cadastro aninhado', () => {
    expect(orderContact({
      client: cadastro,
      client_full_name: 'Nome Digitado',
      client_phone: '71988888888',
      client_email: 'digitado@example.com',
    })).toEqual({ name: 'Nome Digitado', phone: '71988888888', email: 'digitado@example.com' })
  })

  it('usa o cadastro quando o pedido não traz contato', () => {
    expect(orderContact({ client: cadastro })).toEqual({
      name: 'Cadastro Antigo', phone: '71999999999', email: 'cadastro@example.com',
    })
  })

  it('aceita os campos legados e devolve vazio quando não há nada', () => {
    expect(orderContact({ customerName: 'Legado', customerPhone: '71977777777' }).name).toBe('Legado')
    expect(orderContact({})).toEqual({ name: '', email: '', phone: '' })
  })
})

describe('recibo e quadro mostram o contato do pedido', () => {
  const order = {
    id: 1,
    identify: 'PED1',
    client: cadastro,
    client_full_name: 'Nome Digitado',
    client_phone: '71988888888',
    client_email: 'digitado@example.com',
    products: [],
    total: 10,
    status: 'Em Preparo',
  } as unknown as Order

  it('recibo impresso usa nome, e-mail e telefone digitados', () => {
    const html = generateOrderReceiptHtml(order)
    expect(html).toContain('Nome Digitado')
    expect(html).not.toContain('Cadastro Antigo')
    expect(getClientPhone(order)).toBe('71988888888')
  })

  it('card do quadro mostra o nome digitado', () => {
    render(<OrderCard order={order as unknown as BoardOrder} />)
    expect(screen.getByText('Nome Digitado')).toBeInTheDocument()
    expect(screen.queryByText('Cadastro Antigo')).not.toBeInTheDocument()
  })
})
