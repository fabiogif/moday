"use client"

import { useMemo } from "react"
import { useRouter } from "next/navigation"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Switch } from "@/components/ui/switch"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu"
import { useAuthenticatedApi, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { resolveImageUrl } from "@/lib/resolve-image-url"
import { toast } from "sonner"
import { MoreHorizontal, Plus, RefreshCcw, TicketPercent } from "lucide-react"
import { Skeleton } from "@/components/ui/skeleton"
import { cn } from "@/lib/utils"
import { format } from "date-fns"

interface Coupon {
  uuid: string
  code: string
  name: string
  description?: string | null
  discount_type: "percentage" | "fixed"
  discount_value: number
  max_discount_amount?: number | null
  minimum_order_amount?: number | null
  usage_limit?: number | null
  usage_limit_per_client?: number | null
  times_redeemed: number
  start_at?: string | null
  end_at?: string | null
  is_active: boolean
  is_featured: boolean
  metadata?: Record<string, any> | null
  created_at?: string
  updated_at?: string
  image_url?: string | null
}

interface CouponStats {
  total: number
  active: number
  scheduled: number
  expired: number
  expiring_soon: number
}

const formatCurrency = (value?: number | null) => {
  if (value === undefined || value === null) return "-"
  return `R$ ${value.toFixed(2)}`
}

const formatDiscount = (coupon: Coupon) => {
  if (coupon.discount_type === "percentage") {
    return `${coupon.discount_value.toFixed(2)}%`
  }
  return formatCurrency(coupon.discount_value)
}

const formatDate = (value?: string | null) => {
  if (!value) return "—"
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return "—"
  return date.toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  })
}

const resolveStatus = (coupon: Coupon) => {
  const now = new Date()
  const start = coupon.start_at ? new Date(coupon.start_at) : null
  const end = coupon.end_at ? new Date(coupon.end_at) : null

  if (!coupon.is_active) return { label: "Inativo", tone: "destructive" as const }
  if (end && end.getTime() < now.getTime()) return { label: "Expirado", tone: "secondary" as const }
  if (start && start.getTime() > now.getTime()) return { label: "Agendado", tone: "warning" as const }
  return { label: "Ativo", tone: "default" as const }
}

export default function CouponsPage() {
  const router = useRouter()

  const { data: couponsResponse, loading, error, refetch, isAuthenticated } = useAuthenticatedApi<any>(
    endpoints.marketing.coupons.list
  )
  const { data: statsResponse, loading: statsLoading, refetch: refetchStats } = useAuthenticatedApi<CouponStats>(
    endpoints.marketing.coupons.stats
  )
  const { mutate: mutateToggle, loading: toggling } = useMutation()
  const { mutate: mutateDelete, loading: deleting } = useMutation()

  const coupons: Coupon[] = useMemo(() => {
    if (!couponsResponse) return []
    if (Array.isArray(couponsResponse)) return couponsResponse as Coupon[]
    if (Array.isArray(couponsResponse.data)) return couponsResponse.data as Coupon[]
    return []
  }, [couponsResponse])

  const stats: CouponStats | null = useMemo(() => {
    if (!statsResponse) return null
    if ("data" in (statsResponse as any)) {
      return (statsResponse as any).data as CouponStats
    }
    return statsResponse
  }, [statsResponse])

  const handleToggleActive = async (coupon: Coupon, active: boolean) => {
    try {
      await mutateToggle(endpoints.marketing.coupons.toggle(coupon.uuid), "PATCH", { is_active: active })
      toast.success(active ? "Cupom ativado" : "Cupom desativado")
      await Promise.all([refetch(), refetchStats()])
    } catch (err: any) {
      toast.error(err?.message || "Não foi possível alterar o status")
    }
  }

  const handleDelete = async (coupon: Coupon) => {
    try {
      await mutateDelete(endpoints.marketing.coupons.delete(coupon.uuid), "DELETE")
      toast.success("Cupom removido")
      await Promise.all([refetch(), refetchStats()])
    } catch (err: any) {
      toast.error(err?.message || "Não foi possível remover o cupom")
    }
  }

  const isBusy = toggling || deleting

  return (
    <div className="flex flex-col gap-6 px-4 pb-8 md:px-6">
      <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <div className="flex items-center gap-2 text-sm font-medium text-primary">
            <TicketPercent className="h-4 w-4" />
            Marketing
          </div>
          <h1 className="text-3xl font-bold tracking-tight">Cupons Promocionais</h1>
          <p className="text-muted-foreground">
            Crie campanhas de desconto, acompanhe desempenho e mantenha seus clientes engajados.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Button
            variant="outline"
            disabled={loading}
            onClick={() => {
              refetch()
              refetchStats()
            }}
          >
            {/* <RefreshCcw className={cn("mr-2 h-4 w-4", loading ? "animate-spin" : "")} /> Atualizar */}
          </Button>
          <Button onClick={() => router.push("/marketing/coupons/new")}>
            <Plus className="mr-2 h-4 w-4" /> Novo Cupom
          </Button>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {statsLoading ? (
          Array.from({ length: 4 }).map((_, index) => <Skeleton key={index} className="h-28 w-full rounded-2xl" />)
        ) : stats ? (
          [
            {
              label: "Cupons Ativos",
              value: stats.active,
              description: "Disponíveis para os clientes",
            },
            {
              label: "Agendados",
              value: stats.scheduled,
              description: "Entrarão em vigor futuramente",
            },
            {
              label: "Expirados",
              value: stats.expired,
              description: "Finalizados recentemente",
            },
            {
              label: "Total",
              value: stats.total,
              description: `${stats.expiring_soon} expiram em até 7 dias`,
            },
          ].map((stat, index) => (
            <Card key={index} className="rounded-2xl border border-border/60 shadow-sm">
              <CardHeader>
                <CardDescription>{stat.description}</CardDescription>
                <CardTitle className="text-3xl font-bold">{stat.value}</CardTitle>
              </CardHeader>
            </Card>
          ))
        ) : (
          <p className="text-sm text-muted-foreground">Não foi possível carregar as estatísticas.</p>
        )}
      </div>

      <Card className="rounded-3xl border border-border/60 shadow-sm">
        <CardHeader className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <CardTitle>Cupons cadastrados</CardTitle>
            <CardDescription>Gerencie códigos, limites de uso e período de validade.</CardDescription>
          </div>
        </CardHeader>
        <CardContent className="overflow-x-auto">
          {error ? (
            <div className="py-16 text-center text-sm text-muted-foreground">
              <p>Não foi possível carregar os cupons.</p>
              <Button variant="outline" className="mt-4" onClick={() => refetch()}>
                Tentar novamente
              </Button>
            </div>
          ) : loading ? (
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, index) => (
                <Skeleton key={index} className="h-16 w-full rounded-xl" />
              ))}
            </div>
          ) : coupons.length === 0 ? (
            <div className="py-16 text-center text-sm text-muted-foreground">
              <p>Nenhum cupom cadastrado ainda.</p>
              <p className="mt-1">Clique em "Novo Cupom" para criar sua primeira campanha.</p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-[140px]">Código</TableHead>
                  <TableHead>Nome</TableHead>
                  <TableHead className="hidden md:table-cell">Desconto</TableHead>
                  <TableHead className="hidden lg:table-cell">Período</TableHead>
                  <TableHead className="hidden xl:table-cell">Limites</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="w-[120px] text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {coupons.map((coupon) => {
                  const status = resolveStatus(coupon)
                  return (
                    <TableRow key={coupon.uuid} className="hover:bg-muted/50">
                      <TableCell>
                        <div className="flex flex-col gap-1">
                          <span className="font-semibold uppercase tracking-wide">{coupon.code}</span>
                          <span className="text-xs text-muted-foreground">Criação: {formatDate(coupon.created_at)}</span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-col gap-3">
                          {coupon.image_url && (
                            <div className="h-[60px] w-[110px] overflow-hidden rounded-md border border-border/50">
                              <img
                                src={resolveImageUrl(coupon.image_url) || ""}
                                alt={`Banner do cupom ${coupon.code}`}
                                className="h-full w-full object-cover"
                              />
                            </div>
                          )}
                          <p className="font-medium text-foreground">{coupon.name}</p>
                          {coupon.description && (
                            <p className="line-clamp-2 text-xs text-muted-foreground">{coupon.description}</p>
                          )}
                          <div className="flex flex-wrap gap-2 text-xs">
                            {coupon.is_featured && <Badge variant="outline">Destaque</Badge>}
                            {coupon.metadata?.segment && (
                              <Badge variant="outline">Segmento: {coupon.metadata.segment}</Badge>
                            )}
                          </div>
                        </div>
                      </TableCell>
                      <TableCell className="hidden md:table-cell">
                        <div className="space-y-1">
                          <p className="font-semibold text-primary">{formatDiscount(coupon)}</p>
                          {coupon.max_discount_amount ? (
                            <p className="text-xs text-muted-foreground">
                              Limite: {formatCurrency(coupon.max_discount_amount)}
                            </p>
                          ) : null}
                          {coupon.minimum_order_amount ? (
                            <p className="text-xs text-muted-foreground">
                              Pedido mínimo: {formatCurrency(coupon.minimum_order_amount)}
                            </p>
                          ) : null}
                        </div>
                      </TableCell>
                      <TableCell className="hidden lg:table-cell">
                        <div className="space-y-1 text-sm text-muted-foreground">
                          <p>Início: {formatDate(coupon.start_at)}</p>
                          <p>Fim: {formatDate(coupon.end_at)}</p>
                        </div>
                      </TableCell>
                      <TableCell className="hidden xl:table-cell">
                        <div className="space-y-1 text-sm text-muted-foreground">
                          <p>Uso total: {coupon.usage_limit ? `${coupon.times_redeemed}/${coupon.usage_limit}` : `${coupon.times_redeemed}`}</p>
                          <p>
                            Por cliente: {coupon.usage_limit_per_client ? coupon.usage_limit_per_client : "Ilimitado"}
                          </p>
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-col gap-2">
                          <div className="flex items-center gap-2">
                            <Switch
                              checked={coupon.is_active}
                              onCheckedChange={(checked) => handleToggleActive(coupon, checked)}
                              disabled={isBusy}
                            />
                            <Badge variant={status.tone === "default" ? "default" : "outline"}>{status.label}</Badge>
                          </div>
                          {coupon.end_at && (
                            <p className="text-[11px] text-muted-foreground">
                              Expira em {formatDate(coupon.end_at)}
                            </p>
                          )}
                        </div>
                      </TableCell>
                      <TableCell className="text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8">
                              <MoreHorizontal className="h-4 w-4" />
                              <span className="sr-only">Abrir ações</span>
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                          <DropdownMenuItem onSelect={() => router.push(`/marketing/coupons/${coupon.uuid}/edit`)}>
                              Editar
                            </DropdownMenuItem>
                            <DropdownMenuItem onSelect={() => handleToggleActive(coupon, !coupon.is_active)}>
                              {coupon.is_active ? "Desativar" : "Ativar"}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                              className="text-destructive focus:text-destructive"
                              onSelect={() => handleDelete(coupon)}
                            >
                              Remover
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  )
                })}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {!isAuthenticated && (
        <div className="rounded-3xl border border-dashed border-border/60 bg-muted/40 p-6 text-center text-sm text-muted-foreground">
          Você precisa estar autenticado para gerenciar cupons.
        </div>
      )}
    </div>
  )
}
