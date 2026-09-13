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

// ---------------------------------------------------------------------------
// Funções baseadas em order_position (dado real do tenant), não em nomes fixos.
//
// As funções acima (toCanonicalStatus/getNextStatus/STATUS_FLOW) assumem que
// todo tenant usa os nomes canônicos "Pendente/Aceito/Preparo/Concluído". Um
// tenant pode renomear e reordenar seus status livremente (ex.: produção usa
// "Recebido, Preparando, Entrega, Concluído, Cancelado"), então qualquer coisa
// que precise do PRÓXIMO status real (rótulo de botão, liberar "Finalizar",
// achar o status terminal) deve usar order_position — a mesma correção já
// aplicada em OrderService::getNextStatus no backend.
// ---------------------------------------------------------------------------

export interface OrderStatusRecord {
  name: string
  order_position?: number | null
  is_active?: boolean
  [key: string]: unknown
}

function orderedActiveStatuses(statuses: OrderStatusRecord[]): OrderStatusRecord[] {
  return statuses
    .filter((s) => s.is_active !== false && !isCancelledOrderStatus(s.name))
    .sort((a, b) => (a.order_position ?? 0) - (b.order_position ?? 0))
}

/** Próximo status real do tenant (por order_position), a partir da lista carregada. */
export function getNextStatusFromList(
  statuses: OrderStatusRecord[],
  currentStatusName: string | null | undefined
): OrderStatusRecord | null {
  if (!currentStatusName || !statuses?.length) return null
  const ordered = orderedActiveStatuses(statuses)
  const currentIndex = ordered.findIndex((s) => s.name === currentStatusName)
  if (currentIndex === -1) return null
  return ordered[currentIndex + 1] ?? null
}

/** Último status do fluxo normal do tenant (o que representa "concluído"), por posição. */
export function getTerminalStatusFromList(statuses: OrderStatusRecord[]): OrderStatusRecord | null {
  const ordered = orderedActiveStatuses(statuses)
  return ordered[ordered.length - 1] ?? null
}

/** true quando o status atual é o penúltimo do fluxo — passo imediatamente antes do terminal. */
export function isStepBeforeTerminal(
  statuses: OrderStatusRecord[],
  currentStatusName: string | null | undefined
): boolean {
  if (!currentStatusName || !statuses?.length) return false
  const ordered = orderedActiveStatuses(statuses)
  const currentIndex = ordered.findIndex((s) => s.name === currentStatusName)
  return currentIndex !== -1 && currentIndex === ordered.length - 2
}

/** Status de cancelamento do tenant, identificado pelo nome (ex.: "Cancelado"). */
export function findCancelledStatus(statuses: OrderStatusRecord[]): OrderStatusRecord | null {
  return statuses.find((s) => isCancelledOrderStatus(s.name)) ?? null
}
