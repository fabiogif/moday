'use client'

import { useEffect, useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { toast } from 'sonner'
import { KeyRound, Mail, Copy, RefreshCw, User } from 'lucide-react'
import adminApi from '@/lib/admin-api-client'

export interface TenantOwner {
  id: number
  name: string
  email: string
  is_active: boolean
  last_login_at: string | null
  created_at?: string | null
}

interface OwnerAccessCardProps {
  tenantId: string | number
  owner: TenantOwner | null
  canManage: boolean
  onUpdated: () => Promise<void> | void
}

export function OwnerAccessCard({ tenantId, owner, canManage, onUpdated }: OwnerAccessCardProps) {
  const [email, setEmail] = useState(owner?.email ?? '')
  const [savingEmail, setSavingEmail] = useState(false)
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [resetting, setResetting] = useState(false)
  const [generatedPassword, setGeneratedPassword] = useState<string | null>(null)

  useEffect(() => {
    setEmail(owner?.email ?? '')
    setGeneratedPassword(null)
  }, [owner?.id, owner?.email])

  if (!owner) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-lg flex items-center gap-2">
            <User className="h-4 w-4" />
            Acesso do Dono
          </CardTitle>
          <CardDescription>
            Nenhum usuário dono encontrado para esta empresa.
          </CardDescription>
        </CardHeader>
      </Card>
    )
  }

  const handleUpdateEmail = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!canManage) return

    try {
      setSavingEmail(true)
      await adminApi.updateTenantOwnerEmail(tenantId, email.trim())
      toast.success('Email do dono atualizado com sucesso')
      await onUpdated()
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Erro ao atualizar email'
      toast.error(message)
    } finally {
      setSavingEmail(false)
    }
  }

  const handleResetPassword = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!canManage) return

    if (password && password !== passwordConfirmation) {
      toast.error('A confirmação da senha não confere')
      return
    }

    if (password && password.length < 8) {
      toast.error('A senha deve ter pelo menos 8 caracteres')
      return
    }

    try {
      setResetting(true)
      const response = await adminApi.resetTenantOwnerPassword(
        tenantId,
        password
          ? { password, password_confirmation: passwordConfirmation }
          : undefined
      )
      const temporary = response?.data?.temporary_password as string | undefined
      setGeneratedPassword(temporary ?? password ?? null)
      setPassword('')
      setPasswordConfirmation('')
      toast.success('Senha redefinida com sucesso')
      await onUpdated()
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Erro ao resetar senha'
      toast.error(message)
    } finally {
      setResetting(false)
    }
  }

  const copyPassword = async () => {
    if (!generatedPassword) return
    await navigator.clipboard.writeText(generatedPassword)
    toast.success('Senha copiada')
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg flex items-center gap-2">
          <KeyRound className="h-4 w-4" />
          Acesso do Dono
        </CardTitle>
        <CardDescription>
          Visualize e gerencie o email e a senha de login da empresa
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="flex items-start justify-between gap-4">
          <div className="min-w-0 space-y-1">
            <p className="font-medium truncate">{owner.name}</p>
            <p className="text-sm text-muted-foreground truncate flex items-center gap-1.5">
              <Mail className="h-3.5 w-3.5 shrink-0" />
              Email atual: <span className="font-medium text-foreground">{owner.email}</span>
            </p>
          </div>
          <Badge variant={owner.is_active ? 'default' : 'secondary'}>
            {owner.is_active ? 'Ativo' : 'Inativo'}
          </Badge>
        </div>

        <Separator />

        <form onSubmit={handleUpdateEmail} className="space-y-3">
          <div className="space-y-2">
            <Label htmlFor="owner-email">Alterar email</Label>
            <Input
              id="owner-email"
              type="email"
              value={email}
              disabled={!canManage || savingEmail}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="dono@empresa.com"
              required
            />
          </div>
          {canManage && (
            <Button type="submit" disabled={savingEmail || email === owner.email}>
              {savingEmail ? 'Salvando...' : 'Salvar email'}
            </Button>
          )}
        </form>

        <Separator />

        <form onSubmit={handleResetPassword} className="space-y-3">
          <div className="space-y-1">
            <Label>Reset de senha</Label>
            <p className="text-sm text-muted-foreground">
              Deixe em branco para gerar uma senha temporária automaticamente.
            </p>
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="owner-password">Nova senha (opcional)</Label>
              <Input
                id="owner-password"
                type="password"
                value={password}
                disabled={!canManage || resetting}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Mínimo 8 caracteres"
                autoComplete="new-password"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="owner-password-confirmation">Confirmar senha</Label>
              <Input
                id="owner-password-confirmation"
                type="password"
                value={passwordConfirmation}
                disabled={!canManage || resetting}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                placeholder="Repita a senha"
                autoComplete="new-password"
              />
            </div>
          </div>
          {canManage && (
            <Button type="submit" variant="destructive" disabled={resetting}>
              <RefreshCw className={`h-4 w-4 mr-2 ${resetting ? 'animate-spin' : ''}`} />
              {resetting ? 'Resetando...' : 'Resetar senha'}
            </Button>
          )}
        </form>

        {generatedPassword && (
          <Alert>
            <AlertDescription className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <span>
                Nova senha: <code className="font-mono text-sm">{generatedPassword}</code>
              </span>
              <Button type="button" size="sm" variant="outline" onClick={copyPassword}>
                <Copy className="h-4 w-4 mr-2" />
                Copiar
              </Button>
            </AlertDescription>
          </Alert>
        )}
      </CardContent>
    </Card>
  )
}
