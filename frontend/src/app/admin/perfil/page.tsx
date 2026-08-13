'use client'

import { useEffect, useState } from 'react'
import { useAdminAuth } from '@/contexts/admin-auth-context'
import adminApi from '@/lib/admin-api-client'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { useToast } from '@/hooks/use-toast'
import { Shield, User, Lock, AlertTriangle } from 'lucide-react'

const roleLabels: Record<string, string> = {
  super_admin: 'Super Admin',
  admin: 'Administrador',
  analyst: 'Analista',
}

export default function AdminPerfilPage() {
  const { admin, updateAdmin, logout } = useAdminAuth()
  const { toast } = useToast()

  const [profileForm, setProfileForm] = useState({
    name: admin?.name ?? '',
    email: admin?.email ?? '',
  })
  const [profileErrors, setProfileErrors] = useState<Record<string, string>>({})
  const [savingProfile, setSavingProfile] = useState(false)

  const [passwordForm, setPasswordForm] = useState({
    current_password: '',
    password: '',
    password_confirmation: '',
  })
  const [passwordErrors, setPasswordErrors] = useState<Record<string, string>>({})
  const [savingPassword, setSavingPassword] = useState(false)

  useEffect(() => {
    if (!admin) return
    setProfileForm({
      name: admin.name,
      email: admin.email,
    })
  }, [admin?.id, admin?.name, admin?.email])

  const parseApiErrors = (err: unknown): Record<string, string> => {
    const apiError = err as { data?: { errors?: Record<string, string[] | string> } }
    const errors = apiError?.data?.errors ?? {}
    const flat: Record<string, string> = {}
    Object.entries(errors).forEach(([key, value]) => {
      flat[key] = Array.isArray(value) ? value[0] : String(value)
    })
    return flat
  }

  const handleProfileSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setProfileErrors({})
    setSavingProfile(true)

    try {
      const response = await adminApi.updateProfile(profileForm)
      updateAdmin(response.data)
      toast({ title: 'Perfil atualizado com sucesso.' })
    } catch (err: unknown) {
      const flat = parseApiErrors(err)
      if (Object.keys(flat).length > 0) {
        setProfileErrors(flat)
      } else {
        const message = err instanceof Error ? err.message : 'Tente novamente.'
        toast({
          title: 'Erro ao atualizar perfil',
          description: message,
          variant: 'destructive',
        })
      }
    } finally {
      setSavingProfile(false)
    }
  }

  const handlePasswordSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setPasswordErrors({})
    setSavingPassword(true)

    try {
      await adminApi.updatePassword(passwordForm)
      toast({
        title: 'Senha alterada com sucesso.',
        description: 'Você será desconectado para fazer login novamente.',
      })
      setPasswordForm({ current_password: '', password: '', password_confirmation: '' })
      setTimeout(() => logout(), 2000)
    } catch (err: unknown) {
      const flat = parseApiErrors(err)
      if (Object.keys(flat).length > 0) {
        setPasswordErrors(flat)
      } else {
        const message = err instanceof Error ? err.message : 'Tente novamente.'
        toast({
          title: 'Erro ao alterar senha',
          description: message,
          variant: 'destructive',
        })
      }
    } finally {
      setSavingPassword(false)
    }
  }

  return (
    <div className="flex-1 space-y-6 px-6 pt-0 max-w-2xl">
      {/* Header */}
      <div className="flex flex-col gap-2">
        <h1 className="text-2xl font-bold tracking-tight">Meu Perfil</h1>
        <p className="text-muted-foreground">Gerencie seus dados de acesso ao painel administrativo</p>
      </div>

      {/* Info card */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="flex items-center gap-2 text-base">
            <Shield className="h-4 w-4 text-primary" />
            Informações da Conta
          </CardTitle>
        </CardHeader>
        <CardContent className="flex items-center gap-4">
          <div className="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
            <User className="w-6 h-6 text-primary" />
          </div>
          <div className="flex-1 min-w-0">
            <p className="font-medium truncate">{admin?.name}</p>
            <p className="text-sm text-muted-foreground truncate">{admin?.email}</p>
          </div>
          <Badge variant="secondary" className="shrink-0">
            {roleLabels[admin?.role ?? ''] ?? admin?.role}
          </Badge>
        </CardContent>
      </Card>

      {/* Profile form */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <User className="h-4 w-4" />
            Dados Pessoais
          </CardTitle>
          <CardDescription>Atualize seu nome e endereço de email</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleProfileSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Nome</Label>
              <Input
                id="name"
                value={profileForm.name}
                onChange={e => setProfileForm(p => ({ ...p, name: e.target.value }))}
                placeholder="Seu nome completo"
              />
              {profileErrors.name && (
                <p className="text-sm text-destructive">{profileErrors.name}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                value={profileForm.email}
                onChange={e => setProfileForm(p => ({ ...p, email: e.target.value }))}
                placeholder="seu@email.com"
              />
              {profileErrors.email && (
                <p className="text-sm text-destructive">{profileErrors.email}</p>
              )}
            </div>

            <Button type="submit" disabled={savingProfile} className="cursor-pointer">
              {savingProfile ? 'Salvando...' : 'Salvar alterações'}
            </Button>
          </form>
        </CardContent>
      </Card>

      {/* Password form */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <Lock className="h-4 w-4" />
            Alterar Senha
          </CardTitle>
          <CardDescription>Use uma senha com pelo menos 8 caracteres</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handlePasswordSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="current_password">Senha atual</Label>
              <Input
                id="current_password"
                type="password"
                value={passwordForm.current_password}
                onChange={e => setPasswordForm(p => ({ ...p, current_password: e.target.value }))}
                placeholder="••••••••"
              />
              {passwordErrors.current_password && (
                <p className="text-sm text-destructive">{passwordErrors.current_password}</p>
              )}
            </div>

            <Separator />

            <div className="space-y-2">
              <Label htmlFor="password">Nova senha</Label>
              <Input
                id="password"
                type="password"
                value={passwordForm.password}
                onChange={e => setPasswordForm(p => ({ ...p, password: e.target.value }))}
                placeholder="••••••••"
              />
              {passwordErrors.password && (
                <p className="text-sm text-destructive">{passwordErrors.password}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="password_confirmation">Confirmar nova senha</Label>
              <Input
                id="password_confirmation"
                type="password"
                value={passwordForm.password_confirmation}
                onChange={e => setPasswordForm(p => ({ ...p, password_confirmation: e.target.value }))}
                placeholder="••••••••"
              />
              {passwordErrors.password_confirmation && (
                <p className="text-sm text-destructive">{passwordErrors.password_confirmation}</p>
              )}
            </div>

            <div className="flex items-start gap-2 p-3 rounded-md bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800">
              <AlertTriangle className="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
              <p className="text-sm text-amber-700 dark:text-amber-400">
                Ao alterar a senha você será desconectado e precisará fazer login novamente.
              </p>
            </div>

            <Button type="submit" variant="destructive" disabled={savingPassword} className="cursor-pointer">
              {savingPassword ? 'Alterando...' : 'Alterar senha'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
