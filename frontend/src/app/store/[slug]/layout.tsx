import type { ReactNode } from 'react'
import { ClientAuthProvider } from '@/contexts/client-auth-context'

// Sessão do cliente final disponível para todas as páginas da loja (login, cadastro, pedidos)
export default function StoreLayout({ children }: { children: ReactNode }) {
  return <ClientAuthProvider>{children}</ClientAuthProvider>
}
