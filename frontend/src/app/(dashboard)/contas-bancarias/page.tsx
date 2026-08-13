'use client'

import { useEffect, useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Plus, CreditCard, CheckCircle2, Loader2 } from 'lucide-react'
import { BankAccountForm } from './components/bank-account-form'
import { BankAccountList } from './components/bank-account-list'
import api, { endpoints } from '@/lib/api-client'
import { toast } from 'sonner'
import { BankAccount } from '@/types/bank-account'

export default function ContasBancariasPage() {
  const [accounts, setAccounts] = useState<BankAccount[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [editingAccount, setEditingAccount] = useState<BankAccount | null>(null)

  useEffect(() => {
    loadAccounts()
  }, [])

  const loadAccounts = async () => {
    try {
      setIsLoading(true)
      const response = await api.get<BankAccount[]>(endpoints.bankAccounts.list)
      
      if (response.success && response.data) {
        setAccounts(response.data)
      } else {

        setAccounts([])
      }
    } catch (error: any) {

      const errorMessage = error?.response?.data?.message || 
                          error?.message || 
                          'Erro ao carregar contas bancárias'
      
      toast.error(errorMessage)
      setAccounts([])
    } finally {
      setIsLoading(false)
    }
  }

  const handleDelete = async (account: BankAccount) => {
    if (!confirm(`Tem certeza que deseja excluir a conta ${account.bank_name}?`)) {
      return
    }

    try {
      await api.delete(endpoints.bankAccounts.delete(account.uuid))
      toast.success('Conta bancária excluída com sucesso!')
      await loadAccounts()
    } catch (error: any) {

      toast.error(error.message || 'Erro ao excluir conta bancária')
    }
  }

  const handleSetPrimary = async (account: BankAccount) => {
    try {
      await api.post(endpoints.bankAccounts.setPrimary(account.uuid), {})
      toast.success('Conta definida como principal!')
      await loadAccounts()
    } catch (error: any) {

      toast.error(error.message || 'Erro ao definir conta como principal')
    }
  }

  const handleEdit = (account: BankAccount) => {
    setEditingAccount(account)
    setShowForm(true)
  }

  const handleFormSuccess = () => {
    setShowForm(false)
    setEditingAccount(null)
    loadAccounts()
  }

  const handleFormClose = () => {
    setShowForm(false)
    setEditingAccount(null)
  }

  const primaryAccount = accounts.find(acc => acc.is_primary)

  return (
    <div className="flex-1 space-y-6 p-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Contas Bancárias</h1>
          <p className="text-muted-foreground">
            Gerencie as contas bancárias para recebimento
          </p>
        </div>
        <Button onClick={() => setShowForm(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Nova Conta
        </Button>
      </div>

      {/* Loading State */}
      {isLoading && (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
        </div>
      )}

      {/* Content */}
      {!isLoading && (
        <>
          {/* Conta Principal */}
          {primaryAccount && (
            <Card className="border-green-200 bg-green-50 dark:bg-green-950/20">
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="text-lg flex items-center gap-2">
                    <CreditCard className="h-5 w-5" />
                    Conta Principal
                  </CardTitle>
                  <Badge variant="default" className="gap-1">
                    <CheckCircle2 className="h-3 w-3" />
                    Principal
                  </Badge>
                </div>
              </CardHeader>
              <CardContent className="space-y-2">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-semibold">{primaryAccount.bank_name}</p>
                    <p className="text-sm text-muted-foreground">
                      Ag: {primaryAccount.agency}
                      {primaryAccount.agency_digit && `-${primaryAccount.agency_digit}`} • 
                      Conta: {primaryAccount.account_number_masked}
                    </p>
                    <p className="text-sm">{primaryAccount.account_holder_name}</p>
                  </div>
                  <div className="flex flex-col items-end gap-1">
                    {!primaryAccount.is_active && (
                      <Badge variant="destructive">Inativa</Badge>
                    )}
                  </div>
                </div>
                {primaryAccount.pix_key && (
                  <div className="pt-2 border-t">
                    <p className="text-xs text-muted-foreground">Chave PIX</p>
                    <p className="text-sm font-mono">{primaryAccount.pix_key}</p>
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {/* Lista de Contas */}
          <Card>
            <CardHeader>
              <CardTitle>Todas as Contas</CardTitle>
              <CardDescription>
                {accounts.length} conta{accounts.length !== 1 ? 's' : ''} cadastrada{accounts.length !== 1 ? 's' : ''}
              </CardDescription>
            </CardHeader>
            <CardContent>
              {accounts.length === 0 ? (
                <div className="text-center py-12">
                  <CreditCard className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                  <p className="text-muted-foreground mb-4">
                    Nenhuma conta bancária cadastrada
                  </p>
                  <Button onClick={() => setShowForm(true)}>
                    <Plus className="mr-2 h-4 w-4" />
                    Cadastrar Primeira Conta
                  </Button>
                </div>
              ) : (
                <BankAccountList
                  accounts={accounts}
                  onEdit={handleEdit}
                  onDelete={handleDelete}
                  onSetPrimary={handleSetPrimary}
                />
              )}
            </CardContent>
          </Card>
        </>
      )}

      {/* Dialog de Formulário */}
      {showForm && (
        <BankAccountForm
          open={showForm}
          account={editingAccount}
          onClose={handleFormClose}
          onSuccess={handleFormSuccess}
        />
      )}
    </div>
  )
}

