"use client"

/**
 * Hook para gerenciar atualização de pedidos
 * Permite notificar a lista de pedidos quando houver mudanças
 */

import { create } from 'zustand'

interface OrderRefreshStore {
  shouldRefresh: boolean
  triggerRefresh: () => void
  resetRefresh: () => void
}

export const useOrderRefresh = create<OrderRefreshStore>((set) => ({
  shouldRefresh: false,
  triggerRefresh: () => set({ shouldRefresh: true }),
  resetRefresh: () => set({ shouldRefresh: false }),
}))
