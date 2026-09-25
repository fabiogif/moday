"use client"

import { useState, type ReactNode } from 'react'
import { useClientAuth, type ClientUser } from '@/contexts/client-auth-context'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { PasswordInput } from '@/components/ui/password-input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { UserPlus, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { maskCPF, validateCPF, maskPhone, validatePhone, validateEmail } from '@/lib/masks'

interface ClientRegisterFormProps {
  slug: string
  onSuccess: (client: ClientUser) => void
  onSubmittingChange?: (submitting: boolean) => void
  submitLabel?: string
  /** Ações extras abaixo do botão (link de login, "continuar sem cadastro"...) */
  children?: ReactNode
}

// Cadastro do cliente final na loja — usado pela página /register e pela modal do checkout
export function ClientRegisterForm({
  slug,
  onSuccess,
  onSubmittingChange,
  submitLabel = 'Criar Conta',
  children,
}: ClientRegisterFormProps) {
  const { register } = useClientAuth()

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

  const setSubmitting = (submitting: boolean) => {
    setLoading(submitting)
    onSubmittingChange?.(submitting)
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

    setSubmitting(true)

    try {
      const client = await register(formData, slug)
      toast.success('Conta criada com sucesso!')
      onSuccess(client)
    } catch (err) {
      // fetch rejeita com TypeError quando não há conexão com a API
      const errorMessage = err instanceof TypeError
        ? 'Não foi possível conectar. Verifique sua internet e tente novamente.'
        : (err instanceof Error && err.message) || 'Erro ao criar conta. Tente novamente.'
      setError(errorMessage)
      toast.error(errorMessage)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
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
        <PasswordInput
          id="password"
          name="password"
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
        <PasswordInput
          id="password_confirmation"
          name="password_confirmation"
          placeholder="Digite a senha novamente"
          value={formData.password_confirmation}
          onChange={handleChange}
          required
          disabled={loading}
          minLength={6}
        />
      </div>

      <div className="flex flex-col gap-4 pt-2">
        <Button type="submit" className="w-full" disabled={loading}>
          {loading ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Criando conta...
            </>
          ) : (
            <>
              <UserPlus className="mr-2 h-4 w-4" />
              {submitLabel}
            </>
          )}
        </Button>
        {children}
      </div>
    </form>
  )
}
