"use client"

import { useEffect, useMemo, useState } from "react"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import Link from "next/link"
import { useRouter, useSearchParams } from "next/navigation"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { AlbaTecLogo } from "@/components/albatec-logo"
import { useAuth } from "@/contexts/auth-context"
import {
  EmailVerificationError,
  maskEmail,
  resendVerificationEmail,
  verifyEmailCode,
} from "@/lib/auth-email-verification"
import { persistAuthUser } from "@/lib/auth-storage"
import { toast } from "sonner"
import { CheckCircle2, Mail, ShieldCheck } from "lucide-react"

const verifySchema = z.object({
  code: z
    .string()
    .length(6, "O código deve ter 6 dígitos")
    .regex(/^[0-9]{6}$/, "Informe apenas números"),
})

type VerifyFormData = z.infer<typeof verifySchema>

export function VerifyEmailForm({
  className,
  ...props
}: React.ComponentProps<"div">) {
  const router = useRouter()
  const searchParams = useSearchParams()
  const { user, setUser, isAuthenticated, isLoading: authLoading } = useAuth()
  const [isLoading, setIsLoading] = useState(false)
  const [isResending, setIsResending] = useState(false)
  const [cooldown, setCooldown] = useState(0)
  const [fieldError, setFieldError] = useState<string | null>(null)
  const [done, setDone] = useState(false)

  const masked = useMemo(
    () => (user?.email ? maskEmail(user.email) : "seu e-mail"),
    [user?.email],
  )

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<VerifyFormData>({
    resolver: zodResolver(verifySchema),
    defaultValues: { code: "" },
  })

  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.replace("/auth/login?redirect=/auth/verify-email")
    }
  }, [authLoading, isAuthenticated, router])

  useEffect(() => {
    if (user?.email_verified) {
      const next = searchParams.get("next")
      router.replace(next?.startsWith("/") ? next : "/dashboard")
    }
  }, [user?.email_verified, router, searchParams])

  useEffect(() => {
    if (cooldown <= 0) return
    const t = setInterval(() => setCooldown((c) => Math.max(0, c - 1)), 1000)
    return () => clearInterval(t)
  }, [cooldown])

  const onSubmit = async (data: VerifyFormData) => {
    setIsLoading(true)
    setFieldError(null)
    try {
      const message = await verifyEmailCode(data.code)
      if (user) {
        const updated = { ...user, email_verified: true, email_verified_at: new Date().toISOString() }
        setUser(updated)
        persistAuthUser(JSON.stringify(updated))
      }
      setDone(true)
      toast.success(message)
      const next = searchParams.get("next")
      setTimeout(() => {
        router.push(next?.startsWith("/") ? next : "/dashboard")
      }, 800)
    } catch (error) {
      const err = error instanceof EmailVerificationError ? error : null
      const message =
        error instanceof Error ? error.message : "Não foi possível verificar o código"
      setFieldError(message)
      if (err?.errorCode === "expired_code" || err?.errorCode === "too_many_attempts") {
        toast.error(message)
      }
    } finally {
      setIsLoading(false)
    }
  }

  const onResend = async () => {
    if (cooldown > 0) return
    setIsResending(true)
    setFieldError(null)
    try {
      const result = await resendVerificationEmail()
      toast.success(result.message)
      setCooldown(60)
    } catch (error) {
      const err = error instanceof EmailVerificationError ? error : null
      const message =
        error instanceof Error ? error.message : "Erro ao reenviar código"
      if (err?.retryAfter) setCooldown(err.retryAfter)
      toast.error(message)
    } finally {
      setIsResending(false)
    }
  }

  if (authLoading) {
    return (
      <div className="flex min-h-[40vh] items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-b-2 border-primary" />
      </div>
    )
  }

  return (
    <div className={cn("flex flex-col gap-6", className)} {...props}>
      <Card className="overflow-hidden border shadow-xl p-0">
        <CardContent className="grid p-0 lg:grid-cols-2">
          <div className="flex flex-col justify-center bg-card p-8 md:p-10 lg:p-12">
            <div className="mx-auto w-full max-w-sm flex flex-col gap-7">
              <div className="flex flex-col items-center gap-4 text-center">
                <AlbaTecLogo href="/" variant="full" height={80} adaptive />
                <div>
                  <h1 className="text-2xl font-bold tracking-tight">
                    Confirme seu e-mail
                  </h1>
                  <p className="text-muted-foreground text-sm mt-1.5 text-balance">
                    Enviamos um código para proteger sua conta. Use o e-mail informado no cadastro.
                  </p>
                </div>
              </div>

              {done ? (
                <div className="flex flex-col items-center gap-5 text-center">
                  <div className="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <CheckCircle2 className="h-7 w-7" aria-hidden />
                  </div>
                  <p className="text-sm font-medium">E-mail confirmado. Redirecionando…</p>
                </div>
              ) : (
                <form
                  className="flex flex-col gap-5"
                  method="post"
                  onSubmit={handleSubmit(onSubmit)}
                >
                  <div className="rounded-lg border bg-muted/40 p-3 text-sm text-muted-foreground space-y-1">
                    <p className="font-medium text-foreground flex items-center gap-2 justify-center sm:justify-start">
                      <Mail className="h-4 w-4" aria-hidden />
                      Código enviado para
                    </p>
                    <p className="text-center sm:text-left break-all text-foreground">{masked}</p>
                  </div>

                  <div className="grid gap-2">
                    <Label htmlFor="code">Código de 6 dígitos</Label>
                    <Input
                      id="code"
                      inputMode="numeric"
                      autoComplete="one-time-code"
                      placeholder="000000"
                      maxLength={6}
                      className="text-center tracking-[0.4em] text-lg h-12"
                      {...register("code", {
                        onChange: (e) => {
                          const digits = e.target.value.replace(/\D/g, "").slice(0, 6)
                          setValue("code", digits, { shouldValidate: true })
                        },
                      })}
                    />
                    {(errors.code?.message || fieldError) && (
                      <p className="text-sm text-destructive" role="alert">
                        {errors.code?.message || fieldError}
                      </p>
                    )}
                  </div>

                  <Button type="submit" size="lg" className="w-full h-11" disabled={isLoading}>
                    {isLoading ? "Verificando…" : "Confirmar e-mail"}
                  </Button>

                  <div className="text-center text-sm text-muted-foreground space-y-2">
                    <p>Não recebeu o código?</p>
                    <Button
                      type="button"
                      variant="outline"
                      className="w-full"
                      disabled={isResending || cooldown > 0}
                      onClick={onResend}
                    >
                      {cooldown > 0
                        ? `Reenviar em ${cooldown}s`
                        : isResending
                          ? "Reenviando…"
                          : "Reenviar código"}
                    </Button>
                    <p>
                      E-mail errado?{" "}
                      <Link href="/auth/register" className="underline underline-offset-4">
                        Voltar ao cadastro
                      </Link>
                    </p>
                  </div>
                </form>
              )}
            </div>
          </div>

          <div className="relative hidden bg-muted lg:block">
            <div className="absolute inset-0 bg-gradient-to-br from-primary/90 via-primary to-primary/70" />
            <div className="relative z-10 flex h-full flex-col justify-between p-10 text-primary-foreground">
              <div className="space-y-4">
                <ShieldCheck className="h-10 w-10 opacity-90" aria-hidden />
                <h2 className="text-2xl font-semibold leading-tight">
                  Verificação rápida, conta mais segura
                </h2>
                <p className="text-sm text-primary-foreground/85 max-w-sm">
                  Confirmamos que você tem acesso ao e-mail usado no cadastro antes de liberar o painel.
                </p>
              </div>
              <ul className="space-y-2 text-sm text-primary-foreground/90">
                <li>• Código válido por 15 minutos</li>
                <li>• Você pode reenviar se não receber</li>
                <li>• Depois seguimos para o dashboard</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
