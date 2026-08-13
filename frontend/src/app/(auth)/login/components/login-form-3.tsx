"use client"

import { useState, useEffect } from "react"
import { useRouter, useSearchParams } from "next/navigation"
import { isPublicRoute } from "@/lib/auth-routes"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import Link from "next/link"
import { useAuth } from "@/contexts/auth-context"
import { toast } from "sonner"
import { ShoppingCart, BarChart3, Users, Utensils } from "lucide-react"
import { AlbaTecLogo } from "@/components/albatec-logo"

const loginSchema = z.object({
  email: z.string().email("Email inválido"),
  password: z.string().min(1, "Senha é obrigatória"),
})

type LoginFormData = z.infer<typeof loginSchema>

export function LoginForm3({
  className,
  ...props
}: React.ComponentProps<"div">) {
  const [isLoading, setIsLoading] = useState(false)
  const router = useRouter()
  const searchParams = useSearchParams()
  const { login } = useAuth()

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: "",
      password: "",
    },
  })

  useEffect(() => {
    const url = new URL(window.location.href)
    if (url.searchParams.has('email') || url.searchParams.has('password')) {
      window.history.replaceState({}, '', window.location.pathname)
      toast.error("⚠️ Credenciais não devem ser enviadas via URL. Use o formulário.")
    }
  }, [])

  const onSubmit = async (data: LoginFormData) => {
    setIsLoading(true)
    try {
      await login(data.email, data.password)
      toast.success("Login realizado com sucesso!")

      const redirectParam = searchParams.get("redirect")
      let destination = "/dashboard"
      if (
        redirectParam?.startsWith("/") &&
        !redirectParam.startsWith("//") &&
        !isPublicRoute(redirectParam.split("?")[0])
      ) {
        destination = redirectParam
      }
      router.push(destination)
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : "Erro ao fazer login"
      toast.error(errorMessage)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className={cn("flex flex-col gap-6", className)} {...props}>
      <Card className="overflow-hidden border shadow-xl p-0">
        <CardContent className="grid p-0 lg:grid-cols-2">
          {/* Formulário */}
          <form
            className="flex flex-col justify-center bg-card p-8 md:p-10 lg:p-12"
            method="post"
            onSubmit={handleSubmit(onSubmit)}
          >
            <div className="mx-auto w-full max-w-sm flex flex-col gap-7">
              <div className="flex flex-col items-center gap-4 text-center">
                <AlbaTecLogo href="/" variant="full" height={80} adaptive />
                <div>
                  <h1 className="text-2xl font-bold tracking-tight">Bem-vindo de volta</h1>
                  <p className="text-muted-foreground text-sm mt-1.5">
                    Acesse o painel do seu restaurante
                  </p>
                </div>
              </div>

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

              <div className="grid gap-2">
                <div className="flex items-center">
                  <Label htmlFor="password">Senha</Label>
                  <Link
                    href="/auth/forgot-password"
                    className="ml-auto text-sm text-primary underline-offset-2 hover:underline"
                  >
                    Esqueceu a senha?
                  </Link>
                </div>
                <Input
                  id="password"
                  type="password"
                  placeholder="Digite sua senha"
                  autoComplete="current-password"
                  {...register("password")}
                  className={cn("h-11", errors.password && "border-destructive")}
                />
                {errors.password && (
                  <p className="text-sm text-destructive">{errors.password.message}</p>
                )}
              </div>

              <Button
                type="submit"
                size="lg"
                className="w-full cursor-pointer h-11 text-base"
                disabled={isLoading}
              >
                {isLoading ? "Entrando..." : "Entrar"}
              </Button>

              <p className="text-center text-sm text-muted-foreground">
                Ainda não tem conta?{" "}
                <Link href="/auth/register" className="text-primary font-medium hover:underline">
                  Criar conta grátis
                </Link>
              </p>
            </div>
          </form>

          {/* Painel lateral */}
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
                  Gestão completa do seu restaurante
                </h2>
                <p className="text-white/80 text-sm leading-relaxed">
                  Pedidos, cardápio, PDV, finanças e relatórios em uma única plataforma.
                </p>
              </div>

              <div className="grid w-full grid-cols-2 gap-3">
                {[
                  { icon: ShoppingCart, label: "Pedidos" },
                  { icon: BarChart3, label: "Relatórios" },
                  { icon: Users, label: "Clientes" },
                  { icon: Utensils, label: "Cardápio" },
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
