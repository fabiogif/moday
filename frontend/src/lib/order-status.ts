export type OrderTrackerStatus =
  | 'Pendente'
  | 'Aceito'
  | 'Preparo'
  | 'Concluído'
  | 'Cancelado'
  | string

export const FINAL_STATUSES = ['Concluído', 'Cancelado'] as const

export type FinalStatus = (typeof FINAL_STATUSES)[number]

export const INTERMEDIATE_STATUSES = ['Pendente', 'Aceito', 'Preparo'] as const

export type IntermediateStatus = (typeof INTERMEDIATE_STATUSES)[number]

/** Índices alinhados ao tracker de 4 passos: Pendente → Aceito → Preparo → Concluído */
const STATUS_STEP_INDEX: Record<string, number> = {
  Pendente: 0,
  'Pedido Recebido': 0,
  Aceito: 1,
  Confirmado: 1,
  Preparo: 2,
  'Em Preparo': 2,
  'Em Preparação': 2,
  Preparando: 2,
  // Sinônimos legados de "saiu para entrega / pronto para retirada" dobram
  // para dentro de Preparo — não existe mais um passo distinto para isso.
  Entrega: 2,
  Pronto: 2,
  'Pronto para Expedição': 2,
  'Saiu para entrega': 2,
  'Aguardando Entregador': 2,
  'A Caminho': 2,
  'Em Entrega': 2,
  Concluído: 3,
  Entregue: 3,
}

export function resolveOrderStatusStepIndex(status: string): number {
  const exact = STATUS_STEP_INDEX[status]
  if (exact !== undefined) {
    return exact
  }

  const normalized = status.toLowerCase()

  if (normalized.includes('cancel')) {
    return -1
  }
  if (normalized.includes('conclu') || normalized === 'entregue') {
    return 3
  }
  if (
    normalized.includes('entrega') ||
    normalized.includes('caminho') ||
    normalized.includes('rota') ||
    normalized.includes('expedi') ||
    normalized.includes('pronto') ||
    normalized.includes('aguardando')
  ) {
    return 2
  }
  if (normalized.includes('prepar')) {
    return 2
  }
  if (normalized.includes('aceit') || normalized.includes('confirm')) {
    return 1
  }
  if (normalized.includes('pendente') || normalized.includes('receb')) {
    return 0
  }

  return 0
}

export function isCancelledOrderStatus(status: string): boolean {
  return status.toLowerCase().includes('cancel')
}

export function isTerminalOrderStatus(status: string): boolean {
  const normalized = status.toLowerCase()
  return (
    normalized.includes('conclu') ||
    normalized === 'entregue' ||
    normalized.includes('cancel') ||
    // Captura defensiva de dados legados: `archived_at` é a fonte de
    // verdade para arquivamento, `status` não deveria mais conter isso.
    normalized.includes('arquiv')
  )
}

function normalizeStatusName(statusName: string | null | undefined): string {
  if (!statusName) return ''
  const normalized = statusName.trim()
  if (normalized.includes('/')) {
    return normalized.split('/')[0].trim()
  }
  return normalized
}

/**
 * Verifica se um status é final (não pode ser editado). Para checar se um
 * pedido está arquivado, use o campo `archived_at` do pedido — não o status.
 */
export function isFinalStatus(status: string | null | undefined): status is FinalStatus {
  if (!status) return false
  return (FINAL_STATUSES as readonly string[]).includes(status)
}

export function canEditOrder(status: string | null | undefined): boolean {
  return !isFinalStatus(status)
}

export function canAdvanceStatus(status: string | null | undefined): boolean {
  if (!status) return false
  return !isFinalStatus(status)
}

export function canCancelOrder(status: string | null | undefined): boolean {
  if (!status) return true
  // Pode cancelar se não for final, ou se já estiver cancelado (para reabrir)
  return !isFinalStatus(status) || status === 'Cancelado'
}

/**
 * Obtém a cor do badge baseado no status
 */
export function getStatusColor(status: string | null | undefined): string {
  if (!status) return 'default'

  switch (status) {
    case 'Pendente':
      return 'yellow'
    case 'Aceito':
      return 'indigo'
    case 'Preparo':
      return 'blue'
    case 'Concluído':
      return 'emerald'
    case 'Cancelado':
      return 'red'
    default:
      return 'default'
  }
}

/**
 * Obtém a descrição do status
 */
export function getStatusDescription(status: string | null | undefined): string {
  if (!status) return 'Status desconhecido'

  const descriptions: Record<string, string> = {
    Pendente: 'Aguardando aceite',
    Aceito: 'Pedido aceito',
    Preparo: 'Em preparação',
    Concluído: 'Pedido concluído',
    Cancelado: 'Pedido cancelado',
  }

  return descriptions[status] || status
}

const CANONICAL_BY_STEP = ['Pendente', 'Aceito', 'Preparo', 'Concluído'] as const

const STATUS_FLOW: Record<string, string> = {
  Pendente: 'Aceito',
  Aceito: 'Preparo',
  Preparo: 'Concluído',
}

/**
 * Normaliza qualquer status (incluindo legados) para o nome canônico do fluxo.
 */
export function toCanonicalStatus(status: string | null | undefined): string | null {
  if (!status) return null

  const normalized = normalizeStatusName(status)
  if (!normalized) return null

  if (isCancelledOrderStatus(normalized)) return 'Cancelado'
  if ((FINAL_STATUSES as readonly string[]).includes(normalized)) return normalized

  const step = resolveOrderStatusStepIndex(normalized)
  if (step < 0) return 'Cancelado'
  if (step >= CANONICAL_BY_STEP.length) return 'Concluído'

  return CANONICAL_BY_STEP[step]
}

/**
 * Obtém o próximo status possível baseado no status atual.
 * Fluxo canônico: Pendente → Aceito → Preparo → Concluído.
 * Aceita sinônimos legados (ex.: Entrega → Preparo → Concluído).
 */
export function getNextStatus(currentStatus: string | null | undefined): string | null {
  if (!currentStatus) return null

  const canonical = toCanonicalStatus(currentStatus)
  if (!canonical || canonical === 'Cancelado' || isFinalStatus(canonical)) return null

  return STATUS_FLOW[canonical] ?? null
}

/**
 * Obtém o nome amigável do próximo status
 */
export function getNextStatusName(currentStatus: string | null | undefined): string | null {
  return getNextStatus(currentStatus)
}

export type BulkAdvanceSelection =
  | { kind: 'ready'; currentStatus: string; nextStatus: string }
  | { kind: 'mixed' }
  | { kind: 'final'; currentStatus: string }
  | { kind: 'empty' }

/**
 * Resolve o avanço em massa a partir dos status selecionados.
 * Só permite avançar se todos estiverem no mesmo status canônico.
 */
export function resolveBulkAdvanceSelection(
  statuses: Array<string | null | undefined>
): BulkAdvanceSelection {
  if (statuses.length === 0) return { kind: 'empty' }

  const canonicals = statuses.map((status) => toCanonicalStatus(status) ?? '')
  const unique = new Set(canonicals.filter(Boolean))

  if (unique.size === 0) return { kind: 'empty' }
  if (unique.size > 1) return { kind: 'mixed' }

  const currentStatus = [...unique][0]
  const nextStatus = getNextStatus(currentStatus)

  if (!nextStatus) {
    return { kind: 'final', currentStatus }
  }

  return { kind: 'ready', currentStatus, nextStatus }
}
