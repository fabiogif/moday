"use client"

import { useEffect, useState, useCallback, Suspense } from "react"
import Link from "next/link"
import { useRouter, useSearchParams } from "next/navigation"
import {
  CheckCircle,
  Loader2,
  ArrowLeft,
  Shield,
  Zap,
  Users,
  Clock,
  CreditCard,
  Lock,
  Headphones,
  Package,
} from "lucide-react"
import { apiClient, endpoints } from "@/lib/api-client"
import { useMercadoPagoBrick } from "@/hooks/use-mercadopago-brick"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { showErrorToast, showSuccessToast } from "@/components/ui/error-toast"
import { useAuth } from "@/contexts/auth-context"
import { PlanFeaturesList } from "@/components/plan-features-list"
import { cn } from "@/lib/utils"
import { SubscribeShell } from "./components/subscribe-shell"
import type { PublicPlanFeatures } from "@/lib/plan-features"

interface Plan extends PublicPlanFeatures {
  id: number
  name: string
  price: string | number
  description: string | null
}

type Step = "plans" | "payment" | "success"

const trustItems = [
  { icon: CreditCard, label: "Mercado Pago" },
  { icon: Lock, label: "Dados protegidos" },
  { icon: Headphones, label: "Suporte em PT" },
  { icon: Shield, label: "Conexão segura" },
]

function formatPrice(price: string | number) {
  return Number(price).toFixed(2).replace(".", ",")
}

function isFreePlan(plan: Plan) {
  return Number(plan.price) <= 0
}

function TrustStrip() {
  return (
    <div className="flex flex-wrap items-center justify-center gap-4 sm:gap-8 py-4 border-y border-zinc-200 bg-white">
      {trustItems.map(({ icon: Icon, label }) => (
        <div key={label} className="flex items-center gap-2 text-xs sm:text-sm text-zinc-500">
          <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-zinc-100">
            <Icon className="h-3.5 w-3.5 text-zinc-600" aria-hidden />
          </div>
          <span className="font-medium">{label}</span>
        </div>
      ))}
    </div>
  )
}

function PaymentStep({
  plan,
  onBack,
  onSuccess,
}: {
  plan: Plan
  onBack: () => void
  onSuccess: () => void
}) {
  const [submitting, setSubmitting] = useState(false)
  const [activating, setActivating] = useState(false)
  const [brickReady, setBrickReady] = useState(false)
  const [brickError, setBrickError] = useState<string | null>(null)
  const free = isFreePlan(plan)

  const handleSubmit = useCallback(
    async (formData: any, additionalData: any) => {
      setSubmitting(true)
      try {
        const userRaw = typeof window !== "undefined" ? localStorage.getItem("auth-user") : null
        const user = userRaw ? JSON.parse(userRaw) : null

        await apiClient.post(endpoints.subscription.payment, {
          plan_id: plan.id,
          token: formData.token,
          payment_method_id: formData.payment_method_id,
          payment_type_id: additionalData?.paymentTypeId ?? "credit_card",
          transaction_amount: formData.transaction_amount,
          installments: formData.installments ?? 1,
          payer_email: formData.payer?.email ?? user?.email ?? "",
        })

        showSuccessToast("Pagamento aprovado! Sua assinatura está ativa.")
        onSuccess()
      } catch (err: any) {
        showErrorToast(err?.message ?? "Pagamento recusado. Verifique os dados do cartão.")
        throw err
      } finally {
        setSubmitting(false)
      }
    },
    [plan.id, onSuccess]
  )

  useMercadoPagoBrick(
    free ? "" : (process.env.NEXT_PUBLIC_MERCADOPAGO_PUBLIC_KEY || ""),
    { amount: Number(plan.price), containerId: "mp-card-payment-brick" },
    {
      onSubmit: handleSubmit,
      onReady: () => setBrickReady(true),
      onError: (err) => {
        const causes = err?.cause ?? []
        const detail =
          Array.isArray(causes) && causes.length > 0
            ? causes.map((c: any) => `${c.code ?? ""} ${c.description ?? ""}`.trim()).join("; ")
            : (err?.message ?? JSON.stringify(err))
        setBrickError(detail || "unknown")
      },
    }
  )

  const handleActivateFree = async () => {
    setActivating(true)
    try {
      await apiClient.post(endpoints.subscription.activate, {
        plan_id: plan.id,
        payment_method: "free",
      })
      showSuccessToast("Plano gratuito ativado com sucesso.")
      onSuccess()
    } catch (err: any) {
      showErrorToast(err?.message ?? "Não foi possível ativar o plano gratuito.")
    } finally {
      setActivating(false)
    }
  }

  return (
    <div className="px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
      <div className="max-w-5xl mx-auto space-y-6">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <button
            type="button"
            onClick={onBack}
            className="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-900 transition-colors w-fit"
          >
            <ArrowLeft className="h-4 w-4" />
            Voltar para os planos
          </button>
          <Link
            href="/landing"
            className="text-sm text-zinc-500 hover:text-zinc-900 transition-colors w-fit"
          >
            Ir para a página inicial
          </Link>
        </div>

        <div className="text-center sm:text-left space-y-2">
          <p className="text-[11px] uppercase tracking-[0.22em] text-primary font-medium">
            Checkout
          </p>
          <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-zinc-900">
            Finalizar assinatura
          </h1>
          <p className="text-zinc-500 text-sm sm:text-base">
            Plano <strong className="text-zinc-800">{plan.name}</strong>
            {!free && (
              <>
                {" "}
                — <strong className="text-zinc-800">R$ {formatPrice(plan.price)}/mês</strong>
              </>
            )}
          </p>
        </div>

        <div className="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] items-start">
          <aside className="rounded-2xl border border-zinc-200 bg-white p-5 sm:p-6 space-y-5 order-2 lg:order-1">
            <div>
              <h2 className="text-lg font-bold text-zinc-900">{plan.name}</h2>
              {plan.description && (
                <p className="text-sm text-zinc-500 mt-1">{plan.description}</p>
              )}
            </div>
            <div className="flex items-end gap-1 border-b border-zinc-100 pb-4">
              {free ? (
                <span className="text-3xl font-bold text-zinc-900">Grátis</span>
              ) : (
                <>
                  <span className="text-sm text-zinc-500 self-start mt-1.5">R$</span>
                  <span className="text-3xl font-bold tabular-nums text-zinc-900">
                    {formatPrice(plan.price)}
                  </span>
                  <span className="text-sm text-zinc-500 mb-1">/mês</span>
                </>
              )}
            </div>
            <PlanFeaturesList plan={plan} highlight />
            <div className="flex items-start gap-2 rounded-xl bg-emerald-50 border border-emerald-100 p-3 text-xs text-emerald-800">
              <Shield className="h-4 w-4 shrink-0 mt-0.5" />
              <span>
                {free
                  ? "Sem cartão de crédito. Você pode fazer upgrade para um plano pago a qualquer momento."
                  : "Pagamento processado com segurança pelo Mercado Pago. Você pode cancelar quando quiser."}
              </span>
            </div>
          </aside>

          <section className="rounded-2xl border border-zinc-200 bg-white p-5 sm:p-6 space-y-4 order-1 lg:order-2">
            <div className="flex items-start gap-2">
              <Shield className="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" />
              <div>
                <h3 className="font-semibold text-zinc-900">
                  {free ? "Ativar plano gratuito" : "Pagamento seguro"}
                </h3>
                <p className="text-xs text-zinc-500 mt-0.5">
                  {free
                    ? "Este plano não exige cartão de crédito."
                    : "Seus dados são criptografados pelo Mercado Pago."}
                </p>
              </div>
            </div>

            {free ? (
              <div className="space-y-4 py-2">
                <p className="text-sm text-zinc-600 leading-relaxed">
                  Confirme para ativar o plano <strong>{plan.name}</strong> na sua conta. Você
                  poderá fazer upgrade a qualquer momento.
                </p>
                <div className="flex flex-col sm:flex-row gap-2">
                  <Button
                    className="w-full sm:flex-1 rounded-md bg-zinc-900 text-white hover:bg-zinc-700"
                    onClick={handleActivateFree}
                    disabled={activating}
                  >
                    {activating ? (
                      <>
                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                        Ativando…
                      </>
                    ) : (
                      "Confirmar plano gratuito"
                    )}
                  </Button>
                  <Button variant="outline" className="w-full sm:w-auto" onClick={onBack}>
                    Escolher outro plano
                  </Button>
                </div>
              </div>
            ) : (
              <>
                {brickError && (
                  <div className="py-4 space-y-3">
                    <p className="text-sm text-destructive font-medium text-center">
                      Não foi possível carregar o formulário de pagamento.
                    </p>
                    {brickError !== "unknown" && (
                      <p className="text-xs text-zinc-500 bg-zinc-50 rounded-lg p-3 font-mono break-all">
                        {brickError}
                      </p>
                    )}
                    <p className="text-xs text-zinc-500 text-center leading-relaxed">
                      Se o erro mencionar &quot;domain&quot; ou &quot;origin&quot;, adicione{" "}
                      <strong>rest.albatec.com.br</strong> nos domínios permitidos no painel do
                      Mercado Pago.
                    </p>
                    <div className="flex flex-col sm:flex-row justify-center gap-2">
                      <Button variant="outline" size="sm" onClick={onBack}>
                        Voltar e tentar novamente
                      </Button>
                      <Button asChild variant="ghost" size="sm">
                        <Link href="/landing#contact">Falar com suporte</Link>
                      </Button>
                    </div>
                  </div>
                )}

                {!brickReady && !submitting && brickError === null && (
                  <div className="flex flex-col items-center gap-3 py-10">
                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                    <p className="text-xs text-zinc-500">Carregando formulário seguro…</p>
                  </div>
                )}

                {submitting && (
                  <div className="flex flex-col items-center gap-3 py-10">
                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                    <p className="text-sm text-zinc-500">Processando pagamento…</p>
                  </div>
                )}

                <div
                  id="mp-card-payment-brick"
                  className={submitting || brickError !== null ? "hidden" : undefined}
                />
              </>
            )}
          </section>
        </div>
      </div>
    </div>
  )
}

function PlansStep({
  onSelect,
  highlightedPlanId,
}: {
  onSelect: (plan: Plan) => void
  highlightedPlanId?: number | null
}) {
  const [plans, setPlans] = useState<Plan[]>([])
  const [loading, setLoading] = useState(true)
  const { trialStatus, isAuthenticated } = useAuth()

  useEffect(() => {
    apiClient
      .get<Plan[]>(endpoints.subscription.plans)
      .then((res) => {
        const data = res.data
        const list = Array.isArray(data) ? data : (data as any)?.data
        setPlans(Array.isArray(list) ? list : [])
      })
      .catch(() => showErrorToast("Erro ao carregar planos"))
      .finally(() => setLoading(false))
  }, [])

  const isExpired = trialStatus?.is_expired || trialStatus?.needs_payment
  const daysRemaining = trialStatus?.days_remaining ?? 0

  return (
    <div>
      <TrustStrip />

      <div className="px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div className="max-w-6xl mx-auto space-y-10">
          <div className="text-center space-y-4 max-w-2xl mx-auto">
            {isExpired ? (
              <Badge variant="destructive" className="text-xs px-3 py-1">
                Período de teste encerrado
              </Badge>
            ) : (
              <Badge
                variant="outline"
                className="text-xs px-3 py-1 border-primary/40 text-primary"
              >
                <Clock className="h-3 w-3 mr-1" />
                {daysRemaining > 0
                  ? `${daysRemaining} dias restantes no trial`
                  : "Trial ativo"}
              </Badge>
            )}

            <p className="text-[11px] uppercase tracking-[0.22em] text-orange-600 font-medium">
              Planos Alba Tec
            </p>
            <h1 className="text-3xl sm:text-4xl font-bold tracking-tight text-zinc-900">
              Escolha o plano ideal para o seu restaurante
            </h1>
            <p className="text-zinc-500 text-base sm:text-lg leading-relaxed">
              {isExpired
                ? "Seu período de teste gratuito expirou. Selecione um plano para continuar usando o Alba Tec sem interrupções."
                : "Assine agora e garanta acesso contínuo. Cancele quando quiser — pagamento seguro via Mercado Pago."}
            </p>

            <div className="flex flex-wrap items-center justify-center gap-2 pt-1">
              <Button asChild variant="outline" size="sm" className="rounded-md">
                <Link href="/landing">
                  <ArrowLeft className="h-4 w-4 mr-1.5" />
                  Voltar à landing
                </Link>
              </Button>
              {isAuthenticated && (
                <Button asChild variant="ghost" size="sm">
                  <Link href="/dashboard">Ir para o painel</Link>
                </Button>
              )}
            </div>
          </div>

          {loading ? (
            <div className="flex flex-col items-center gap-3 py-16">
              <Loader2 className="h-8 w-8 animate-spin text-primary" />
              <p className="text-sm text-zinc-500">Carregando planos…</p>
            </div>
          ) : plans.length === 0 ? (
            <div className="rounded-2xl border border-zinc-200 bg-white p-8 text-center space-y-4 max-w-md mx-auto">
              <Package className="h-10 w-10 text-zinc-300 mx-auto" />
              <p className="text-zinc-600">Nenhum plano disponível no momento.</p>
              <Button asChild variant="outline">
                <Link href="/landing#contact">Falar com a equipe</Link>
              </Button>
            </div>
          ) : (
            <div
              className={cn(
                "grid gap-4 sm:gap-5",
                plans.length === 1 && "max-w-md mx-auto",
                plans.length === 2 && "grid-cols-1 md:grid-cols-2 max-w-3xl mx-auto",
                plans.length >= 3 && "grid-cols-1 md:grid-cols-2 xl:grid-cols-3"
              )}
            >
              {plans.map((plan, idx) => {
                const popular = idx === Math.floor(plans.length / 2) && plans.length > 1
                const free = isFreePlan(plan)
                const preselected = highlightedPlanId != null && Number(plan.id) === Number(highlightedPlanId)

                return (
                  <article
                    key={plan.id}
                    className={cn(
                      "relative flex flex-col rounded-2xl border bg-white p-5 sm:p-6 transition-shadow hover:shadow-md",
                      popular || preselected
                        ? "border-zinc-900 ring-1 ring-zinc-900 shadow-sm"
                        : "border-zinc-200"
                    )}
                  >
                    {(popular || preselected) && (
                      <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span className="inline-flex items-center gap-1 rounded-full bg-white border border-zinc-200 px-3 py-1 text-xs font-semibold text-zinc-900 shadow-sm">
                          <Zap className="h-3 w-3" />
                          {preselected ? "Selecionado" : "Mais popular"}
                        </span>
                      </div>
                    )}

                    <div className="mb-5">
                      <h2 className="text-lg font-bold text-zinc-900 tracking-tight">{plan.name}</h2>
                      <p className="text-sm text-zinc-500 mt-1">
                        {plan.description || "Plano completo para seu negócio"}
                      </p>
                    </div>

                    <div className="mb-5">
                      <div className="flex items-end gap-1">
                        {free ? (
                          <span className="text-3xl font-bold text-zinc-900">Grátis</span>
                        ) : (
                          <>
                            <span className="text-sm text-zinc-500 self-start mt-1.5">R$</span>
                            <span className="text-3xl font-bold tabular-nums text-zinc-900">
                              {formatPrice(plan.price)}
                            </span>
                            <span className="text-sm text-zinc-500 mb-1">/mês</span>
                          </>
                        )}
                      </div>
                    </div>

                    <ul className="space-y-2 text-sm text-zinc-600 mb-5">
                      {plan.max_users != null && (
                        <li className="flex items-center gap-2">
                          <Users className="h-4 w-4 text-primary shrink-0" />
                          Até {plan.max_users} usuários
                        </li>
                      )}
                      {plan.max_orders_per_month != null && (
                        <li className="flex items-center gap-2">
                          <Zap className="h-4 w-4 text-primary shrink-0" />
                          Até {plan.max_orders_per_month} pedidos/mês
                        </li>
                      )}
                      {plan.has_reports && (
                        <li className="flex items-center gap-2">
                          <CheckCircle className="h-4 w-4 text-emerald-500 shrink-0" />
                          Relatórios avançados
                        </li>
                      )}
                      {plan.has_marketing && (
                        <li className="flex items-center gap-2">
                          <CheckCircle className="h-4 w-4 text-emerald-500 shrink-0" />
                          Módulo marketing
                        </li>
                      )}
                    </ul>

                    <div className="mt-auto space-y-3">
                      <Button
                        className={cn(
                          "w-full rounded-md h-11 font-semibold",
                          popular || preselected
                            ? "bg-zinc-900 text-white hover:bg-zinc-700"
                            : "border-zinc-300 text-zinc-700 hover:bg-zinc-50"
                        )}
                        variant={popular || preselected ? "default" : "outline"}
                        onClick={() => onSelect(plan)}
                      >
                        {free ? "Ativar plano gratuito" : "Assinar agora"}
                      </Button>
                      <PlanFeaturesList plan={plan} highlight={popular || preselected} />
                    </div>
                  </article>
                )
              })}
            </div>
          )}

          <p className="text-center text-xs text-zinc-400 max-w-lg mx-auto leading-relaxed">
            Pagamento processado com segurança pelo Mercado Pago · Cancele quando quiser ·{" "}
            <Link href="/landing#contact" className="underline underline-offset-2 hover:text-zinc-700">
              Precisa de ajuda?
            </Link>
          </p>
        </div>
      </div>
    </div>
  )
}

function SuccessStep({ planName, onContinue }: { planName: string; onContinue: () => void }) {
  return (
    <div className="px-4 py-16 sm:py-24 flex items-center justify-center">
      <div className="max-w-md w-full text-center space-y-6 rounded-2xl border border-zinc-200 bg-white p-8 sm:p-10 shadow-sm">
        <div className="flex justify-center">
          <div className="rounded-full bg-emerald-50 p-5">
            <CheckCircle className="h-14 w-14 text-emerald-600" />
          </div>
        </div>
        <div className="space-y-2">
          <h1 className="text-2xl font-bold text-zinc-900">Assinatura ativada!</h1>
          <p className="text-zinc-500 text-sm sm:text-base leading-relaxed">
            Bem-vindo ao plano <strong className="text-zinc-800">{planName}</strong>. Seu acesso
            completo está liberado.
          </p>
        </div>
        <div className="flex flex-col gap-2">
          <Button
            className="w-full rounded-md bg-zinc-900 text-white hover:bg-zinc-700 h-11"
            onClick={onContinue}
          >
            Acessar o sistema
          </Button>
          <Button asChild variant="ghost" className="w-full">
            <Link href="/landing">Voltar à página inicial</Link>
          </Button>
        </div>
      </div>
    </div>
  )
}

function SubscribePageContent() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const [step, setStep] = useState<Step>("plans")
  const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null)
  const [prefillPlanId, setPrefillPlanId] = useState<number | null>(null)
  const [bootstrapping, setBootstrapping] = useState(true)

  useEffect(() => {
    const planIdParam = searchParams.get("plan_id")
    if (!planIdParam) {
      setBootstrapping(false)
      return
    }

    const planId = Number(planIdParam)
    setPrefillPlanId(Number.isFinite(planId) ? planId : null)

    apiClient
      .get<Plan[]>(endpoints.subscription.plans)
      .then((res) => {
        const data = res.data
        const list = Array.isArray(data) ? data : (data as any)?.data
        const plan = (Array.isArray(list) ? list : []).find((p) => Number(p.id) === planId)
        if (plan) {
          setSelectedPlan(plan)
          setStep("payment")
        }
      })
      .catch(() => {
        /* permanece em planos */
      })
      .finally(() => setBootstrapping(false))
  }, [searchParams])

  const goToPlans = () => {
    setStep("plans")
    setSelectedPlan(null)
    router.replace("/subscribe", { scroll: false })
  }

  const selectPlan = (plan: Plan) => {
    setSelectedPlan(plan)
    setStep("payment")
    router.replace(`/subscribe?plan_id=${plan.id}`, { scroll: false })
  }

  if (bootstrapping) {
    return (
      <div className="min-h-[50vh] flex flex-col items-center justify-center gap-3">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="text-sm text-zinc-500">Preparando assinatura…</p>
      </div>
    )
  }

  return (
    <>
      {step === "plans" && (
        <PlansStep onSelect={selectPlan} highlightedPlanId={prefillPlanId} />
      )}
      {step === "payment" && selectedPlan && (
        <PaymentStep plan={selectedPlan} onBack={goToPlans} onSuccess={() => setStep("success")} />
      )}
      {step === "success" && selectedPlan && (
        <SuccessStep
          planName={selectedPlan.name}
          onContinue={() => router.push("/dashboard")}
        />
      )}
    </>
  )
}

export default function SubscribePage() {
  return (
    <SubscribeShell>
      <Suspense
        fallback={
          <div className="min-h-[50vh] flex items-center justify-center">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
          </div>
        }
      >
        <SubscribePageContent />
      </Suspense>
    </SubscribeShell>
  )
}
