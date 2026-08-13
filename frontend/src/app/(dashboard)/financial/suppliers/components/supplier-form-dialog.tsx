'use client'

import { useState, useEffect, type ChangeEvent } from 'react'
import { useForm } from 'react-hook-form'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Supplier, SupplierFormData } from '@/hooks/use-suppliers'
import { Building2, Phone, MapPin, CreditCard, AlertCircle, ChevronLeft, ChevronRight, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'
import { extractValidationErrors } from '@/lib/error-formatter'
import { OrderStepper } from '@/components/order-stepper'
import { useViaCEP } from '@/hooks/use-viacep'
import { StateCitySelect } from '@/components/location/state-city-select'
import { applyCepToForm } from '@/lib/apply-cep-to-form'
import { useReceitaWS } from '@/hooks/use-receitaws'
import { formatReceitaWSCEP, type CompanyData } from '@/services/receitaws'
import { maskCNPJ, maskCPF, maskPhone, maskZipCode } from '@/lib/masks'
import { CnpjAutofillConfirmDialog } from '@/components/cnpj-autofill-confirm-dialog'
import {
  scheduleWizardStep,
  WIZARD_DIALOG_CONTENT_CLASS,
} from '../../components/account-form-shared'

interface SupplierFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  supplier?: Supplier | null
  onSubmit: (data: SupplierFormData) => Promise<void>
  isLoading?: boolean
}

const STEPS = [
  { label: 'Dados Principais', icon: Building2 },
  { label: 'Contato', icon: Phone },
  { label: 'Endereço', icon: MapPin },
  { label: 'Financeiro', icon: CreditCard },
]

// Campos validados ao avançar de cada passo (os demais são opcionais)
const STEP_FIELDS: (keyof SupplierFormData)[][] = [
  ['name', 'document_type', 'document'],
  ['phone'],
  [],
  [],
]

// Passo em que cada campo aparece — usado para levar o usuário até o erro retornado pelo backend
const FIELD_STEP: Partial<Record<keyof SupplierFormData, number>> = {
  name: 0,
  fantasy_name: 0,
  document_type: 0,
  document: 0,
  phone: 1,
  phone2: 1,
  email: 1,
  address: 2,
  number: 2,
  complement: 2,
  neighborhood: 2,
  zip_code: 2,
  city: 2,
  state: 2,
  bank_name: 3,
  bank_agency: 3,
  bank_account: 3,
  pix_key: 3,
  notes: 3,
}

export function SupplierFormDialog({
  open,
  onOpenChange,
  supplier,
  onSubmit,
  isLoading,
}: SupplierFormDialogProps) {
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})
  const [currentStep, setCurrentStep] = useState(0)
  const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set())
  const [cnpjAutofillOpen, setCnpjAutofillOpen] = useState(false)
  const [pendingCompany, setPendingCompany] = useState<CompanyData | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
    setValue,
    getValues,
    watch,
    trigger,
  } = useForm<SupplierFormData>({
    mode: 'onBlur',
  })

  const { loading: loadingCEP, searchCEP } = useViaCEP()
  const { loading: loadingCNPJ, searchCNPJ } = useReceitaWS()

  const documentType = watch('document_type')
  const documentValue = watch('document')
  const phoneValue = watch('phone')
  const phone2Value = watch('phone2')
  const zipCodeValue = watch('zip_code')
  const stateValue = watch('state')
  const cityValue = watch('city')

  useEffect(() => {
    register('document', { required: 'O documento é obrigatório' })
    register('phone', { required: 'O telefone é obrigatório' })
  }, [register])

  useEffect(() => {
    if (supplier) {
      Object.entries(supplier).forEach(([key, value]) => {
        setValue(key as any, value)
      })
      setBackendErrors({})
    } else {
      reset()
      setValue('document_type', 'cnpj')
      setValue('is_active', true)
      setBackendErrors({})
    }
    setCurrentStep(0)
    setCompletedSteps(new Set())
    setCnpjAutofillOpen(false)
    setPendingCompany(null)
  }, [supplier, setValue, reset, open])

  const applyCompanyAutofill = (company: CompanyData) => {
    const current = getValues()

    if (company.nome && !current.name) setValue('name', company.nome, { shouldDirty: true })
    if (company.nomeFantasia && !current.fantasy_name) {
      setValue('fantasy_name', company.nomeFantasia, { shouldDirty: true })
    }
    if (company.email && !current.email) setValue('email', company.email, { shouldDirty: true })
    if (company.phone && !current.phone) {
      setValue('phone', maskPhone(company.phone), { shouldDirty: true })
    }

    if (company.address) setValue('address', company.address, { shouldDirty: true })
    if (company.number) setValue('number', company.number, { shouldDirty: true })
    if (company.complement) setValue('complement', company.complement, { shouldDirty: true })
    if (company.neighborhood) setValue('neighborhood', company.neighborhood, { shouldDirty: true })
    if (company.city) setValue('city', company.city, { shouldDirty: true })
    if (company.state) setValue('state', company.state, { shouldDirty: true })
    if (company.zipCode) {
      setValue('zip_code', formatReceitaWSCEP(company.zipCode), { shouldDirty: true })
    }

    toast.success('Dados do fornecedor preenchidos automaticamente!')
  }

  const handleConfirmCnpjAutofill = () => {
    if (pendingCompany) {
      applyCompanyAutofill(pendingCompany)
    }
    setPendingCompany(null)
    setCnpjAutofillOpen(false)
  }

  const applyMaskedValue = (
    field: keyof SupplierFormData,
    value: string,
    maskFn: (value: string) => string
  ) => {
    setValue(field, maskFn(value) as never, { shouldDirty: true, shouldValidate: true })
  }

  const handleDocumentChange = (e: ChangeEvent<HTMLInputElement>) => {
    const maskFn = documentType === 'cpf' ? maskCPF : maskCNPJ
    applyMaskedValue('document', e.target.value, maskFn)
  }

  const handlePhoneChange = (field: 'phone' | 'phone2') => (e: ChangeEvent<HTMLInputElement>) => {
    applyMaskedValue(field, e.target.value, maskPhone)
  }

  const handleZipCodeChange = (e: ChangeEvent<HTMLInputElement>) => {
    applyMaskedValue('zip_code', e.target.value, maskZipCode)
  }

  const handleSearchCNPJ = async (cnpj: string) => {
    if (documentType !== 'cnpj') return

    const cleanCNPJ = cnpj.replace(/\D/g, '')
    if (cleanCNPJ.length !== 14) return

    const company = await searchCNPJ(cnpj)
    if (!company) return

    setPendingCompany(company)
    setCnpjAutofillOpen(true)
  }

  const handleSearchCEP = async (cep: string) => {
    const cleanCEP = cep.replace(/\D/g, '')
    if (cleanCEP.length !== 8) return

    try {
      const address = await searchCEP(cep)
      if (!address) return

      applyCepToForm(setValue, address, {
        address: 'address',
        neighborhood: 'neighborhood',
        state: 'state',
        city: 'city',
      })
    } catch {
      // Erro já tratado pelo useViaCEP
    }
  }

  const goNext = async () => {
    const fields = STEP_FIELDS[currentStep]
    const valid = fields.length === 0 || (await trigger(fields))
    if (!valid) return
    setCompletedSteps((prev) => new Set(prev).add(currentStep))
    scheduleWizardStep(() => {
      setCurrentStep((s) => Math.min(s + 1, STEPS.length - 1))
    })
  }

  const goBack = () => setCurrentStep((s) => Math.max(s - 1, 0))

  const goToStep = (step: number) => {
    if (step <= currentStep || completedSteps.has(step)) {
      setCurrentStep(step)
    }
  }

  const handleFormSubmit = async (data: any) => {
    try {
      setBackendErrors({})
      await onSubmit(data)
      reset()
      setBackendErrors({})
      setCurrentStep(0)
      setCompletedSteps(new Set())
    } catch (error: any) {
      const validationErrors = extractValidationErrors(error)
      setBackendErrors(validationErrors)

      const errorCount = Object.keys(validationErrors).filter(k => k !== '_general').length
      if (validationErrors._general) {
        toast.error(validationErrors._general)
      } else if (errorCount > 0) {
        const firstError = Object.values(validationErrors)[0]
        toast.error(firstError, {
          description: errorCount > 1 ? `${errorCount - 1} outro(s) erro(s)` : undefined
        })
      }

      // Leva o usuário até o passo onde está o primeiro campo com erro retornado pelo backend
      const firstErrorField = Object.keys(validationErrors).find(k => k !== '_general') as keyof SupplierFormData | undefined
      const stepWithError = firstErrorField ? FIELD_STEP[firstErrorField] : undefined
      if (stepWithError !== undefined) {
        setCurrentStep(stepWithError)
      }

      const dialogContent = document.querySelector('[role="dialog"]')
      if (dialogContent) {
        dialogContent.scrollTop = 0
      }
    }
  }

  const hasError = (field: string) => errors[field as keyof SupplierFormData] || backendErrors[field]
  const getErrorMessage = (field: string) => {
    const frontendError = errors[field as keyof SupplierFormData]?.message
    const backendError = backendErrors[field]
    return frontendError || backendError
  }

  const isLastStep = currentStep === STEPS.length - 1

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className={WIZARD_DIALOG_CONTENT_CLASS}>
        <DialogHeader className="shrink-0 space-y-1 text-left">
          <DialogTitle className="flex items-center gap-2 text-xl font-bold sm:text-2xl">
            <Building2 className="h-5 w-5 shrink-0 sm:h-6 sm:w-6" />
            {supplier ? 'Editar Fornecedor' : 'Novo Fornecedor'}
          </DialogTitle>
          <DialogDescription>
            Preencha os dados do fornecedor. Campos com * são obrigatórios.
          </DialogDescription>
        </DialogHeader>

        <div className="shrink-0 overflow-x-hidden">
          <OrderStepper
            currentStep={currentStep}
            steps={STEPS}
            onStepClick={goToStep}
            completedSteps={completedSteps}
          />
        </div>

        {backendErrors._general && (
          <Alert variant="destructive" className="shrink-0">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{backendErrors._general}</AlertDescription>
          </Alert>
        )}

        <form
          onSubmit={(e) => {
            if (!isLastStep) {
              e.preventDefault()
              return
            }
            void handleSubmit(handleFormSubmit)(e)
          }}
          className="flex min-h-0 flex-1 flex-col gap-6 overflow-x-hidden"
        >
          <div className="min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden pr-1">
          {/* Passo 1: Dados Principais */}
          {currentStep === 0 && (
            <div className="space-y-4">
              <h3 className="text-lg font-semibold">Dados Principais</h3>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div className="min-w-0 space-y-2">
                  <Label htmlFor="name">Nome/Razão Social *</Label>
                  <Input
                    id="name"
                    className={cn('h-9 w-full', hasError('name') && 'border-destructive')}
                    {...register('name', { required: 'O nome é obrigatório' })}
                    placeholder="Ex: Empresa Fornecedora Ltda"
                  />
                  {hasError('name') && (
                    <p className="text-sm text-destructive flex items-center gap-1">
                      <AlertCircle className="h-3 w-3" />
                      {getErrorMessage('name')}
                    </p>
                  )}
                </div>

                <div className="min-w-0 space-y-2">
                  <Label htmlFor="fantasy_name">Nome Fantasia</Label>
                  <Input
                    id="fantasy_name"
                    className="h-9 w-full"
                    {...register('fantasy_name')}
                    placeholder="Ex: Fornecedora"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div className="min-w-0 space-y-2">
                  <Label htmlFor="document_type">Tipo de Documento *</Label>
                  <Select
                    value={documentType}
                    onValueChange={(value) => setValue('document_type', value as any)}
                  >
                    <SelectTrigger className={cn('h-9 w-full', hasError('document_type') && 'border-destructive')}>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="cnpj">CNPJ</SelectItem>
                      <SelectItem value="cpf">CPF</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="min-w-0 space-y-2 md:col-span-2">
                  <Label htmlFor="document">{documentType === 'cnpj' ? 'CNPJ' : 'CPF'} *</Label>
                  <div className="relative">
                    <Input
                      id="document"
                      className={cn('h-9 w-full', hasError('document') && 'border-destructive')}
                      value={documentValue || ''}
                      onChange={handleDocumentChange}
                      onBlur={(e) => {
                        void trigger('document')
                        void handleSearchCNPJ(e.target.value)
                      }}
                      placeholder={documentType === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00'}
                      maxLength={documentType === 'cnpj' ? 18 : 14}
                      disabled={loadingCNPJ}
                      name="document"
                    />
                    {loadingCNPJ && (
                      <div className="absolute right-2 top-1/2 -translate-y-1/2">
                        <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                      </div>
                    )}
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {documentType === 'cnpj'
                      ? loadingCNPJ
                        ? 'Consultando Receita Federal...'
                        : 'Digite o CNPJ para buscar dados da empresa'
                      : 'Informe o CPF do fornecedor'}
                  </p>
                  {hasError('document') && (
                    <p className="text-sm text-destructive flex items-center gap-1">
                      <AlertCircle className="h-3 w-3" />
                      {getErrorMessage('document')}
                    </p>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Passo 2: Contato */}
          {currentStep === 1 && (
            <div className="space-y-4">
              <h3 className="text-lg font-semibold flex items-center gap-2">
                <Phone className="h-5 w-5" />
                Contato
              </h3>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div className="min-w-0 space-y-2">
                  <Label htmlFor="phone">Telefone *</Label>
                  <Input
                    id="phone"
                    className={cn('h-9 w-full', hasError('phone') && 'border-destructive')}
                    value={phoneValue || ''}
                    onChange={handlePhoneChange('phone')}
                    onBlur={() => void trigger('phone')}
                    placeholder="(00) 00000-0000"
                    maxLength={15}
                    name="phone"
                  />
                  {hasError('phone') && (
                    <p className="text-sm text-destructive">
                      {getErrorMessage('phone')}
                    </p>
                  )}
                </div>

                <div className="min-w-0 space-y-2">
                  <Label htmlFor="phone2">Telefone 2</Label>
                  <Input
                    id="phone2"
                    className="h-9 w-full"
                    value={phone2Value || ''}
                    onChange={handlePhoneChange('phone2')}
                    placeholder="(00) 00000-0000"
                    maxLength={15}
                  />
                </div>

                <div className="min-w-0 space-y-2">
                  <Label htmlFor="email">E-mail</Label>
                  <Input
                    id="email"
                    type="email"
                    className={cn('h-9 w-full', hasError('email') && 'border-destructive')}
                    {...register('email')}
                    placeholder="contato@fornecedor.com.br"
                  />
                  {hasError('email') && (
                    <p className="text-sm text-destructive">
                      {getErrorMessage('email')}
                    </p>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Passo 3: Endereço */}
          {currentStep === 2 && (
            <div className="space-y-4">
              <h3 className="text-lg font-semibold flex items-center gap-2">
                <MapPin className="h-5 w-5" />
                Endereço <span className="text-sm text-muted-foreground font-normal">(opcional)</span>
              </h3>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div className="min-w-0 space-y-2">
                  <Label htmlFor="zip_code">CEP</Label>
                  <div className="relative">
                    <Input
                      id="zip_code"
                      className="h-9 w-full"
                      value={zipCodeValue || ''}
                      onChange={handleZipCodeChange}
                      onBlur={(e) => {
                        void handleSearchCEP(e.target.value)
                      }}
                      placeholder="00000-000"
                      maxLength={9}
                      disabled={loadingCEP}
                    />
                    {loadingCEP && (
                      <div className="absolute right-2 top-1/2 -translate-y-1/2">
                        <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                      </div>
                    )}
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {loadingCEP ? 'Buscando endereço...' : 'Digite o CEP para preencher automaticamente'}
                  </p>
                </div>

                <div className="min-w-0 space-y-2 md:col-span-2">
                  <Label htmlFor="address">Logradouro</Label>
                  <Input
                    id="address"
                    className="h-9 w-full"
                    {...register('address')}
                    placeholder="Rua, Avenida, etc."
                  />
                </div>

                <div className="min-w-0 space-y-2">
                  <Label htmlFor="number">Número</Label>
                  <Input
                    id="number"
                    className="h-9 w-full"
                    {...register('number')}
                    placeholder="123"
                  />
                </div>

                <div className="min-w-0 space-y-2">
                  <Label htmlFor="complement">Complemento</Label>
                  <Input
                    id="complement"
                    className="h-9 w-full"
                    {...register('complement')}
                    placeholder="Sala, Bloco, etc."
                  />
                </div>

                <div className="min-w-0 space-y-2">
                  <Label htmlFor="neighborhood">Bairro</Label>
                  <Input
                    id="neighborhood"
                    className="h-9 w-full"
                    {...register('neighborhood')}
                    placeholder="Centro"
                  />
                </div>

                <div className="min-w-0 space-y-2 md:col-span-3">
                  <StateCitySelect
                    stateValue={stateValue || ''}
                    cityValue={cityValue || ''}
                    onStateChange={(value) => {
                      setValue('state', value, { shouldDirty: true })
                      setValue('city', '', { shouldDirty: true })
                    }}
                    onCityChange={(value) => setValue('city', value, { shouldDirty: true })}
                  />
                </div>
              </div>
            </div>
          )}

          {/* Passo 4: Financeiro */}
          {currentStep === 3 && (
            <div className="space-y-6">
              <div className="space-y-4">
                <h3 className="text-lg font-semibold flex items-center gap-2">
                  <CreditCard className="h-5 w-5" />
                  Dados Bancários <span className="text-sm text-muted-foreground font-normal">(opcional)</span>
                </h3>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div className="min-w-0 space-y-2">
                    <Label htmlFor="bank_name">Banco</Label>
                    <Input
                      id="bank_name"
                      className="h-9 w-full"
                      {...register('bank_name')}
                      placeholder="Ex: Banco do Brasil"
                    />
                  </div>

                  <div className="min-w-0 space-y-2">
                    <Label htmlFor="bank_agency">Agência</Label>
                    <Input
                      id="bank_agency"
                      className="h-9 w-full"
                      {...register('bank_agency')}
                      placeholder="0000"
                    />
                  </div>

                  <div className="min-w-0 space-y-2">
                    <Label htmlFor="bank_account">Conta</Label>
                    <Input
                      id="bank_account"
                      className="h-9 w-full"
                      {...register('bank_account')}
                      placeholder="00000-0"
                    />
                  </div>

                  <div className="min-w-0 space-y-2">
                    <Label htmlFor="pix_key">Chave PIX</Label>
                    <Input
                      id="pix_key"
                      className="h-9 w-full"
                      {...register('pix_key')}
                      placeholder="CNPJ, email, telefone ou chave aleatória"
                    />
                  </div>
                </div>
              </div>

              <div className="min-w-0 space-y-2">
                <Label htmlFor="notes">Observações</Label>
                <Textarea
                  id="notes"
                  {...register('notes')}
                  placeholder="Informações adicionais sobre o fornecedor..."
                  rows={3}
                  className="resize-none"
                />
              </div>
            </div>
          )}
          </div>

          <DialogFooter className="shrink-0 grid grid-cols-1 gap-2 sm:grid-cols-2">
            {currentStep > 0 ? (
              <Button
                type="button"
                variant="outline"
                className="h-9 w-full sm:justify-self-start"
                onClick={goBack}
                disabled={isLoading}
              >
                <ChevronLeft className="h-4 w-4 mr-1" />
                Voltar
              </Button>
            ) : (
              <Button
                type="button"
                variant="outline"
                className="h-9 w-full sm:justify-self-start"
                onClick={() => onOpenChange(false)}
                disabled={isLoading}
              >
                Cancelar
              </Button>
            )}

            {isLastStep ? (
              <Button
                type="submit"
                className="h-9 w-full sm:justify-self-end"
                disabled={isLoading}
              >
                {isLoading ? 'Salvando...' : supplier ? 'Atualizar' : 'Criar Fornecedor'}
              </Button>
            ) : (
              <Button
                type="button"
                className="h-9 w-full sm:justify-self-end"
                onClick={(e) => {
                  e.preventDefault()
                  e.stopPropagation()
                  void goNext()
                }}
                disabled={isLoading}
              >
                Continuar
                <ChevronRight className="h-4 w-4 ml-1" />
              </Button>
            )}
          </DialogFooter>
        </form>
      </DialogContent>

      <CnpjAutofillConfirmDialog
        open={cnpjAutofillOpen}
        onOpenChange={(nextOpen) => {
          setCnpjAutofillOpen(nextOpen)
          if (!nextOpen) setPendingCompany(null)
        }}
        companyName={pendingCompany?.nome || 'Empresa'}
        onConfirm={handleConfirmCnpjAutofill}
      />
    </Dialog>
  )
}
