"use client"

import { useState } from "react"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import Link from "next/link"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { AlbaTecLogo } from "@/components/albatec-logo"
import { requestPasswordReset } from "@/lib/auth-password"
import { toast } from "sonner"
import { CheckCircle2, KeyRound, Mail, ShieldCheck } from "lucide-react"

const forgotPasswordSchema = z.object({
  email: z.string().email("E-mail inválido"),
})

type ForgotPasswordFormData = z.infer<typeof forgotPasswordSchema>

export function ForgotPasswordForm({
  className,
  ...props
}: React.ComponentProps<"div">) {
  const [isLoading, setIsLoading] = useState(false)
  const [emailSent, setEmailSent] = useState(false)
  const [sentEmail, setSentEmail] = useState("")

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ForgotPasswordFormData>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: { email: "" },
  })

  const onSubmit = async (data: ForgotPasswordFormData) => {
    setIsLoading(true)
    try {
      const message = await requestPasswordReset(data.email)
      setSentEmail(data.email)
      setEmailSent(true)
      toast.success(message)
    } catch (error) {
      const errorMessage =
        error instanceof Error ? error.message : "Erro ao enviar link de recuperação"
      toast.error(errorMessage)
    } finally {
      setIsLoading(false)
    }
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
                  <h1 className="text-2xl font-bold tracking-tight">Esqueceu sua senha?</h1>
                  <p className="text-muted-foreground text-sm mt-1.5 text-balance">
                    {emailSent
                      ? "Verifique sua caixa de entrada e siga as instruções do e-mail."
                      : "Informe o e-mail da sua conta para receber o link de redefinição."}
                  </p>
                </div>
              </div>

              {emailSent ? (
                <div className="flex flex-col items-center gap-5 text-center">
                  <div className="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <CheckCircle2 className="h-7 w-7" />
                  </div>
                  <div className="space-y-1">
                    <p className="text-sm font-medium">E-mail enviado para</p>
                    <p className="text-sm text-muted-foreground break-all">{sentEmail}</p>
                  </div>
                  <Button asChild size="lg" className="w-full h-11">
                    <Link href="/auth/login">Voltar para o login</Link>
                  </Button>
                </div>
              ) : (
                <form
                  className="flex flex-col gap-5"
                  method="post"
                  onSubmit={handleSubmit(onSubmit)}
                >
                  <div className="grid gap-2">
                    <Label htmlFor="email">E-mail</Label>
                    <Input
                      id="email"
                      type="email"
                      placeholder="seu@email.com"
                      autoComplete="email"
                      {...register("email")}
                      className={cn("h-11", errors.email && "border-destructive")}
                    />
                    {errors.email && (
                      <p className="text-sm text-destructive">{errors.email.message}</p>
                    )}
                  </div>

                  <Button
                    type="submit"
                    size="lg"
                    className="w-full cursor-pointer h-11 text-base"
                    disabled={isLoading}
                  >
                    {isLoading ? "Enviando..." : "Enviar link de redefinição"}
                  </Button>

                  <p className="text-center text-sm text-muted-foreground">
                    Lembrou sua senha?{" "}
                    <Link href="/auth/login" className="text-primary font-medium hover:underline">
                      Voltar para o login
                    </Link>
                  </p>
                </form>
              )}
            </div>
          </div>

          <div className="relative hidden lg:flex flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-primary-700 via-primary to-primary-900 p-10 text-white">
            <div
              className="absolute inset-0 opacity-15"
              style={{
                backgroundImage: "radial-gradient(circle at 2px 2px, white 1px, transparent 0)",
                backgroundSize: "28px 28px",
              }}
            />
            <div className="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
            <div className="absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-violet-300/20 blur-3xl" />

            <div className="relative z-10 flex w-full max-w-sm flex-col items-center text-center gap-8">
              <div className="flex flex-col items-center gap-2 rounded-xl border border-white/15 bg-white/10 backdrop-blur-sm p-4">
                <AlbaTecLogo variant="full" height={140} onDark />
              </div>

              <div>
                <h2 className="text-2xl font-bold mb-2 leading-tight">
                  Recupere o acesso com segurança
                </h2>
                <p className="text-white/80 text-sm leading-relaxed">
                  Enviaremos um link válido por tempo limitado para você criar uma nova senha.
                </p>
              </div>

              <div className="grid w-full grid-cols-2 gap-3">
                {[
                  { icon: Mail, label: "E-mail seguro" },
                  { icon: KeyRound, label: "Nova senha" },
                  { icon: ShieldCheck, label: "Conta protegida" },
                  { icon: CheckCircle2, label: "Acesso rápido" },
                ].map(({ icon: Icon, label }) => (
                  <div
                    key={label}
                    className="flex flex-col items-center gap-2 rounded-xl border border-white/15 bg-white/10 backdrop-blur-sm p-4"
                  >
                    <Icon className="h-5 w-5" />
                    <span className="text-xs font-medium">{label}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <p className="text-muted-foreground text-center text-xs text-balance">
        Ao continuar, você concorda com nossos{" "}
        <a href="#" className="underline underline-offset-2 hover:text-primary">Termos de Serviço</a>
        {" "}e{" "}
        <a href="#" className="underline underline-offset-2 hover:text-primary">Política de Privacidade</a>.
      </p>
    </div>
  )
}
