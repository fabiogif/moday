"use client"

import { useParams, useRouter } from 'next/navigation'
import Link from 'next/link'
import { useClientAuth } from '@/contexts/client-auth-context'
import { ClientRegisterForm } from '@/components/client-register-form'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Store, ArrowLeft } from 'lucide-react'

export default function ClientRegisterPage() {
  const params = useParams()
  const router = useRouter()
  const slug = params.slug as string
  const { isAuthenticated } = useClientAuth()

  // Se já estiver autenticado, redirecionar
  if (isAuthenticated) {
    router.push(`/store/${slug}`)
    return null
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted flex items-center justify-center p-4">
      <div className="w-full max-w-md space-y-4">
        {/* Header */}
        <div className="text-center space-y-2">
          <Link 
            href={`/store/${slug}`}
            className="inline-flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors mb-4"
          >
            <ArrowLeft className="h-4 w-4" />
            <span className="text-sm">Voltar para a loja</span>
          </Link>
          
          <div className="flex items-center justify-center gap-2">
            <Store className="h-8 w-8" />
            <h1 className="text-3xl font-bold">Criar Conta</h1>
          </div>
          <p className="text-muted-foreground">
            Cadastre-se para acompanhar seus pedidos
          </p>
        </div>

        {/* Register Card */}
        <Card>
          <CardHeader>
            <CardTitle>Bem-vindo!</CardTitle>
            <CardDescription>
              Preencha os dados abaixo para criar sua conta
            </CardDescription>
          </CardHeader>
          
          <CardContent>
            <ClientRegisterForm slug={slug} onSuccess={() => router.push(`/store/${slug}`)}>
              <div className="text-center text-sm text-muted-foreground">
                Já tem uma conta?{' '}
                <Link 
                  href={`/store/${slug}/login`}
                  className="text-primary hover:underline font-medium"
                >
                  Fazer login
                </Link>
              </div>
            </ClientRegisterForm>
          </CardContent>
        </Card>

        {/* Info Box */}
        <Card className="bg-muted">
          <CardContent className="pt-6">
            <p className="text-sm text-muted-foreground text-center">
              🔒 <strong>Seus dados estão seguros.</strong> Usamos criptografia para proteger suas informações.
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
