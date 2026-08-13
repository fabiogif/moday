'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { AlbaTecLogo } from '@/components/albatec-logo'
import { requestPasswordReset } from '@/lib/auth-password'
import { toast } from 'sonner'
import { CheckCircle2, Loader2 } from 'lucide-react'

const schema = z.object({
  email: z.string().email('E-mail inválido'),
})

type FormData = z.infer<typeof schema>

export default function AdminForgotPasswordPage() {
  const [isLoading, setIsLoading] = useState(false)
  const [emailSent, setEmailSent] = useState(false)
  const [sentEmail, setSentEmail] = useState('')

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { email: '' },
  })

  const onSubmit = async (data: FormData) => {
    setIsLoading(true)
    try {
      const message = await requestPasswordReset(data.email, 'admin')
      setSentEmail(data.email)
      setEmailSent(true)
      toast.success(message)
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Erro ao enviar link de recuperação')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900 p-4">
      <Card className="w-full max-w-md shadow-xl">
        <CardHeader className="space-y-4 text-center pb-2">
          <div className="mx-auto">
            <AlbaTecLogo variant="full" height={72} adaptive />
          </div>
          <div>
            <CardTitle className="text-2xl font-bold">Recuperar senha</CardTitle>
            <CardDescription className="mt-1.5 text-balance">
              {emailSent
                ? 'Verifique sua caixa de entrada e siga as instruções do e-mail.'
                : 'Informe o e-mail da sua conta administrativa.'}
            </CardDescription>
          </div>
        </CardHeader>

        <CardContent>
          {emailSent ? (
            <div className="flex flex-col items-center gap-5 text-center">
              <div className="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                <CheckCircle2 className="h-7 w-7" />
              </div>
              <p className="text-sm text-muted-foreground break-all">{sentEmail}</p>
              <Button asChild className="w-full">
                <Link href="/admin/login">Voltar para o login</Link>
              </Button>
            </div>
          ) : (
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email">E-mail</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="admin@albtec.app"
                  autoComplete="email"
                  disabled={isLoading}
                  {...register('email')}
                />
                {errors.email && (
                  <p className="text-sm text-destructive">{errors.email.message}</p>
                )}
              </div>

              <Button type="submit" className="w-full" disabled={isLoading}>
                {isLoading ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Enviando...
                  </>
                ) : (
                  'Enviar link de redefinição'
                )}
              </Button>

              <p className="text-center text-sm text-muted-foreground">
                <Link href="/admin/login" className="text-primary font-medium hover:underline">
                  Voltar para o login
                </Link>
              </p>
            </form>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
