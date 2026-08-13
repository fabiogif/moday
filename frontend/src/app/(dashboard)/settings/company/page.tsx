"use client"

import { zodResolver } from "@hookform/resolvers/zod"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { useEffect, useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Button } from "@/components/ui/button"
import { useAuth } from "@/contexts/auth-context"
import { apiClient, endpoints } from "@/lib/api-client"
import { toast } from "sonner"
import { Loader2, Building2, Upload, X, ExternalLink, Copy, MapPin, ChevronLeft, ChevronRight } from "lucide-react"
import Image from "next/image"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { useBackendValidation, commonFieldMappings } from "@/hooks/use-backend-validation"
import { useInputMask } from "@/hooks/use-input-mask"
import { validateCNPJ, validateEmail, validatePhone } from "@/lib/masks"
import { useViaCEP } from "@/hooks/use-viacep"
import { useReceitaWS } from "@/hooks/use-receitaws"
import { type CompanyData } from "@/services/receitaws"
import { AlertCircle, CheckCircle2 } from "lucide-react"
import { StateCityFormFields } from "@/components/location/state-city-form-fields"
import { applyCepToForm } from "@/lib/apply-cep-to-form"
import { PlansSection } from "./components/plans-section"
import { resolveImageUrl } from "@/lib/resolve-image-url"
import { OrderStepper } from "@/components/order-stepper"
import { extractValidationErrors } from "@/lib/error-formatter"
import { CnpjAutofillConfirmDialog } from "@/components/cnpj-autofill-confirm-dialog"

const companyFormSchema = z.object({
  name: z.string().min(1, "Nome da empresa é obrigatório"),
  email: z.string()
    .min(1, "Email é obrigatório")
    .refine((value) => validateEmail(value), {
      message: "Email inválido. Use o formato: exemplo@email.com",
    }),
  cnpj: z.string()
    .optional()
    .refine((value) => !value || value === '' || validateCNPJ(value), {
      message: "CNPJ inválido. Verifique os dígitos.",
    }),
  phone: z.string()
    .optional()
    .refine((value) => !value || value === '' || validatePhone(value), {
      message: "Telefone inválido. Use (00) 00000-0000",
    }),
  address: z.string().optional(),
  city: z.string().optional(),
  state: z.string().max(2, "Estado deve ter no máximo 2 caracteres (UF)").optional(),
  zipcode: z.string().optional(),
  country: z.string().optional(),
})

type CompanyFormValues = z.infer<typeof companyFormSchema>

const STEPS = [
  { label: "Logo", icon: Upload },
  { label: "Dados da Empresa", icon: Building2 },
  { label: "Endereço", icon: MapPin },
]

const STEP_FIELDS: (keyof CompanyFormValues)[][] = [
  [],
  ["name", "email", "cnpj", "phone"],
  ["address", "city", "state", "zipcode", "country"],
]

const STEP_PAYLOAD_FIELDS: (keyof CompanyFormValues)[][] = [
  [],
  ["name", "email", "cnpj", "phone"],
  ["address", "city", "state", "zipcode", "country"],
]

const FIELD_STEP: Partial<Record<keyof CompanyFormValues, number>> = {
  name: 1,
  email: 1,
  cnpj: 1,
  phone: 1,
  address: 2,
  city: 2,
  state: 2,
  zipcode: 2,
  country: 2,
}

function isFilled(value: unknown) {
  return typeof value === "string" ? value.trim().length > 0 : value != null && value !== ""
}

interface TenantData {
  id: number
  uuid: string
  name: string
  slug: string
  email: string
  cnpj?: string
  phone?: string
  address?: string
  city?: string
  state?: string
  zipcode?: string
  country?: string
  logo?: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export default function CompanySettings() {
  const { user } = useAuth()
  const [tenantData, setTenantData] = useState<TenantData | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [logoFile, setLogoFile] = useState<File | null>(null)
  const [logoPreview, setLogoPreview] = useState<string | null>(null)
  const [removeLogo, setRemoveLogo] = useState(false)
  const [currentStep, setCurrentStep] = useState(0)
  const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set())
  const [validatingStep, setValidatingStep] = useState(false)
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})
  const [cnpjAutofillOpen, setCnpjAutofillOpen] = useState(false)
  const [pendingCompany, setPendingCompany] = useState<CompanyData | null>(null)

  const form = useForm<CompanyFormValues>({
    resolver: zodResolver(companyFormSchema),
    defaultValues: {
      name: "",
      email: "",
      cnpj: "",
      phone: "",
      address: "",
      city: "",
      state: "",
      zipcode: "",
      country: "",
    },
  })

  const { handleBackendErrors } = useBackendValidation(form.setError)

  const name = form.watch("name")
  const email = form.watch("email")

  const canContinue =
    currentStep === 1 ? isFilled(name) && isFilled(email) : true

  const applyBackendErrors = (error: unknown) => {
    const validationErrors = extractValidationErrors(error)
    setBackendErrors(validationErrors)

    Object.entries(validationErrors).forEach(([field, message]) => {
      if (field === "_general") return
      if (field in FIELD_STEP) {
        form.setError(field as keyof CompanyFormValues, {
          type: "server",
          message,
        })
      }
    })

    const errorCount = Object.keys(validationErrors).filter((k) => k !== "_general").length
    if (validationErrors._general) {
      toast.error(validationErrors._general)
    } else if (errorCount > 0) {
      const firstError = Object.values(validationErrors)[0]
      toast.error(firstError, {
        description: errorCount > 1 ? `${errorCount - 1} outro(s) erro(s)` : undefined,
      })
    }

    return validationErrors
  }

  const validateStepOnBackend = async (step: number) => {
    const values = form.getValues()
    const payload: Record<string, unknown> = {
      step,
      uuid: tenantData?.uuid,
    }

    STEP_PAYLOAD_FIELDS[step].forEach((field) => {
      payload[field] = values[field] ?? ""
    })

    await apiClient.post(endpoints.tenant.validate, payload)
  }

  const goNext = async () => {
    setBackendErrors({})
    form.clearErrors()

    const fields = STEP_FIELDS[currentStep]
    const frontValid = fields.length === 0 || (await form.trigger(fields))
    if (!frontValid) return

    setValidatingStep(true)
    try {
      await validateStepOnBackend(currentStep)
      setCompletedSteps((prev) => new Set(prev).add(currentStep))
      setCurrentStep((s) => Math.min(s + 1, STEPS.length - 1))
    } catch (error: any) {
      applyBackendErrors(error)
    } finally {
      setValidatingStep(false)
    }
  }

  const goBack = () => setCurrentStep((s) => Math.max(s - 1, 0))

  const goToStep = (step: number) => {
    if (step <= currentStep || completedSteps.has(step)) {
      setCurrentStep(step)
    }
  }
  const { loading: loadingCEP, searchCEP } = useViaCEP()
  const { loading: loadingCNPJ, companyData, searchCNPJ } = useReceitaWS()
  
  // Função para buscar endereço pelo CEP
  const handleSearchCEP = async (cep: string) => {
    const cleanCEP = cep.replace(/\D/g, '');
    
    if (cleanCEP.length !== 8) {
      return;
    }
    
    const address = await searchCEP(cep);
    
    if (address) {
      applyCepToForm(form.setValue, address, {
        address: 'address',
        state: 'state',
        city: 'city',
      })
    }
  }
  
  // Função para buscar dados da empresa pelo CNPJ
  const applyCompanyAutofill = (company: CompanyData) => {
    if (company.nome && !form.getValues('name')) {
      form.setValue('name', company.nome)
    }
    if (company.email && !form.getValues('email')) {
      form.setValue('email', company.email)
    }
    if (company.phone && !form.getValues('phone')) {
      form.setValue('phone', company.phone)
    }

    if (company.address) form.setValue('address', company.address)
    if (company.city) form.setValue('city', company.city)
    if (company.state) form.setValue('state', company.state)
    if (company.zipCode) form.setValue('zipcode', company.zipCode)

    toast.success('Dados da empresa preenchidos automaticamente!')
  }

  const handleConfirmCnpjAutofill = () => {
    if (pendingCompany) {
      applyCompanyAutofill(pendingCompany)
    }
    setPendingCompany(null)
    setCnpjAutofillOpen(false)
  }

  const handleSearchCNPJ = async (cnpj: string) => {
    const cleanCNPJ = cnpj.replace(/\D/g, '');
    
    if (cleanCNPJ.length !== 14) {
      return; // CNPJ incompleto
    }
    
    const company = await searchCNPJ(cnpj);
    
    if (company) {
      setPendingCompany(company)
      setCnpjAutofillOpen(true)
    }
  }

  // Carregar dados da empresa
  useEffect(() => {
    const loadTenantData = async () => {
      try {
        setLoading(true)
        
        // Primeiro, obter os dados do usuário para pegar o tenant_id
        const userResponse = await apiClient.get('/api/auth/me')
        
        if (userResponse.success && userResponse.data) {
          const userData = userResponse.data as any
          
          if (userData.tenant) {
            const tenant = userData.tenant
            
            // Buscar dados completos do tenant
            const tenantResponse = await apiClient.get(`/api/tenant/${tenant.uuid}`)
            
            if (tenantResponse.success && tenantResponse.data) {
              const tenantInfo = tenantResponse.data as TenantData
              setTenantData(tenantInfo)
              
              // Preencher formulário com dados do tenant
              form.reset({
                name: tenantInfo.name || "",
                email: tenantInfo.email || "",
                cnpj: tenantInfo.cnpj || "",
                phone: tenantInfo.phone || "",
                address: tenantInfo.address || "",
                city: tenantInfo.city || "",
                state: tenantInfo.state || "",
                zipcode: tenantInfo.zipcode || "",
                country: tenantInfo.country || "",
              })
            }
          }
        }
      } catch (error) {

        toast.error('Erro ao carregar informações da empresa')
      } finally {
        setLoading(false)
      }
    }

    loadTenantData()
  }, [form])

  // Manipular seleção de logo
  const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      // Validar tamanho (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        toast.error('O arquivo deve ter no máximo 5MB')
        return
      }

      // Validar tipo
      if (!file.type.startsWith('image/')) {
        toast.error('Por favor, selecione uma imagem válida')
        return
      }

      setLogoFile(file)
      setRemoveLogo(false)
      
      // Criar preview
      const reader = new FileReader()
      reader.onloadend = () => {
        setLogoPreview(reader.result as string)
      }
      reader.readAsDataURL(file)
    }
  }

  // Remover logo
  const handleRemoveLogo = () => {
    setLogoFile(null)
    setLogoPreview(null)
    setRemoveLogo(true)
    toast.success('Logo marcado para remoção')
  }

  // Cancelar remoção
  const handleCancelRemove = () => {
    setRemoveLogo(false)
    toast.success('Remoção de logo cancelada')
  }

  // Copiar slug/URL da loja
  const handleCopySlug = (slug: string) => {
    navigator.clipboard.writeText(slug)
    toast.success('Slug copiado para a área de transferência')
  }

  const handleCopyStoreUrl = (slug: string) => {
    const storeUrl = `${window.location.origin}/store/${slug}`
    navigator.clipboard.writeText(storeUrl)
    toast.success('URL da loja copiada para a área de transferência')
  }

  async function onSubmit(data: CompanyFormValues) {
    if (!tenantData?.uuid) {
      toast.error('Dados da empresa não encontrados')
      return
    }

    setBackendErrors({})
    form.clearErrors()

    // Valida o último passo no backend antes de salvar
    try {
      setValidatingStep(true)
      await validateStepOnBackend(STEPS.length - 1)
      setCompletedSteps((prev) => new Set(prev).add(STEPS.length - 1))
    } catch (error: any) {
      const validationErrors = applyBackendErrors(error)
      const firstErrorField = Object.keys(validationErrors).find(
        (k) => k !== '_general'
      ) as keyof CompanyFormValues | undefined
      const stepWithError = firstErrorField ? FIELD_STEP[firstErrorField] : undefined
      if (stepWithError !== undefined) {
        setCurrentStep(stepWithError)
      }
      setValidatingStep(false)
      return
    }
    setValidatingStep(false)

    try {
      setSaving(true)

      // Preparar FormData se houver arquivo
      let response
      if (logoFile || removeLogo) {
        const formData = new FormData()
        
        // Adicionar _method para Laravel reconhecer como PUT
        formData.append('_method', 'PUT')
        
        // Adicionar campos do formulário
        Object.entries(data).forEach(([key, value]) => {
          if (value !== undefined && value !== null && value !== '') {
            formData.append(key, value.toString())
          }
        })
        
        // Adicionar arquivo se houver
        if (logoFile) {
          formData.append('logo', logoFile)
        }
        
        // Marcar para remover logo se necessário
        if (removeLogo && !logoFile) {
          formData.append('remove_logo', 'true')
        }
        
        // Usar POST com _method=PUT para upload de arquivo
        response = await apiClient.post(`/api/tenant/${tenantData.uuid}`, formData)
      } else {
        // Enviar como JSON normal com PUT
        response = await apiClient.put(`/api/tenant/${tenantData.uuid}`, data)
      }
      
      if (response.success) {
        toast.success('Informações da empresa atualizadas com sucesso')
        
        // Atualizar dados locais
        if (response.data) {
          setTenantData(response.data as TenantData)
          setLogoFile(null)
          setLogoPreview(null)
          setRemoveLogo(false)
        }
      }
    } catch (error: any) {

      // Se houver erros de validação, mostrar no console
      if (error.data?.data) {

        Object.entries(error.data.data).forEach(([field, messages]) => {

        })
      }
      
      // Mapeamento de campos específicos para empresa
      const companyFieldMappings: Record<string, string> = {
        'name': 'name',
        'email': 'email',
        'phone': 'phone',
        'cnpj': 'cnpj',
        'address': 'address',
        'city': 'city',
        'state': 'state',
        'zipcode': 'zipcode',
        'country': 'country',
      }
      
      const handled = handleBackendErrors(error, companyFieldMappings as any)

      const validationErrors = extractValidationErrors(error)
      const firstErrorField = Object.keys(validationErrors).find(
        (k) => k !== '_general'
      ) as keyof CompanyFormValues | undefined
      const stepWithError = firstErrorField ? FIELD_STEP[firstErrorField] : undefined
      if (stepWithError !== undefined) {
        setCurrentStep(stepWithError)
      }

      if (!handled) {
        const errorMsg = error.data?.message || error.message || 'Erro ao atualizar empresa'
        toast.error(errorMsg)
      }
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return (
      <div className="space-y-6 px-4 lg:px-6">
        <div>
          <h1 className="text-3xl font-bold">Configurações da Empresa</h1>
          <p className="text-muted-foreground">
            Gerencie as informações da sua empresa.
          </p>
        </div>
        <div className="flex items-center justify-center py-8">
          <Loader2 className="h-8 w-8 animate-spin" />
          <span className="ml-2">Carregando informações da empresa...</span>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6 px-4 lg:px-6">
      <div>
        <h1 className="text-3xl font-bold">Configurações da Empresa</h1>
        <p className="text-muted-foreground">
          Gerencie as informações da sua empresa.
        </p>
      </div>

      {/* Informações da empresa */}
      {tenantData && (
        <Card>
          <CardHeader>
            <CardTitle>Informações Atuais</CardTitle>
            <CardDescription>Dados cadastrados da empresa</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center gap-4 mb-6">
              {tenantData.logo ? (
                <Image 
                  src={resolveImageUrl(tenantData.logo) || ""} 
                  alt={tenantData.name} 
                  width={80} 
                  height={80} 
                  className="rounded-lg object-cover border"
                  unoptimized
                />
              ) : (
                <div className="w-20 h-20 rounded-lg bg-muted flex items-center justify-center border">
                  <Building2 className="h-10 w-10 text-muted-foreground" />
                </div>
              )}
              <div className="flex-1">
                <h3 className="text-xl font-semibold">{tenantData.name}</h3>
                <p className="text-sm text-muted-foreground">{tenantData.email}</p>
                <div className="flex items-center gap-2 mt-2">
                  <Badge variant="secondary" className="font-mono text-xs">
                    {tenantData.slug}
                  </Badge>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => handleCopySlug(tenantData.slug)}
                    className="h-6 w-6 p-0"
                    title="Copiar slug"
                  >
                    <Copy className="h-3 w-3" />
                  </Button>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => window.open(`/store/${tenantData.slug}`, '_blank')}
                    className="h-6 w-6 p-0"
                    title="Abrir loja pública"
                  >
                    <ExternalLink className="h-3 w-3" />
                  </Button>
                </div>
              </div>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="text-sm font-medium text-muted-foreground">URL da Loja</label>
                <div className="flex items-center gap-2 mt-1">
                  <code className="text-xs bg-muted px-2 py-1 rounded">
                    /store/{tenantData.slug}
                  </code>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => handleCopyStoreUrl(tenantData.slug)}
                    className="h-7 px-2"
                  >
                    <Copy className="h-3 w-3 mr-1" />
                    Copiar
                  </Button>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => window.open(`/store/${tenantData.slug}`, '_blank')}
                    className="h-7 px-2"
                  >
                    <ExternalLink className="h-3 w-3 mr-1" />
                    Abrir
                  </Button>
                </div>
              </div>
              {tenantData.cnpj && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">CNPJ</label>
                  <p className="text-sm">{tenantData.cnpj}</p>
                </div>
              )}
              {tenantData.phone && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Telefone</label>
                  <p className="text-sm">{tenantData.phone}</p>
                </div>
              )}
              <div>
                <label className="text-sm font-medium text-muted-foreground">Status</label>
                <p className="text-sm">{tenantData.is_active ? 'Ativo' : 'Inativo'}</p>
              </div>
              {tenantData.city && tenantData.state && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Localização</label>
                  <p className="text-sm">{tenantData.city}/{tenantData.state}</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      )}

      <div className="px-1">
        <OrderStepper
          currentStep={currentStep}
          steps={STEPS}
          onStepClick={goToStep}
          completedSteps={completedSteps}
        />
      </div>

      {backendErrors._general && (
        <div className="flex items-center gap-2 rounded-md border border-destructive/50 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          <AlertCircle className="h-4 w-4 shrink-0" />
          {backendErrors._general}
        </div>
      )}

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          {/* Card de Upload de Logo */}
          {currentStep === 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Logo da Empresa</CardTitle>
              <CardDescription>
                Faça upload do logo da sua empresa. Recomendamos uma imagem quadrada de pelo menos 200x200px.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex flex-col md:flex-row gap-6 items-start">
                {/* Preview do Logo */}
                <div className="flex flex-col items-center gap-3">
                  <div className="w-32 h-32 rounded-lg border-2 border-dashed border-muted-foreground/25 flex items-center justify-center overflow-hidden bg-muted/30">
                    {logoPreview ? (
                      <Image 
                        src={logoPreview} 
                        alt="logo padrão" 
                        width={128} 
                        height={128} 
                        className="object-cover w-full h-full"
                      />
                    ) : tenantData?.logo && !removeLogo ? (
                      <Image 
                        src={resolveImageUrl(tenantData.logo) || ""} 
                        alt="Logo atual" 
                        width={128} 
                        height={128} 
                        className="object-cover w-full h-full"
                        unoptimized
                      />
                    ) : (
                      <div className="flex flex-col items-center justify-center text-muted-foreground">
                        <Building2 className="h-12 w-12 mb-2" />
                        <span className="text-xs">Sem logo</span>
                      </div>
                    )}
                  </div>
                  {(tenantData?.logo || logoPreview) && !removeLogo && (
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={handleRemoveLogo}
                      className="text-destructive hover:text-destructive"
                    >
                      <X className="h-4 w-4 mr-1" />
                      Remover Logo
                    </Button>
                  )}
                  {removeLogo && (
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={handleCancelRemove}
                    >
                      Cancelar Remoção
                    </Button>
                  )}
                </div>

                {/* Upload Input */}
                <div className="flex-1 space-y-3">
                  <div>
                    <Label htmlFor="logo-upload" className="cursor-pointer">
                      <div className="border-2 border-dashed rounded-lg p-6 hover:border-primary/50 transition-colors">
                        <div className="flex flex-col items-center justify-center gap-2 text-center">
                          <Upload className="h-8 w-8 text-muted-foreground" />
                          <div className="space-y-1">
                            <p className="text-sm font-medium">
                              Clique para fazer upload ou arraste uma imagem
                            </p>
                            <p className="text-xs text-muted-foreground">
                              PNG, JPG ou WEBP (máx. 5MB)
                            </p>
                          </div>
                        </div>
                      </div>
                      <Input
                        id="logo-upload"
                        type="file"
                        accept="image/png,image/jpeg,image/jpg,image/webp"
                        onChange={handleLogoChange}
                        className="hidden"
                      />
                    </Label>
                  </div>
                  {logoFile && (
                    <div className="bg-muted/50 rounded-lg p-3">
                      <p className="text-sm font-medium">Arquivo selecionado:</p>
                      <p className="text-xs text-muted-foreground mt-1">
                        {logoFile.name} ({(logoFile.size / 1024).toFixed(2)} KB)
                      </p>
                    </div>
                  )}
                  {removeLogo && (
                    <div className="bg-destructive/10 border border-destructive/30 rounded-lg p-3">
                      <p className="text-sm font-medium text-destructive">
                        O logo será removido ao salvar as alterações
                      </p>
                    </div>
                  )}
                </div>
              </div>
            </CardContent>
          </Card>
          )}

          {currentStep === 1 && (
          <Card>
            <CardHeader>
              <CardTitle>Informações da Empresa</CardTitle>
              <CardDescription>
                Atualize as informações cadastrais da sua empresa.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Nome da Empresa</FormLabel>
                      <FormControl>
                        <Input placeholder="Digite o nome da empresa" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Email</FormLabel>
                      <FormControl>
                        <Input type="email" placeholder="email@empresa.com" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="cnpj"
                  render={({ field }) => {
                    const handleCNPJChange = useInputMask('cnpj', field.onChange);
                    
                    return (
                      <FormItem>
                        <FormLabel>CNPJ</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <Input 
                              placeholder="00.000.000/0000-00" 
                              value={field.value}
                              onChange={handleCNPJChange}
                              onBlur={(e) => {
                                field.onBlur();
                                handleSearchCNPJ(e.target.value);
                              }}
                              name={field.name}
                              maxLength={18}
                              disabled={loadingCNPJ}
                            />
                            {loadingCNPJ && (
                              <div className="absolute right-2 top-1/2 -translate-y-1/2">
                                <div className="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                              </div>
                            )}
                          </div>
                        </FormControl>
                        <p className="text-xs text-muted-foreground mt-1">
                          {loadingCNPJ ? 'Consultando Receita Federal...' : 'Digite o CNPJ para buscar dados da empresa'}
                        </p>
                        {companyData && (
                          <div className="flex items-start gap-2 mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md">
                            <CheckCircle2 className="h-4 w-4 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" />
                            <div className="text-xs space-y-0.5">
                              <p className="font-medium text-green-900 dark:text-green-100">{companyData.nome}</p>
                              <p className="text-green-700 dark:text-green-300">Situação: {companyData.situacao}</p>
                              {companyData.nomeFantasia && (
                                <p className="text-green-700 dark:text-green-300">Nome Fantasia: {companyData.nomeFantasia}</p>
                              )}
                            </div>
                          </div>
                        )}
                        <FormMessage />
                      </FormItem>
                    );
                  }}
                />
                <FormField
                  control={form.control}
                  name="phone"
                  render={({ field }) => {
                    const handlePhoneChange = useInputMask('phone', field.onChange);
                    
                    return (
                      <FormItem>
                        <FormLabel>Telefone</FormLabel>
                        <FormControl>
                          <Input 
                            placeholder="(00) 00000-0000" 
                            value={field.value}
                            onChange={handlePhoneChange}
                            onBlur={field.onBlur}
                            name={field.name}
                            maxLength={15}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    );
                  }}
                />
              </div>
            </CardContent>
          </Card>
          )}

          {currentStep === 2 && (
          <Card>
            <CardHeader>
              <CardTitle>Endereço</CardTitle>
              <CardDescription>
                Informações de localização da empresa.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <FormField
                control={form.control}
                name="address"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Endereço</FormLabel>
                    <FormControl>
                      <Input placeholder="Rua, número, complemento" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              
              {/* Estado e Cidade */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <StateCityFormFields
                  control={form.control}
                  stateFieldName="state"
                  cityFieldName="city"
                  stateLabel="Estado"
                  cityLabel="Cidade"
                />
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <FormField
                  control={form.control}
                  name="zipcode"
                  render={({ field }) => {
                    const handleZipCodeChange = useInputMask('zipCode', field.onChange);
                    
                    return (
                      <FormItem>
                        <FormLabel>CEP</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <Input 
                              placeholder="00000-000" 
                              value={field.value}
                              onChange={handleZipCodeChange}
                              onBlur={(e) => {
                                field.onBlur();
                                handleSearchCEP(e.target.value);
                              }}
                              name={field.name}
                              maxLength={9}
                              disabled={loadingCEP}
                            />
                            {loadingCEP && (
                              <div className="absolute right-2 top-1/2 -translate-y-1/2">
                                <div className="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                              </div>
                            )}
                          </div>
                        </FormControl>
                        <p className="text-xs text-muted-foreground mt-1">
                          {loadingCEP ? 'Buscando endereço...' : 'Digite o CEP para preencher automaticamente'}
                        </p>
                        <FormMessage />
                      </FormItem>
                    );
                  }}
                />
              </div>
              <FormField
                control={form.control}
                name="country"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>País</FormLabel>
                    <FormControl>
                      <Input placeholder="Brasil" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </CardContent>
          </Card>
          )}

          <div className="sticky bottom-0 bg-background border-t pt-3 pb-4 mt-4 -mx-4 px-4 lg:-mx-6 lg:px-6 flex items-center justify-between gap-2">
            {currentStep > 0 ? (
              <Button
                type="button"
                variant="outline"
                className="cursor-pointer"
                onClick={goBack}
                disabled={saving || validatingStep}
              >
                <ChevronLeft className="h-4 w-4 mr-1" />
                Voltar
              </Button>
            ) : (
              <Button
                variant="outline"
                type="button"
                className="cursor-pointer"
                disabled={saving || validatingStep}
                onClick={() => {
                  form.reset()
                  setCurrentStep(0)
                  setCompletedSteps(new Set())
                  setBackendErrors({})
                }}
              >
                Cancelar
              </Button>
            )}

            {currentStep === STEPS.length - 1 ? (
              <Button type="submit" className="cursor-pointer" disabled={saving || validatingStep}>
                {saving || validatingStep ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    {validatingStep ? 'Validando...' : 'Salvando...'}
                  </>
                ) : (
                  'Salvar alterações'
                )}
              </Button>
            ) : (
              <Button
                type="button"
                className="cursor-pointer"
                onClick={goNext}
                disabled={saving || validatingStep || !canContinue}
              >
                {validatingStep ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Validando...
                  </>
                ) : (
                  <>
                    Continuar
                    <ChevronRight className="h-4 w-4 ml-1" />
                  </>
                )}
              </Button>
            )}
          </div>
        </form>
      </Form>

      {/* Seção de Planos */}
      <Card id="planos" className="scroll-mt-20">
        <CardHeader>
          <CardTitle>Planos e Limites</CardTitle>
          <CardDescription>
            Gerencie seu plano atual e migre para planos superiores quando necessário.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <PlansSection />
        </CardContent>
      </Card>

      <CnpjAutofillConfirmDialog
        open={cnpjAutofillOpen}
        onOpenChange={(nextOpen) => {
          setCnpjAutofillOpen(nextOpen)
          if (!nextOpen) setPendingCompany(null)
        }}
        companyName={pendingCompany?.nome || 'Empresa'}
        onConfirm={handleConfirmCnpjAutofill}
      />
    </div>
  )
}
