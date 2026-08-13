'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { AlbaTecLogo } from '@/components/albatec-logo'
import { resetPassword } from '@/lib/auth-password'
import { PASSWORD_MIN_LENGTH } from '@/lib/password-policy'
import { toast } from 'sonner'
import { Loader2 } from 'lucide-react'

const schema = z
  .object({
    password: z.string().min(PASSWORD_MIN_LENGTH, `Senha deve ter pelo menos ${PASSWORD_MIN_LENGTH} caracteres`),
    password_confirmation: z.string().min(PASSWORD_MIN_LENGTH, 'Confirme sua senha'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'As senhas não conferem',
    path: ['password_confirmation'],
  })

type FormData = z.infer<typeof schema>

type AdminResetPasswordFormProps = {
  token: string
  email: string
}

export function AdminResetPasswordForm({ token, email }: AdminResetPasswordFormProps) {
  const router = useRouter()
  const hasValidParams = Boolean(token && email)
  const [isLoading, setIsLoading] = useState(false)

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { password: '', password_confirmation: '' },
  })

  const onSubmit = async (data: FormData) => {
    if (!hasValidParams) {
      toast.error('Link de redefinição inválido ou expirado.')
      return
    }

    setIsLoading(true)
    try {
      const message = await resetPassword(
        {
          token,
          email,
          password: data.password,
          password_confirmation: data.password_confirmation,
        },
        'admin',
      )
      toast.success(message)
      router.push('/admin/login')
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Erro ao redefinir senha')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Card className="w-full max-w-md shadow-xl">
      <CardHeader className="space-y-4 text-center pb-2">
        <div className="mx-auto">
          <AlbaTecLogo variant="full" height={72} adaptive />
        </div>
        <div>
          <CardTitle className="text-2xl font-bold">Redefinir senha</CardTitle>
          <CardDescription className="mt-1.5 text-balance">
            {hasValidParams
              ? 'Crie uma nova senha para acessar o painel administrativo.'
              : 'Este link é inválido ou expirou. Solicite um novo e-mail de recuperação.'}
          </CardDescription>
        </div>
      </CardHeader>

      <CardContent>
        {!hasValidParams ? (
          <div className="space-y-4">
            <Button asChild className="w-full">
              <Link href="/admin/forgot-password">Solicitar novo link</Link>
            </Button>
            <p className="text-center text-sm text-muted-foreground">
              <Link href="/admin/login" className="text-primary font-medium hover:underline">
                Voltar para o login
              </Link>
            </p>
          </div>
        ) : (
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="email">E-mail</Label>
              <Input id="email" type="email" value={email} disabled className="bg-muted" />
            </div>

            <div className="space-y-2">
              <Label htmlFor="password">Nova senha</Label>
              <Input
                id="password"
                type="password"
                placeholder={`Mínimo de ${PASSWORD_MIN_LENGTH} caracteres`}
                autoComplete="new-password"
                disabled={isLoading}
                {...register('password')}
              />
              {errors.password && (
                <p className="text-sm text-destructive">{errors.password.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="password_confirmation">Confirmar nova senha</Label>
              <Input
                id="password_confirmation"
                type="password"
                placeholder="Repita a nova senha"
                autoComplete="new-password"
                disabled={isLoading}
                {...register('password_confirmation')}
              />
              {errors.password_confirmation && (
                <p className="text-sm text-destructive">{errors.password_confirmation.message}</p>
              )}
            </div>

            <Button type="submit" className="w-full" disabled={isLoading}>
              {isLoading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Salvando...
                </>
              ) : (
                'Redefinir senha'
              )}
            </Button>
          </form>
        )}
      </CardContent>
    </Card>
  )
}
