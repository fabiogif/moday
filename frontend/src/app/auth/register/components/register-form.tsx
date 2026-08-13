"use client"

import { useState, useEffect } from "react"
import { useRouter } from "next/navigation"
import { zodResolver } from "@hookform/resolvers/zod"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { cn } from "@/lib/utils"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Checkbox } from "@/components/ui/checkbox"
import { useToast } from "@/hooks/use-toast"
import { Loader2, Check } from "lucide-react"
import { apiClient } from "@/lib/api-client"
import { useAuth } from "@/contexts/auth-context"
import { maskCNPJ, validateCNPJ } from "@/lib/masks"
import { PASSWORD_MIN_LENGTH } from "@/lib/password-policy"
import { TRIAL_DAYS } from "@/lib/subscription"
import type { TrialStatus } from "@/contexts/auth-context"

const registerFormSchema = z.object({
  company_name: z.string().min(3, "Nome da empresa deve ter pelo menos 3 caracteres"),
  company_email: z.string().email("Email inválido").optional().or(z.literal("")),
  company_cnpj: z.string().min(1, "CNPJ é obrigatório").refine((value) => validateCNPJ(value), {
    message: "CNPJ inválido. Verifique os dígitos.",
  }),
  name: z.string().min(2, "Nome deve ter pelo menos 2 caracteres"),
  email: z.string().email("Email inválido"),
  password: z.string().min(PASSWORD_MIN_LENGTH, `Senha deve ter pelo menos ${PASSWORD_MIN_LENGTH} caracteres`),
  password_confirmation: z.string().min(PASSWORD_MIN_LENGTH, "Confirme sua senha"),
  phone: z.string().optional(),
  plan_id: z.string().min(1, "Selecione um plano"),
  terms: z.boolean().refine(val => val === true, "Você deve aceitar os termos"),
}).refine((data) => data.password === data.password_confirmation, {
  message: "As senhas não conferem",
  path: ["password_confirmation"],
})

type RegisterFormValues = z.infer<typeof registerFormSchema>

interface Plan {
  id: number
  name: string
  price: number | string  // Pode vir como string do backend
  description: string
  details: {
    id: number
    name: string
  }[]
}

export function RegisterForm({
  className,
  preSelectedPlanId,
  onSuccess,
  ...props
}: React.ComponentProps<"div"> & {
  preSelectedPlanId?: string
  onSuccess?: () => void
}) {
  const router = useRouter()
  const { toast } = useToast()
  const { setUser, setToken, setTrialStatus } = useAuth()
  const [isLoading, setIsLoading] = useState(false)
  const [plans, setPlans] = useState<Plan[]>([])
  const [loadingPlans, setLoadingPlans] = useState(true)

  const form = useForm<RegisterFormValues>({
    resolver: zodResolver(registerFormSchema),
    defaultValues: {
      company_name: "",
      company_email: "",
      company_cnpj: "",
      name: "",
      email: "",
      password: "",
      password_confirmation: "",
      phone: "",
      plan_id: preSelectedPlanId || "",  // Usa o plano pré-selecionado
      terms: false,
    },
  })

  // Carregar planos disponíveis
  useEffect(() => {
    async function fetchPlans() {
      try {
        const response = await apiClient.get<Plan[]>('/api/plans')
        if (response.success && response.data) {
          // Garantir que price seja número
          const plansWithNumberPrice = response.data.map(plan => ({
            ...plan,
            price: typeof plan.price === 'string' ? parseFloat(plan.price) : plan.price
          }))
          setPlans(plansWithNumberPrice)
        }
      } catch (error) {

        toast({
          title: "Erro",
          description: "Não foi possível carregar os planos. Tente novamente.",
          variant: "destructive",
        })
      } finally {
        setLoadingPlans(false)
      }
    }

    fetchPlans()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  // Atualizar plano quando a lista de planos carregar e houver um plano pré-selecionado
  useEffect(() => {
    if (preSelectedPlanId && plans.length > 0) {
      // Verificar se o plano existe
      const planExists = plans.some(p => p.id.toString() === preSelectedPlanId)
      if (planExists) {
        form.setValue('plan_id', preSelectedPlanId)
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [plans, preSelectedPlanId])

  async function onSubmit(data: RegisterFormValues) {
    setIsLoading(true)

    try {
      interface RegisterResponse {
        user: {
          id: number
          name: string
          email: string
          is_active: boolean
          tenant: {
            id: number
            uuid: string
            name: string
            slug: string
          }
        }
        token: string
        expires_in: number
        trial_status?: TrialStatus
      }

      const response = await apiClient.post<RegisterResponse>('/api/register', data)

      if (response.success && response.data) {
        // Salvar token e dados do usuário
        const { token, user } = response.data
        
        if (token) {
          setToken(token)
          setUser({
            id: String(user.id),
            name: user.name,
            email: user.email,
            tenant_id: user.tenant?.id ? String(user.tenant.id) : undefined,
            tenant: user.tenant
              ? { uuid: user.tenant.uuid, name: user.tenant.name }
              : undefined,
          })

          if (response.data.trial_status) {
            setTrialStatus(response.data.trial_status)
          }

          toast({
            title: "Cadastro realizado!",
            description: "Bem-vindo ao Alba Tec. Redirecionando...",
          })

          onSuccess?.()

          setTimeout(() => {
            router.push('/dashboard')
          }, 1000)
        }
      }
    } catch (error: any) {

      const errorMessage = error.message || "Erro ao realizar cadastro. Tente novamente."
      
      toast({
        title: "Erro no cadastro",
        description: errorMessage,
        variant: "destructive",
      })

      // Se houver erros de validação, mostrar nos campos
      if (error.errors) {
        Object.keys(error.errors).forEach((key) => {
          form.setError(key as any, {
            type: "manual",
            message: error.errors[key][0],
          })
        })
      }
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className={cn("flex flex-col gap-6", className)} {...props}>
      <Card>
        <CardHeader className="text-center">
          <CardTitle className="text-xl">Criar sua conta</CardTitle>
          <CardDescription>
            Preencha os dados abaixo para começar a usar o Alba Tec
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)}>
              <div className="grid gap-6">
                {/* Dados da Empresa */}
                <div className="space-y-2">
                  <h3 className="text-sm font-medium">Dados da Empresa</h3>
                  <div className="grid gap-4">
                    <FormField
                      control={form.control}
                      name="company_name"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Nome da Empresa *</FormLabel>
                          <FormControl>
                            <Input placeholder="Restaurante ABC" {...field} />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                      <FormField
                        control={form.control}
                        name="company_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Email da Empresa</FormLabel>
                            <FormControl>
                              <Input
                                type="email"
                                placeholder="contato@empresa.com"
                                {...field}
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                      <FormField
                        control={form.control}
                        name="company_cnpj"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>CNPJ *</FormLabel>
                            <FormControl>
                              <Input
                                placeholder="00.000.000/0000-00"
                                value={field.value}
                                name={field.name}
                                onBlur={field.onBlur}
                                ref={field.ref}
                                onChange={(e) => field.onChange(maskCNPJ(e.target.value))}
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                  </div>
                </div>

                {/* Dados do Usuário */}
                <div className="space-y-2">
                  <h3 className="text-sm font-medium">Seus Dados</h3>
                  <div className="grid gap-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                      <FormField
                        control={form.control}
                        name="name"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Nome Completo *</FormLabel>
                            <FormControl>
                              <Input placeholder="João Silva" {...field} />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                      <FormField
                        control={form.control}
                        name="phone"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Telefone</FormLabel>
                            <FormControl>
                              <Input placeholder="(00) 00000-0000" {...field} />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                    <FormField
                      control={form.control}
                      name="email"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Seu Email *</FormLabel>
                          <FormControl>
                            <Input
                              type="email"
                              placeholder="seu@email.com"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                      <FormField
                        control={form.control}
                        name="password"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Senha *</FormLabel>
                            <FormControl>
                              <Input type="password" {...field} />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                      <FormField
                        control={form.control}
                        name="password_confirmation"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Confirmar Senha *</FormLabel>
                            <FormControl>
                              <Input type="password" {...field} />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                  </div>
                </div>

                {/* Seleção de Plano */}
                <div className="space-y-2">
                  <h3 className="text-sm font-medium">Escolha seu Plano</h3>
                  <FormField
                    control={form.control}
                    name="plan_id"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Plano *</FormLabel>
                        <Select
                          onValueChange={field.onChange}
                          defaultValue={field.value}
                          disabled={loadingPlans}
                        >
                          <FormControl>
                            <SelectTrigger>
                              <SelectValue placeholder={loadingPlans ? "Carregando planos..." : "Selecione um plano"} />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            {plans.map((plan) => (
                              <SelectItem key={plan.id} value={plan.id.toString()}>
                                {plan.name} - R$ {Number(plan.price).toFixed(2)}/mês
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                        <FormMessage />
                        {form.watch("plan_id") && plans.length > 0 && (
                          <div className="mt-2 p-3 bg-muted rounded-md text-sm">
                            {(() => {
                              const selectedPlan = plans.find(p => p.id.toString() === form.watch("plan_id"))
                              if (!selectedPlan) return null
                              const isFreePlan = Number(selectedPlan.price) <= 0
                              return (
                                <div>
                                  <p className="font-medium mb-2">{selectedPlan.name}</p>
                                  {!isFreePlan && (
                                    <p className="text-emerald-600 dark:text-emerald-400 text-xs font-medium mb-2">
                                      Você terá {TRIAL_DAYS} dias para testar todos os recursos deste plano, sem cobrança.
                                    </p>
                                  )}
                                  {isFreePlan && (
                                    <p className="text-muted-foreground text-xs font-medium mb-2">
                                      Plano gratuito permanente, sem limite de tempo.
                                    </p>
                                  )}
                                  <p className="text-muted-foreground text-xs mb-2">
                                    {selectedPlan.description}
                                  </p>
                                  <ul className="space-y-1">
                                    {selectedPlan.details?.slice(0, 4).map((detail) => (
                                      <li key={detail.id} className="flex items-start gap-2 text-xs">
                                        <Check className="h-3 w-3 mt-0.5 text-primary flex-shrink-0" />
                                        <span>{detail.name}</span>
                                      </li>
                                    ))}
                                    {selectedPlan.details?.length > 4 && (
                                      <li className="text-xs text-muted-foreground">
                                        + {selectedPlan.details.length - 4} benefícios adicionais
                                      </li>
                                    )}
                                  </ul>
                                </div>
                              )
                            })()}
                          </div>
                        )}
                      </FormItem>
                    )}
                  />
                </div>

                {/* Termos */}
                <FormField
                  control={form.control}
                  name="terms"
                  render={({ field }) => (
                    <FormItem className="flex flex-row items-start space-x-3 space-y-0">
                      <FormControl>
                        <Checkbox
                          checked={field.value}
                          onCheckedChange={field.onChange}
                        />
                      </FormControl>
                      <div className="space-y-1 leading-none">
                        <FormLabel className="text-sm font-normal">
                          Aceito os{" "}
                          <a href="#" className="underline underline-offset-4 hover:text-primary">
                            termos de serviço
                          </a>{" "}
                          e{" "}
                          <a href="#" className="underline underline-offset-4 hover:text-primary">
                            política de privacidade
                          </a>
                        </FormLabel>
                        <FormMessage />
                      </div>
                    </FormItem>
                  )}
                />

                <Button type="submit" className="w-full cursor-pointer" disabled={isLoading}>
                  {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                  {isLoading ? "Criando conta..." : "Criar conta"}
                </Button>

                <div className="text-center text-sm">
                  Já tem uma conta?{" "}
                  <Link href="/auth/login" className="underline underline-offset-4 hover:text-primary">
                    Fazer login
                  </Link>
                </div>
              </div>
            </form>
          </Form>
        </CardContent>
      </Card>
    </div>
  )
}
