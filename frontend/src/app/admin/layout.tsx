'use client'

import { useEffect } from 'react'
import { usePathname, useRouter } from 'next/navigation'
import { AdminAuthProvider, useAdminAuth } from '@/contexts/admin-auth-context'
import { AdminSidebar } from '@/components/admin/admin-sidebar'

function AdminLayoutInner({ children }: { children: React.ReactNode }) {
  const pathname = usePathname()
  const router = useRouter()
  const { isAuthenticated, isLoading } = useAdminAuth()

  // Páginas que NÃO precisam de sidebar (como login)
  const publicPages = ['/admin/login']
  const isPublicPage = publicPages.includes(pathname)

  useEffect(() => {
    if (!isLoading && !isAuthenticated && !isPublicPage) {
      router.push('/admin/login')
    }
  }, [isLoading, isAuthenticated, isPublicPage, router])

  // Se for página pública (login), não mostra sidebar
  if (isPublicPage) {
    return <>{children}</>
  }

  // Se não está autenticado, não renderiza nada (vai redirecionar)
  if (!isAuthenticated) {
    return null
  }

  // Páginas autenticadas: mostra sidebar
  return (
    <div className="flex h-screen overflow-hidden">
      <AdminSidebar />
      <main className="flex-1 overflow-y-auto">
        <div className="container mx-auto py-6">
          {children}
        </div>
      </main>
    </div>
  )
}

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <AdminAuthProvider>
      <AdminLayoutInner>{children}</AdminLayoutInner>
    </AdminAuthProvider>
  )
}

