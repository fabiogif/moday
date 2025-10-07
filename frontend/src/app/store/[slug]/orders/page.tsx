"use client"

import { useParams } from 'next/navigation'
import Link from 'next/link'
import { ClientOrders } from '@/components/client-orders'
import { ClientAuthProvider } from '@/contexts/client-auth-context'
import { Button } from '@/components/ui/button'
import { ArrowLeft, Store } from 'lucide-react'

export default function ClientOrdersPage() {
  const params = useParams()
  const slug = params.slug as string

  return (
    <ClientAuthProvider>
      <div className="min-h-screen bg-background">
        {/* Header */}
        <header className="border-b">
          <div className="container mx-auto px-4 py-4">
            <div className="flex items-center justify-between">
              <Link 
                href={`/store/${slug}`}
                className="flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors"
              >
                <ArrowLeft className="h-5 w-5" />
                <span>Voltar para a loja</span>
              </Link>
              
              <Link 
                href={`/store/${slug}`}
                className="flex items-center gap-2"
              >
                <Store className="h-5 w-5" />
                <span className="font-semibold">Loja</span>
              </Link>
            </div>
          </div>
        </header>

        {/* Main Content */}
        <main className="container mx-auto px-4 py-8">
          <ClientOrders slug={slug} />
        </main>

        {/* Footer */}
        <footer className="border-t mt-12">
          <div className="container mx-auto px-4 py-6">
            <div className="flex flex-col md:flex-row items-center justify-between gap-4">
              <p className="text-sm text-muted-foreground">
                © 2025 Sistema de Pedidos. Todos os direitos reservados.
              </p>
              <div className="flex gap-4">
                <Link 
                  href={`/store/${slug}`}
                  className="text-sm text-muted-foreground hover:text-foreground transition-colors"
                >
                  Continuar Comprando
                </Link>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </ClientAuthProvider>
  )
}
