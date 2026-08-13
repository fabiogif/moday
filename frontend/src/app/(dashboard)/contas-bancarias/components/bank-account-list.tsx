'use client'

import { BankAccount } from '@/types/bank-account'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { CreditCard, Star, Edit, Trash2 } from 'lucide-react'

interface Props {
  accounts: BankAccount[]
  onEdit: (account: BankAccount) => void
  onDelete: (account: BankAccount) => void
  onSetPrimary: (account: BankAccount) => void
}

const accountTypeLabels = {
  checking: 'Corrente',
  savings: 'Poupança',
  payment: 'Pagamento',
}

export function BankAccountList({ accounts, onEdit, onDelete, onSetPrimary }: Props) {
  return (
    <div className="space-y-4">
      {accounts.map((account) => (
        <Card key={account.uuid} className="p-4 hover:bg-accent/50 transition-colors">
          <div className="flex items-center justify-between gap-4">
            {/* Left Side - Account Info */}
            <div className="flex items-center gap-4 flex-1">
              <div className={`p-3 rounded-lg ${account.is_primary ? 'bg-green-100 dark:bg-green-950' : 'bg-muted'}`}>
                <CreditCard className={`h-6 w-6 ${account.is_primary ? 'text-green-600' : 'text-muted-foreground'}`} />
              </div>
              
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 flex-wrap mb-1">
                  <h3 className="font-semibold">{account.bank_name}</h3>
                  
                  {account.is_primary && (
                    <Badge variant="default" className="gap-1">
                      <Star className="h-3 w-3 fill-current" />
                      Principal
                    </Badge>
                  )}
                  
                  {!account.is_active && (
                    <Badge variant="destructive">Inativa</Badge>
                  )}

                  {account.is_verified ? (
                    <Badge variant="outline" className="text-green-600 border-green-300">Verificada</Badge>
                  ) : (
                    <Badge variant="outline" className="text-yellow-600 border-yellow-300">Pendente</Badge>
                  )}

                  <Badge variant="secondary">{accountTypeLabels[account.account_type]}</Badge>
                </div>
                
                <p className="text-sm text-muted-foreground">
                  Ag: {account.agency}
                  {account.agency_digit && `-${account.agency_digit}`} • 
                  Conta: {account.account_number_masked}
                </p>
                
                <p className="text-xs text-muted-foreground mt-0.5">
                  {account.account_holder_name}
                </p>
                
                {account.pix_key && (
                  <div className="flex items-center gap-2 mt-2">
                    <Badge variant="outline" className="text-xs">
                      PIX: {account.pix_key_type?.toUpperCase()}
                    </Badge>
                    <span className="text-xs font-mono text-muted-foreground">
                      {account.pix_key}
                    </span>
                  </div>
                )}
              </div>
            </div>
            
            {/* Right Side - Actions */}
            <div className="flex gap-2">
              {!account.is_primary && account.is_active && (
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => onSetPrimary(account)}
                  title="Tornar conta principal"
                >
                  <Star className="h-4 w-4 mr-1" />
                  Tornar Principal
                </Button>
              )}
              
              <Button
                size="sm"
                variant="ghost"
                onClick={() => onEdit(account)}
                title="Editar conta"
              >
                <Edit className="h-4 w-4" />
              </Button>
              
              {!account.is_primary && (
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => onDelete(account)}
                  title="Excluir conta"
                  className="text-destructive hover:text-destructive"
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              )}
            </div>
          </div>
        </Card>
      ))}
    </div>
  )
}

