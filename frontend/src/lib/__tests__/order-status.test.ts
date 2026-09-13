import {
  isCancelledOrderStatus,
  isTerminalOrderStatus,
  isFinalStatus,
  resolveOrderStatusStepIndex,
  getNextStatus,
  getNextStatusName,
  toCanonicalStatus,
  resolveBulkAdvanceSelection,
  getNextStatusFromList,
  getTerminalStatusFromList,
  isStepBeforeTerminal,
  findCancelledStatus,
  type OrderStatusRecord,
} from '../order-status'

describe('order-status', () => {
  it('mapeia os 5 status canônicos do seeder', () => {
    expect(resolveOrderStatusStepIndex('Pendente')).toBe(0)
    expect(resolveOrderStatusStepIndex('Aceito')).toBe(1)
    expect(resolveOrderStatusStepIndex('Preparo')).toBe(2)
    expect(resolveOrderStatusStepIndex('Concluído')).toBe(3)
  })

  it('dobra sinônimos legados de "saiu para entrega/pronto" para dentro de Preparo', () => {
    expect(resolveOrderStatusStepIndex('Entrega')).toBe(2)
    expect(resolveOrderStatusStepIndex('Em Preparo')).toBe(2)
    expect(resolveOrderStatusStepIndex('Em Preparação')).toBe(2)
    expect(resolveOrderStatusStepIndex('Pronto para Expedição')).toBe(2)
    expect(resolveOrderStatusStepIndex('Aguardando Entregador')).toBe(2)
    expect(resolveOrderStatusStepIndex('Em Entrega')).toBe(2)
    expect(resolveOrderStatusStepIndex('Pronto')).toBe(2)
    expect(resolveOrderStatusStepIndex('Saiu para entrega')).toBe(2)
    expect(resolveOrderStatusStepIndex('A Caminho')).toBe(2)
  })

  it('mapeia outros status legados para o índice canônico correto', () => {
    expect(resolveOrderStatusStepIndex('Pedido Recebido')).toBe(0)
    expect(resolveOrderStatusStepIndex('Confirmado')).toBe(1)
    expect(resolveOrderStatusStepIndex('Entregue')).toBe(3)
  })

  it('identifica cancelamento', () => {
    expect(isCancelledOrderStatus('Cancelado')).toBe(true)
    expect(resolveOrderStatusStepIndex('Cancelado')).toBe(-1)
  })

  it('isTerminalOrderStatus reconhece Concluído e Cancelado', () => {
    expect(isTerminalOrderStatus('Concluído')).toBe(true)
    expect(isTerminalOrderStatus('Cancelado')).toBe(true)
    expect(isTerminalOrderStatus('Preparo')).toBe(false)
  })

  it('isFinalStatus só reconhece Concluído e Cancelado', () => {
    expect(isFinalStatus('Concluído')).toBe(true)
    expect(isFinalStatus('Cancelado')).toBe(true)
    expect(isFinalStatus('Preparo')).toBe(false)
    expect(isFinalStatus('Entregue')).toBe(false)
    expect(isFinalStatus(null)).toBe(false)
  })

  it('getNextStatus segue o fluxo canônico de 3 saltos', () => {
    expect(getNextStatus('Pendente')).toBe('Aceito')
    expect(getNextStatus('Aceito')).toBe('Preparo')
    expect(getNextStatus('Preparo')).toBe('Concluído')
    expect(getNextStatus('Concluído')).toBeNull()
    expect(getNextStatus('Cancelado')).toBeNull()
  })

  it('getNextStatus avança a partir de status legados', () => {
    expect(getNextStatus('Entrega')).toBe('Concluído')
    expect(getNextStatus('Em Preparo')).toBe('Concluído')
    expect(getNextStatus('Confirmado')).toBe('Preparo')
    expect(getNextStatus('Pedido Recebido')).toBe('Aceito')
    expect(getNextStatus('Entregue')).toBeNull()
  })

  it('toCanonicalStatus normaliza sinônimos', () => {
    expect(toCanonicalStatus('Entrega')).toBe('Preparo')
    expect(toCanonicalStatus('Confirmado')).toBe('Aceito')
    expect(toCanonicalStatus('Entregue')).toBe('Concluído')
  })

  it('resolveBulkAdvanceSelection exige status iguais', () => {
    expect(resolveBulkAdvanceSelection(['Pendente', 'Pendente'])).toEqual({
      kind: 'ready',
      currentStatus: 'Pendente',
      nextStatus: 'Aceito',
    })
    expect(resolveBulkAdvanceSelection(['Pendente', 'Aceito'])).toEqual({
      kind: 'mixed',
    })
    expect(resolveBulkAdvanceSelection(['Concluído', 'Concluído'])).toEqual({
      kind: 'final',
      currentStatus: 'Concluído',
    })
    expect(resolveBulkAdvanceSelection(['Entrega', 'Preparo'])).toEqual({
      kind: 'ready',
      currentStatus: 'Preparo',
      nextStatus: 'Concluído',
    })
  })

  it('getNextStatusName espelha getNextStatus', () => {
    expect(getNextStatusName('Aceito')).toBe('Preparo')
  })
})

// Reproduz o bug de produção: um tenant com status renomeados (ex.: "Recebido",
// "Preparando") não é reconhecido pelas funções baseadas em nomes fixos acima.
// As funções por order_position abaixo resolvem isso usando o dado real do
// tenant em vez de um dicionário de nomes em português.
describe('funções baseadas em order_position (status customizados por tenant)', () => {
  const customStatuses: OrderStatusRecord[] = [
    { name: 'Recebido', order_position: 1, is_active: true },
    { name: 'Preparando', order_position: 2, is_active: true },
    { name: 'Entrega', order_position: 3, is_active: true },
    { name: 'Concluído', order_position: 4, is_active: true },
    { name: 'Cancelado', order_position: 5, is_active: true },
  ]

  it('getNextStatusFromList acha o próximo por posição, não por nome fixo', () => {
    expect(getNextStatusFromList(customStatuses, 'Recebido')).toEqual(
      expect.objectContaining({ name: 'Preparando' })
    )
    expect(getNextStatusFromList(customStatuses, 'Entrega')).toEqual(
      expect.objectContaining({ name: 'Concluído' })
    )
  })

  it('getNextStatusFromList retorna null no último status do fluxo normal', () => {
    expect(getNextStatusFromList(customStatuses, 'Concluído')).toBeNull()
  })

  it('getNextStatusFromList retorna null para um status desconhecido', () => {
    expect(getNextStatusFromList(customStatuses, 'Não Existe')).toBeNull()
  })

  it('getTerminalStatusFromList acha o último status ativo, ignorando Cancelado', () => {
    expect(getTerminalStatusFromList(customStatuses)).toEqual(
      expect.objectContaining({ name: 'Concluído' })
    )
  })

  it('isStepBeforeTerminal reconhece o penúltimo passo do fluxo custom', () => {
    expect(isStepBeforeTerminal(customStatuses, 'Entrega')).toBe(true)
    expect(isStepBeforeTerminal(customStatuses, 'Recebido')).toBe(false)
    expect(isStepBeforeTerminal(customStatuses, 'Preparando')).toBe(false)
  })

  it('findCancelledStatus acha o status de cancelamento pelo nome', () => {
    expect(findCancelledStatus(customStatuses)).toEqual(
      expect.objectContaining({ name: 'Cancelado' })
    )
  })

  it('status inativos são ignorados na ordenação', () => {
    const withInactive: OrderStatusRecord[] = [
      ...customStatuses,
      { name: 'Passo Extra', order_position: 2.5, is_active: false },
    ]
    expect(getNextStatusFromList(withInactive, 'Recebido')).toEqual(
      expect.objectContaining({ name: 'Preparando' })
    )
  })
})
