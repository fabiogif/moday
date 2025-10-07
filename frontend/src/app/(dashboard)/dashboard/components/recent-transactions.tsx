"use client"

import { useEffect, useState } from "react"
import { Eye, MoreHorizontal } from "lucide-react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Skeleton } from "@/components/ui/skeleton"
import { useAuth } from "@/contexts/auth-context"
import { useRouter } from "next/navigation"
import { useAuthenticatedApi } from "@/hooks/use-authenticated-api"

interface Transaction {
  id: number
  identify: string
  client: {
    name: string
    email: string
  }
  table: string | null
  total: number
  formatted_total: string
  status: string
  payment_method: string
  created_at: string
  created_at_human: string
}

const statusConfig: Record<string, { variant: "default" | "secondary" | "destructive" | "outline", label: string }> = {
  "Em Preparo": { variant: "secondary", label: "Em Preparo" },
  "Pronto": { variant: "default", label: "Pronto" },
  "Entregue": { variant: "outline", label: "Entregue" },
  "Cancelado": { variant: "destructive", label: "Cancelado" },
}

export function RecentTransactions() {
  const { isAuthenticated, isLoading: authLoading } = useAuth()
  const [transactions, setTransactions] = useState<Transaction[]>([])
  const router = useRouter()

  // Use authenticated API hook for recent transactions
  const { data: transactionsData, loading, error, refetch } = useAuthenticatedApi<{
    transactions: Transaction[]
  }>(
    '/api/dashboard/recent-transactions',
    { immediate: false }
  )

  useEffect(() => {
    // Aguardar autenticação completa antes de carregar
    if (!authLoading && isAuthenticated) {
      refetch()
    }
  }, [authLoading, isAuthenticated, refetch])

  useEffect(() => {
    if (transactionsData) {
      setTransactions(transactionsData.transactions)
    }
  }, [transactionsData])

  function handleViewOrderDetails(transaction: Transaction) {
    // Navigate to orders page and open order details dialog
    router.push(`/orders?view=${transaction.identify}`)
  }

  if (authLoading || loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Transações Recentes</CardTitle>
          <CardDescription>Últimas transações do cliente</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="flex items-center gap-4">
                <Skeleton className="h-10 w-10 rounded-full" />
                <div className="flex-1 space-y-2">
                  <Skeleton className="h-4 w-32" />
                  <Skeleton className="h-3 w-24" />
                </div>
                <Skeleton className="h-6 w-20" />
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Transações Recentes</CardTitle>
          <CardDescription>Últimas transações do cliente</CardDescription>
        </div>
        <Button variant="ghost" size="sm">
          Ver todas
        </Button>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {transactions.length === 0 ? (
            <p className="text-sm text-muted-foreground text-center py-4">Nenhuma transação encontrada</p>
          ) : (
            transactions.map((transaction) => {
              const statusInfo = statusConfig[transaction.status] || { variant: "outline", label: transaction.status }
              const initials = transaction.client.name
                .split(' ')
                .map(n => n[0])
                .join('')
                .toUpperCase()
                .slice(0, 2)

              return (
                <div key={transaction.id} className="flex items-center gap-4">
                  <Avatar className="h-9 w-9">
                    <AvatarFallback>{initials}</AvatarFallback>
                  </Avatar>
                  <div className="flex-1 space-y-1">
                    <p className="text-sm font-medium leading-none">{transaction.client.name}</p>
                    <p className="text-sm text-muted-foreground">
                      {transaction.client.email || transaction.identify}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <div className="text-right">
                      <p className="text-sm font-medium">{transaction.formatted_total}</p>
                      <p className="text-xs text-muted-foreground">{transaction.created_at_human}</p>
                    </div>
                    <Badge variant={statusInfo.variant} className="ml-2">
                      {statusInfo.label}
                    </Badge>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="h-8 w-8">
                          <MoreHorizontal className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => handleViewOrderDetails(transaction)}>
                          <Eye className="mr-2 h-4 w-4" />
                          Ver detalhes
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                </div>
              )
            })
          )}
        </div>
      </CardContent>
    </Card>
  )
}