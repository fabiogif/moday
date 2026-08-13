"use client"

import { useParams } from "next/navigation"
import { ArrowLeft } from "lucide-react"
import { Button } from "@/components/ui/button"
import Link from "next/link"
import { OrderTrack } from "../components/order-track"
import { SiteFooter } from "@/components/site-footer"

export default function OrderTrackPage() {
  const params = useParams()
  const slug = params.slug as string

  return (
    <div className="min-h-screen flex flex-col bg-background">
      {/* Header */}
      <header className="border-b sticky top-0 z-50 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <Link href={`/store/${slug}`}>
              <Button variant="ghost" size="sm">
                <ArrowLeft className="h-4 w-4 mr-2" />
                Voltar para a loja
              </Button>
            </Link>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-1 container mx-auto px-4 py-8">
        <div className="max-w-4xl mx-auto space-y-6">
          <div className="text-center space-y-2">
            <h1 className="text-3xl font-bold tracking-tight">
              Acompanhe seu Pedido
            </h1>
            <p className="text-muted-foreground">
              Consulte o status do seu pedido em tempo real
            </p>
          </div>

          <OrderTrack slug={slug} />

          <div className="p-4 bg-muted rounded-lg text-sm text-muted-foreground">
            <p className="font-semibold mb-2">ℹ️ Informações importantes:</p>
            <ul className="list-disc list-inside space-y-1">
              <li>Os pedidos ficam disponíveis para consulta enquanto estiverem em andamento</li>
              <li>Após a conclusão, o pedido fica disponível por até 24 horas</li>
              <li>Você pode consultar usando seu CPF ou telefone cadastrado</li>
            </ul>
          </div>
        </div>
      </main>

      {/* Footer */}
      <SiteFooter />
    </div>
  )
}

