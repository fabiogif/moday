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
import { Store, ArrowLeft, UserPlus, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { maskCPF, validateCPF, maskPhone, validatePhone, validateEmail } from '@/lib/masks'

export default function ClientRegisterPage() {
  const params = useParams()
  const router = useRouter()
  const slug = params.slug as string
  const { register, isAuthenticated } = useClientAuth()

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    cpf: '',
    password: '',
    password_confirmation: '',
  })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  // Se já estiver autenticado, redirecionar
  if (isAuthenticated) {
    router.push(`/store/${slug}`)
    return null
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target
    let maskedValue = value

    // Aplicar máscaras
    if (name === 'cpf') {
      maskedValue = maskCPF(value)
    } else if (name === 'phone') {
      maskedValue = maskPhone(value)
    }

    setFormData(prev => ({
      ...prev,
      [name]: maskedValue
    }))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    // Validar email
    if (!validateEmail(formData.email)) {
      setError('Email inválido')
      toast.error('Por favor, insira um email válido')
      return
    }

    // Validar telefone
    if (!validatePhone(formData.phone)) {
      setError('Telefone inválido')
      toast.error('Por favor, insira um telefone válido com DDD')
      return
    }

    // Validar CPF se fornecido
    if (formData.cpf && !validateCPF(formData.cpf)) {
      setError('CPF inválido')
      toast.error('Por favor, insira um CPF válido')
      return
    }

    // Validar senhas
    if (formData.password !== formData.password_confirmation) {
      setError('As senhas não coincidem')
      toast.error('As senhas não coincidem')
      return
    }

    if (formData.password.length < 6) {
      setError('A senha deve ter pelo menos 6 caracteres')
      toast.error('A senha deve ter pelo menos 6 caracteres')
      return
    }

    setLoading(true)

    try {
      await register(formData, slug)
      toast.success('Conta criada com sucesso!')
      
      // Redirecionar para a loja
      router.push(`/store/${slug}`)
    } catch (err: any) {
      const errorMessage = err.message || 'Erro ao criar conta. Tente novamente.'
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
          
          <form onSubmit={handleSubmit}>
            <CardContent className="space-y-4">
              {error && (
                <Alert variant="destructive">
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}

              <div className="space-y-2">
                <Label htmlFor="name">Nome Completo *</Label>
                <Input
                  id="name"
                  name="name"
                  type="text"
                  placeholder="João Silva"
                  value={formData.name}
                  onChange={handleChange}
                  required
                  disabled={loading}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="email">Email *</Label>
                <Input
                  id="email"
                  name="email"
                  type="email"
                  placeholder="joao@email.com"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  disabled={loading}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="phone">Telefone *</Label>
                <Input
                  id="phone"
                  name="phone"
                  type="tel"
                  placeholder="(11) 99999-9999"
                  value={formData.phone}
                  onChange={handleChange}
                  required
                  disabled={loading}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="cpf">CPF (opcional)</Label>
                <Input
                  id="cpf"
                  name="cpf"
                  type="text"
                  placeholder="000.000.000-00"
                  value={formData.cpf}
                  onChange={handleChange}
                  disabled={loading}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Senha *</Label>
                <Input
                  id="password"
                  name="password"
                  type="password"
                  placeholder="Mínimo 6 caracteres"
                  value={formData.password}
                  onChange={handleChange}
                  required
                  disabled={loading}
                  minLength={6}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password_confirmation">Confirmar Senha *</Label>
                <Input
                  id="password_confirmation"
                  name="password_confirmation"
                  type="password"
                  placeholder="Digite a senha novamente"
                  value={formData.password_confirmation}
                  onChange={handleChange}
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
                    Criando conta...
                  </>
                ) : (
                  <>
                    <UserPlus className="mr-2 h-4 w-4" />
                    Criar Conta
                  </>
                )}
              </Button>

              <div className="text-center text-sm text-muted-foreground">
                Já tem uma conta?{' '}
                <Link 
                  href={`/store/${slug}/login`}
                  className="text-primary hover:underline font-medium"
                >
                  Fazer login
                </Link>
              </div>
            </CardFooter>
          </form>
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
