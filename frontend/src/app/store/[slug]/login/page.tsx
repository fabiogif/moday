"use client"

import { useState } from 'react'
import { useParams, useRouter } from 'next/navigation'
import Link from 'next/link'
import { useClientAuth } from '@/contexts/client-auth-context'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Store, ArrowLeft, LogIn, Loader2 } from 'lucide-react'
import { toast } from 'sonner'

export default function ClientLoginPage() {
  const params = useParams()
  const router = useRouter()
  const slug = params.slug as string
  const { login, isAuthenticated } = useClientAuth()

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  // Se já estiver autenticado, redirecionar
  if (isAuthenticated) {
    router.push(`/store/${slug}`)
    return null
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      await login(email, password, slug)
      toast.success('Login realizado com sucesso!')
      
      // Redirecionar para a loja ou página anterior
      const returnUrl = new URLSearchParams(window.location.search).get('return') || `/store/${slug}`
      router.push(returnUrl)
    } catch (err: any) {
      const errorMessage = err.message || 'Erro ao fazer login. Verifique suas credenciais.'
      setError(errorMessage)
      toast.error(errorMessage)
    } finally {
      setLoading(false)
    }
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
            <h1 className="text-3xl font-bold">Login do Cliente</h1>
          </div>
          <p className="text-muted-foreground">
            Acesse sua conta para ver pedidos e mais
          </p>
        </div>

        {/* Login Card */}
        <Card>
          <CardHeader>
            <CardTitle>Bem-vindo de volta!</CardTitle>
            <CardDescription>
              Entre com seu email e senha para continuar
            </CardDescription>
          </CardHeader>
          
          <form onSubmit={handleSubmit}>
            <CardContent className="space-y-4">
              {error && (
                <Alert variant="destructive">
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}

              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="seu@email.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  disabled={loading}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Senha</Label>
                <Input
                  id="password"
                  type="password"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  disabled={loading}
                  minLength={6}
                />
              </div>
            </CardContent>

            <CardFooter className="flex flex-col gap-4">
              <Button 
                type="submit" 
                className="w-full"
                disabled={loading}
              >
                {loading ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Entrando...
                  </>
                ) : (
                  <>
                    <LogIn className="mr-2 h-4 w-4" />
                    Entrar
                  </>
                )}
              </Button>

              <div className="text-center text-sm text-muted-foreground">
                Não tem uma conta?{' '}
                <Link 
                  href={`/store/${slug}/register`}
                  className="text-primary hover:underline font-medium"
                >
                  Criar conta
                </Link>
              </div>
            </CardFooter>
          </form>
        </Card>

        {/* Info Box */}
        <Card className="bg-muted">
          <CardContent className="pt-6">
            <p className="text-sm text-muted-foreground text-center">
              💡 <strong>Dica:</strong> Você também pode criar uma conta durante o checkout ao fazer um pedido!
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
