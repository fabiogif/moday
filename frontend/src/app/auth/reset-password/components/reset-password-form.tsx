"use client"

import { useState } from "react"
import { useRouter } from "next/navigation"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import { PASSWORD_MIN_LENGTH } from "@/lib/password-policy"
import Link from "next/link"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { AlbaTecLogo } from "@/components/albatec-logo"
import { resetPassword } from "@/lib/auth-password"
import { toast } from "sonner"
import { KeyRound, Lock, ShieldCheck, CheckCircle2 } from "lucide-react"

const resetPasswordSchema = z
  .object({
    password: z.string().min(PASSWORD_MIN_LENGTH, `Senha deve ter pelo menos ${PASSWORD_MIN_LENGTH} caracteres`),
    password_confirmation: z.string().min(PASSWORD_MIN_LENGTH, "Confirme sua senha"),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "As senhas não conferem",
    path: ["password_confirmation"],
  })

type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>

type ResetPasswordFormProps = React.ComponentProps<"div"> & {
  token: string
  email: string
}

export function ResetPasswordForm({
  token,
  email,
  className,
  ...props
}: ResetPasswordFormProps) {
  const router = useRouter()
  const [isLoading, setIsLoading] = useState(false)
  const hasValidParams = Boolean(token && email)

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ResetPasswordFormData>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      password: "",
      password_confirmation: "",
    },
  })

  const onSubmit = async (data: ResetPasswordFormData) => {
    if (!hasValidParams) {
      toast.error("Link de redefinição inválido ou expirado.")
      return
    }

    setIsLoading(true)
    try {
      const message = await resetPassword({
        token,
        email,
        password: data.password,
        password_confirmation: data.password_confirmation,
      })
      toast.success(message)
      router.push("/auth/login")
    } catch (error) {
      const errorMessage =
        error instanceof Error ? error.message : "Erro ao redefinir senha"
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
                  <h1 className="text-2xl font-bold tracking-tight">Redefinir senha</h1>
                  <p className="text-muted-foreground text-sm mt-1.5 text-balance">
                    {hasValidParams
                      ? "Crie uma nova senha para acessar sua conta."
                      : "Este link é inválido ou expirou. Solicite um novo e-mail de recuperação."}
                  </p>
                </div>
              </div>

              {!hasValidParams ? (
                <div className="flex flex-col gap-4">
                  <Button asChild size="lg" className="w-full h-11">
                    <Link href="/auth/forgot-password">Solicitar novo link</Link>
                  </Button>
                  <p className="text-center text-sm text-muted-foreground">
                    <Link href="/auth/login" className="text-primary font-medium hover:underline">
                      Voltar para o login
                    </Link>
                  </p>
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
                      value={email}
                      disabled
                      className="h-11 bg-muted"
                    />
                  </div>

                  <div className="grid gap-2">
                    <Label htmlFor="password">Nova senha</Label>
                    <Input
                      id="password"
                      type="password"
                      placeholder="Mínimo de 8 caracteres"
                      autoComplete="new-password"
                      {...register("password")}
                      className={cn("h-11", errors.password && "border-destructive")}
                    />
                    {errors.password && (
                      <p className="text-sm text-destructive">{errors.password.message}</p>
                    )}
                  </div>

                  <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Confirmar nova senha</Label>
                    <Input
                      id="password_confirmation"
                      type="password"
                      placeholder="Repita a nova senha"
                      autoComplete="new-password"
                      {...register("password_confirmation")}
                      className={cn("h-11", errors.password_confirmation && "border-destructive")}
                    />
                    {errors.password_confirmation && (
                      <p className="text-sm text-destructive">
                        {errors.password_confirmation.message}
                      </p>
                    )}
                  </div>

                  <Button
                    type="submit"
                    size="lg"
                    className="w-full cursor-pointer h-11 text-base"
                    disabled={isLoading}
                  >
                    {isLoading ? "Salvando..." : "Redefinir senha"}
                  </Button>

                  <p className="text-center text-sm text-muted-foreground">
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
                  Defina uma senha forte
                </h2>
                <p className="text-white/80 text-sm leading-relaxed">
                  Use pelo menos 8 caracteres e evite reutilizar senhas de outros serviços.
                </p>
              </div>

              <div className="grid w-full grid-cols-2 gap-3">
                {[
                  { icon: Lock, label: "Senha segura" },
                  { icon: KeyRound, label: "Acesso rápido" },
                  { icon: ShieldCheck, label: "Conta protegida" },
                  { icon: CheckCircle2, label: "Pronto para usar" },
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
