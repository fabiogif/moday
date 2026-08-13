'use client'

import { useState, useEffect } from 'react'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Textarea } from '@/components/ui/textarea'
import api, { endpoints } from '@/lib/api-client'
import { toast } from 'sonner'
import { Bank, BankAccount, BankAccountDetail, BankAccountFormData } from '@/types/bank-account'
import { Loader2 } from 'lucide-react'

// Funções de máscara para Chave PIX
const maskPixKey = (value: string, type: string): string => {
  const cleaned = value.replace(/\D/g, '')
  
  switch (type) {
    case 'cpf':
      // 111.111.111-11
      return cleaned
        .slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
    
    case 'cnpj':
      // 11.111.111/1111-11
      return cleaned
        .slice(0, 14)
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2')
    
    case 'phone':
      // (11) 99999-9999
      return cleaned
        .slice(0, 11)
        .replace(/(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d{1,4})$/, '$1-$2')
    
    default:
      return value
  }
}

const getPixKeyPlaceholder = (type?: string): string => {
  switch (type) {
    case 'cpf':
      return '000.000.000-00'
    case 'cnpj':
      return '00.000.000/0000-00'
    case 'phone':
      return '(00) 00000-0000'
    case 'email':
      return 'email@exemplo.com'
    case 'random':
      return 'Chave aleatória do banco'
    default:
      return 'Digite a chave PIX'
  }
}

const getPixKeyMaxLength = (type?: string): number => {
  switch (type) {
    case 'cpf':
      return 14 // 111.111.111-11
    case 'cnpj':
      return 18 // 11.111.111/1111-11
    case 'phone':
      return 15 // (11) 99999-9999
    case 'email':
      return 100
    case 'random':
      return 100
    default:
      return 100
  }
}

interface BankAccountFormProps {
  open: boolean
  account?: BankAccount | null
  onClose: () => void
  onSuccess: () => void
}

export function BankAccountForm({ open, account, onClose, onSuccess }: BankAccountFormProps) {
  const [banks, setBanks] = useState<Bank[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [isLoadingBanks, setIsLoadingBanks] = useState(true)
  
  const [formData, setFormData] = useState<BankAccountFormData>({
    account_type: 'checking',
    bank_code: '',
    agency: '',
    agency_digit: '',
    account_number: '',
    account_digit: '',
    account_holder_name: '',
    account_holder_document: '',
    account_holder_type: 'company',
    pix_key_type: undefined,
    pix_key: '',
    is_primary: false,
    notes: '',
  })

  useEffect(() => {
    if (open) {
      loadBanks()
      if (account) {
        loadAccountDetails()
      }
    }
  }, [open, account])

  const loadBanks = async () => {
    try {
      setIsLoadingBanks(true)
      const response = await api.get<Bank[]>(endpoints.bankAccounts.banks)
      if (response.success && response.data) {
        setBanks(response.data)
      }
    } catch (error) {

      toast.error('Erro ao carregar lista de bancos')
    } finally {
      setIsLoadingBanks(false)
    }
  }

  const loadAccountDetails = async () => {
    if (!account) return
    
    try {
      const response = await api.get<BankAccountDetail>(endpoints.bankAccounts.show(account.uuid))
      if (response.success && response.data) {
        const data = response.data
        setFormData({
          account_type: data.account_type,
          bank_code: data.bank_code,
          agency: data.agency,
          agency_digit: data.agency_digit || '',
          account_number: data.account_number,
          account_digit: data.account_digit,
          account_holder_name: data.account_holder_name,
          account_holder_document: data.account_holder_document,
          account_holder_type: data.account_holder_type,
          pix_key_type: data.pix_key_type || undefined,
          pix_key: data.pix_key || '',
          notes: data.notes || '',
        })
      }
    } catch (error) {

      toast.error('Erro ao carregar detalhes da conta')
    }
  }

  const formatDocument = (value: string) => {
    const numbers = value.replace(/\D/g, '')
    if (numbers.length <= 11) {
      // CPF
      return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
    } else {
      // CNPJ
      return numbers.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    
    // Validações
    if (!formData.bank_code) {
      toast.error('Selecione um banco')
      return
    }

    if (!formData.account_number || !formData.account_digit) {
      toast.error('Preencha o número e dígito da conta')
      return
    }

    const cleanDocument = formData.account_holder_document.replace(/\D/g, '')
    if (cleanDocument.length !== 11 && cleanDocument.length !== 14) {
      toast.error('CPF deve ter 11 dígitos ou CNPJ 14 dígitos')
      return
    }

    try {
      setIsLoading(true)
      
      const dataToSend = {
        ...formData,
        account_holder_document: cleanDocument,
        pix_key: formData.pix_key || undefined,
        pix_key_type: formData.pix_key ? formData.pix_key_type : undefined,
      }

      if (account) {
        // Editar (somente campos permitidos)
        await api.put(endpoints.bankAccounts.update(account.uuid), {
          account_type: dataToSend.account_type,
          agency: dataToSend.agency,
          agency_digit: dataToSend.agency_digit,
          pix_key_type: dataToSend.pix_key_type,
          pix_key: dataToSend.pix_key,
          notes: dataToSend.notes,
        })
        toast.success('Conta bancária atualizada com sucesso!')
      } else {
        // Criar
        await api.post(endpoints.bankAccounts.create, dataToSend)
        toast.success('Conta bancária cadastrada com sucesso!')
      }
      
      onSuccess()
    } catch (error: any) {

      toast.error(error.message || 'Erro ao salvar conta bancária')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={onClose}>
      <DialogContent className="flex max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl flex-col gap-6 overflow-x-hidden overflow-y-auto p-6 sm:max-w-2xl">
        <DialogHeader className="shrink-0 space-y-1 text-left">
          <DialogTitle>{account ? 'Editar' : 'Nova'} Conta Bancária</DialogTitle>
          <DialogDescription>
            {account
              ? 'Atualize os dados da conta bancária'
              : 'Cadastre uma conta bancária para receber pagamentos'}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="flex min-h-0 flex-1 flex-col gap-6 overflow-x-hidden">
          <div className="min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden pr-1">
            <div className="min-w-0 space-y-2">
              <Label htmlFor="account_type">Tipo de Conta *</Label>
              <Select
                value={formData.account_type}
                onValueChange={(value: BankAccountFormData['account_type']) =>
                  setFormData({ ...formData, account_type: value })
                }
              >
                <SelectTrigger id="account_type" className="h-9 w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="checking">Conta Corrente</SelectItem>
                  <SelectItem value="savings">Conta Poupança</SelectItem>
                  <SelectItem value="payment">Conta Pagamento</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="min-w-0 space-y-2">
              <Label htmlFor="bank_code">Banco *</Label>
              <Select
                value={formData.bank_code}
                onValueChange={(value) => setFormData({ ...formData, bank_code: value })}
                disabled={isLoadingBanks || !!account}
              >
                <SelectTrigger id="bank_code" className="h-9 w-full">
                  <SelectValue
                    placeholder={isLoadingBanks ? 'Carregando...' : 'Selecione o banco'}
                  />
                </SelectTrigger>
                <SelectContent>
                  {banks.map((bank) => (
                    <SelectItem key={bank.code} value={bank.code}>
                      {bank.code} - {bank.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div className="min-w-0 space-y-2 sm:col-span-2">
                <Label htmlFor="agency">Agência *</Label>
                <Input
                  id="agency"
                  className="h-9 w-full"
                  value={formData.agency}
                  onChange={(e) =>
                    setFormData({ ...formData, agency: e.target.value.replace(/\D/g, '') })
                  }
                  placeholder="0001"
                  maxLength={10}
                  required
                  disabled={!!account}
                />
              </div>
              <div className="min-w-0 space-y-2">
                <Label htmlFor="agency_digit">Dígito</Label>
                <Input
                  id="agency_digit"
                  className="h-9 w-full"
                  value={formData.agency_digit}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      agency_digit: e.target.value.replace(/\D/g, ''),
                    })
                  }
                  placeholder="0"
                  maxLength={2}
                  disabled={!!account}
                />
              </div>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div className="min-w-0 space-y-2 sm:col-span-2">
                <Label htmlFor="account_number">Número da Conta *</Label>
                <Input
                  id="account_number"
                  className="h-9 w-full"
                  value={formData.account_number}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      account_number: e.target.value.replace(/\D/g, ''),
                    })
                  }
                  placeholder="12345678"
                  maxLength={20}
                  required
                  disabled={!!account}
                />
              </div>
              <div className="min-w-0 space-y-2">
                <Label htmlFor="account_digit">Dígito *</Label>
                <Input
                  id="account_digit"
                  className="h-9 w-full"
                  value={formData.account_digit}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      account_digit: e.target.value.replace(/\D/g, ''),
                    })
                  }
                  placeholder="0"
                  maxLength={2}
                  required
                  disabled={!!account}
                />
              </div>
            </div>

            {!account && (
              <>
                <div className="min-w-0 space-y-2">
                  <Label htmlFor="account_holder_name">Nome do Titular *</Label>
                  <Input
                    id="account_holder_name"
                    className="h-9 w-full"
                    value={formData.account_holder_name}
                    onChange={(e) =>
                      setFormData({ ...formData, account_holder_name: e.target.value })
                    }
                    placeholder="Nome completo ou razão social"
                    required
                  />
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div className="min-w-0 space-y-2">
                    <Label htmlFor="account_holder_type">Tipo de Titular *</Label>
                    <Select
                      value={formData.account_holder_type}
                      onValueChange={(value: BankAccountFormData['account_holder_type']) =>
                        setFormData({ ...formData, account_holder_type: value })
                      }
                    >
                      <SelectTrigger id="account_holder_type" className="h-9 w-full">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="individual">Pessoa Física</SelectItem>
                        <SelectItem value="company">Pessoa Jurídica</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="min-w-0 space-y-2">
                    <Label htmlFor="account_holder_document">CPF/CNPJ *</Label>
                    <Input
                      id="account_holder_document"
                      className="h-9 w-full"
                      value={formData.account_holder_document}
                      onChange={(e) => {
                        const formatted = formatDocument(e.target.value)
                        setFormData({ ...formData, account_holder_document: formatted })
                      }}
                      placeholder={
                        formData.account_holder_type === 'individual'
                          ? '000.000.000-00'
                          : '00.000.000/0000-00'
                      }
                      required
                    />
                  </div>
                </div>
              </>
            )}

            <div className="space-y-4 border-t pt-4">
              <Label className="text-base font-semibold">Chave PIX (Opcional)</Label>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="min-w-0 space-y-2">
                  <Label htmlFor="pix_key_type">Tipo de Chave</Label>
                  <Select
                    value={formData.pix_key_type || ''}
                    onValueChange={(value) => {
                      setFormData({
                        ...formData,
                        pix_key_type: value as BankAccountFormData['pix_key_type'],
                        pix_key: '',
                      })
                    }}
                  >
                    <SelectTrigger id="pix_key_type" className="h-9 w-full">
                      <SelectValue placeholder="Selecione" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="cpf">CPF</SelectItem>
                      <SelectItem value="cnpj">CNPJ</SelectItem>
                      <SelectItem value="email">E-mail</SelectItem>
                      <SelectItem value="phone">Telefone</SelectItem>
                      <SelectItem value="random">Chave Aleatória</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="min-w-0 space-y-2">
                  <Label htmlFor="pix_key">Chave</Label>
                  <Input
                    id="pix_key"
                    className="h-9 w-full"
                    type={formData.pix_key_type === 'email' ? 'email' : 'text'}
                    value={formData.pix_key}
                    onChange={(e) => {
                      const value = e.target.value
                      const type = formData.pix_key_type

                      if (type && ['cpf', 'cnpj', 'phone'].includes(type)) {
                        const masked = maskPixKey(value, type)
                        setFormData({ ...formData, pix_key: masked })
                      } else {
                        setFormData({ ...formData, pix_key: value })
                      }
                    }}
                    placeholder={getPixKeyPlaceholder(formData.pix_key_type)}
                    maxLength={getPixKeyMaxLength(formData.pix_key_type)}
                    disabled={!formData.pix_key_type}
                  />
                </div>
              </div>
            </div>

            <div className="min-w-0 space-y-2">
              <Label htmlFor="notes">Observações</Label>
              <Textarea
                id="notes"
                className="w-full resize-none"
                value={formData.notes}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                placeholder="Observações internas sobre esta conta..."
                rows={3}
              />
            </div>

            {!account && (
              <div className="flex items-start gap-2">
                <Checkbox
                  id="is_primary"
                  className="mt-0.5"
                  checked={formData.is_primary}
                  onCheckedChange={(checked) =>
                    setFormData({ ...formData, is_primary: checked as boolean })
                  }
                />
                <label
                  htmlFor="is_primary"
                  className="text-sm font-medium leading-snug peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                >
                  Definir como conta principal para recebimentos
                </label>
              </div>
            )}
          </div>

          <DialogFooter className="shrink-0 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <Button
              type="button"
              variant="outline"
              className="h-9 w-full sm:justify-self-start"
              onClick={onClose}
              disabled={isLoading}
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              className="h-9 w-full sm:justify-self-end"
              disabled={isLoading}
            >
              {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {isLoading ? 'Salvando...' : account ? 'Atualizar' : 'Cadastrar'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

