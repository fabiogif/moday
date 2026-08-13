"use client"

import { useState, useEffect } from "react"
import { useParams, useRouter } from "next/navigation"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import {
  ArrowLeft,
  Save,
  User,
  MapPin,
  Phone,
  Mail,
  Calendar,
  ShoppingCart,
  Edit,
  Trash2,
} from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Separator } from "@/components/ui/separator"
import { Badge } from "@/components/ui/badge"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { PageLoading } from "@/components/ui/loading-progress"
import { useAuthenticatedApi, useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { toast } from "sonner"
import { useInputMask } from "@/hooks/use-input-mask"
import { validateCPF, validateEmail, validatePhone } from "@/lib/masks"
import { showErrorToast } from "@/components/ui/error-toast"
import { useViaCEP } from "@/hooks/use-viacep"
import { StateCityFormFields } from "@/components/location/state-city-form-fields"
import { applyCepToForm } from "@/lib/apply-cep-to-form"

// Schema de validação
const clientSchema = z.object({
  name: z.string().min(3, "Nome deve ter pelo menos 3 caracteres"),
  cpf: z.string()
    .min(1, { message: "CPF é obrigatório." })
    .refine((value) => validateCPF(value), {
      message: "CPF inválido. Verifique os dígitos.",
    }),
  email: z.string()
    .optional()
    .refine((value) => !value || value === '' || validateEmail(value), {
      message: "Email inválido. Use o formato: exemplo@email.com",
    }),
  phone: z.string()
    .min(1, { message: "Telefone é obrigatório." })
    .refine((value) => validatePhone(value), {
      message: "Telefone inválido. Use (00) 00000-0000",
    }),
  address: z.string().optional(),
  city: z.string().optional(),
  state: z.string().max(2, "Estado deve ter 2 caracteres (UF)").optional(),
  zip_code: z.string().optional(),
  neighborhood: z.string().optional(),
  number: z.string().optional(),
  complement: z.string().optional(),
})

type ClientFormValues = z.infer<typeof clientSchema>

interface Client {
  id: number
  name: string
  cpf: string
  email: string
  phone: string
  address?: string
  city?: string
  state?: string
  zip_code?: string
  neighborhood?: string
  number?: string
  complement?: string
  full_address?: string
  has_complete_address?: boolean
  total_orders: number
  last_order?: string
  last_order_raw?: string
  is_active: boolean
  created_at: string
  created_at_formatted: string
  updated_at: string
}

export default function ClientDetailPage() {
  const params = useParams()
  const router = useRouter()
  const clientId = params.id as string
  
  const [isEditing, setIsEditing] = useState(false)
  const [showDeleteDialog, setShowDeleteDialog] = useState(false)
  
  const { data: client, loading, error, refetch } = useAuthenticatedApi<Client>(
    endpoints.clients.getById(clientId)
  )
  
  const { mutate: updateClient, loading: updating } = useMutation()
  const { mutate: deleteClient, loading: deleting } = useMutation()
  const { loading: loadingCEP, searchCEP } = useViaCEP()
  
  const form = useForm<ClientFormValues>({
    resolver: zodResolver(clientSchema),
    defaultValues: {
      name: "",
      cpf: "",
      email: "",
      phone: "",
      address: "",
      city: "",
      state: "",
      zip_code: "",
      neighborhood: "",
      number: "",
      complement: "",
    },
  })

  const handleSearchCEP = async (cep: string) => {
    const cleanCEP = cep.replace(/\D/g, "")
    if (cleanCEP.length !== 8) return

    const address = await searchCEP(cep)
    if (address) {
      applyCepToForm(form.setValue, address, {
        address: "address",
        neighborhood: "neighborhood",
        state: "state",
        city: "city",
      })
    }
  }
  
  // Atualizar formulário quando cliente carregar
  useEffect(() => {
    if (client) {
      form.reset({
        name: client.name || "",
        cpf: client.cpf || "",
        email: client.email || "",
        phone: client.phone || "",
        address: client.address || "",
        city: client.city || "",
        state: client.state || "",
        zip_code: client.zip_code || "",
        neighborhood: client.neighborhood || "",
        number: client.number || "",
        complement: client.complement || "",
      })
    }
  }, [client, form])
  
  const onSubmit = async (data: ClientFormValues) => {
    try {
      const response = await updateClient(endpoints.clients.update(clientId), 'PUT', data)
      
      if (response) {
        toast.success("Cliente atualizado com sucesso!")
        setIsEditing(false)
        refetch()
      }
    } catch (error: any) {
      toast.error(error.message || "Erro ao atualizar cliente")
    }
  }
  
  const handleDelete = async () => {
    try {
      const response = await deleteClient(endpoints.clients.delete(clientId), 'DELETE')
      
      // Para exclusão, o backend retorna success: true mesmo com data vazia
      if (response !== null) {
        toast.success("Cliente excluído com sucesso!")
        router.push("/clients")
      }
    } catch (error: any) {
      if (error?.status === 409) {
        showErrorToast(error, "Ação não permitida")
        return
      }
      toast.error(error.message || "Erro ao excluir cliente")
    }
  }
  
  if (loading) {
    return <PageLoading />
  }
  
  if (error || !client) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px]">
        <p className="text-muted-foreground mb-4">Cliente não encontrado</p>
        <Button onClick={() => router.push("/clients")} variant="outline">
          <ArrowLeft className="w-4 h-4 mr-2" />
          Voltar para Clientes
        </Button>
      </div>
    )
  }
  
  return (
    <div className="container mx-auto p-6 space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => router.push("/clients")}
          >
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div>
            <h1 className="text-3xl font-bold">{client.name}</h1>
            <p className="text-muted-foreground">Detalhes do Cliente</p>
          </div>
        </div>
        
        <div className="flex gap-2">
          {isEditing ? (
            <>
              <Button
                variant="outline"
                onClick={() => {
                  setIsEditing(false)
                  form.reset()
                }}
              >
                Cancelar
              </Button>
              <Button
                onClick={form.handleSubmit(onSubmit)}
                disabled={updating}
              >
                <Save className="w-4 h-4 mr-2" />
                {updating ? "Salvando..." : "Salvar"}
              </Button>
            </>
          ) : (
            <>
              <Button
                variant="outline"
                onClick={() => setIsEditing(true)}
              >
                <Edit className="w-4 h-4 mr-2" />
                Editar
              </Button>
              <Button
                variant="destructive"
                onClick={() => setShowDeleteDialog(true)}
              >
                <Trash2 className="w-4 h-4 mr-2" />
                Excluir
              </Button>
            </>
          )}
        </div>
      </div>
      
      <Separator />
      
      {/* Cards de Estatísticas */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total de Pedidos</CardTitle>
            <ShoppingCart className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{client.total_orders}</div>
            <p className="text-xs text-muted-foreground">
              Pedidos realizados
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Último Pedido</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {client.last_order || "Nenhum"}
            </div>
            <p className="text-xs text-muted-foreground">
              Data do último pedido
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Status</CardTitle>
            <User className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <Badge variant={client.is_active ? "default" : "secondary"}>
              {client.is_active ? "Ativo" : "Inativo"}
            </Badge>
            <p className="text-xs text-muted-foreground mt-2">
              Cliente desde {client.created_at_formatted}
            </p>
          </CardContent>
        </Card>
      </div>
      
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          {/* Informações Pessoais */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <User className="w-5 h-5" />
                Informações Pessoais
              </CardTitle>
              <CardDescription>
                Dados básicos do cliente
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Nome Completo</FormLabel>
                      <FormControl>
                        <Input {...field} disabled={!isEditing} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="cpf"
                  render={({ field }) => {
                    const handleCPFChange = useInputMask('cpf', field.onChange);
                    
                    return (
                      <FormItem>
                        <FormLabel>CPF</FormLabel>
                        <FormControl>
                          <Input 
                            value={field.value}
                            onChange={handleCPFChange}
                            onBlur={field.onBlur}
                            name={field.name}
                            disabled={!isEditing}
                            placeholder="000.000.000-00"
                            maxLength={14}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    );
                  }}
                />
              </div>
              
              <div className="grid gap-4 md:grid-cols-2">
                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Email</FormLabel>
                      <FormControl>
                        <Input {...field} type="email" disabled={!isEditing} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
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
                            value={field.value}
                            onChange={handlePhoneChange}
                            onBlur={field.onBlur}
                            name={field.name}
                            disabled={!isEditing}
                            placeholder="(00) 00000-0000"
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
          
          {/* Endereço */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <MapPin className="w-5 h-5" />
                Endereço
              </CardTitle>
              <CardDescription>
                Endereço para entrega
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-3">
                <FormField
                  control={form.control}
                  name="zip_code"
                  render={({ field }) => {
                    const handleZipCodeChange = useInputMask('zipCode', field.onChange);
                    
                    return (
                      <FormItem>
                        <FormLabel>CEP</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <Input 
                              value={field.value}
                              onChange={handleZipCodeChange}
                              onBlur={(e) => {
                                field.onBlur()
                                if (isEditing) {
                                  void handleSearchCEP(e.target.value)
                                }
                              }}
                              name={field.name}
                              disabled={!isEditing || loadingCEP}
                              placeholder="00000-000"
                              maxLength={9}
                            />
                            {loadingCEP && (
                              <div className="absolute right-2 top-1/2 -translate-y-1/2">
                                <div className="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                              </div>
                            )}
                          </div>
                        </FormControl>
                        {isEditing && (
                          <p className="text-xs text-muted-foreground mt-1">
                            {loadingCEP ? 'Buscando endereço...' : 'Digite o CEP para preencher automaticamente'}
                          </p>
                        )}
                        <FormMessage />
                      </FormItem>
                    );
                  }}
                />
              </div>

              <div className="grid gap-4 md:grid-cols-3">
                <StateCityFormFields
                  control={form.control}
                  stateFieldName="state"
                  cityFieldName="city"
                  stateLabel="Estado"
                  cityLabel="Cidade"
                  disabled={!isEditing}
                />
              </div>
              
              <div className="grid gap-4 md:grid-cols-3">
                <div className="md:col-span-2">
                  <FormField
                    control={form.control}
                    name="address"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Rua</FormLabel>
                        <FormControl>
                          <Input {...field} disabled={!isEditing} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                </div>
                
                <FormField
                  control={form.control}
                  name="number"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Número</FormLabel>
                      <FormControl>
                        <Input {...field} disabled={!isEditing} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
              
              <div className="grid gap-4 md:grid-cols-2">
                <FormField
                  control={form.control}
                  name="neighborhood"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Bairro</FormLabel>
                      <FormControl>
                        <Input {...field} disabled={!isEditing} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="complement"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Complemento</FormLabel>
                      <FormControl>
                        <Input {...field} disabled={!isEditing} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </CardContent>
          </Card>
        </form>
      </Form>
      
      {/* Dialog de Confirmação de Exclusão */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar Exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir o cliente <strong>{client.name}</strong>?
              Esta ação não pode ser desfeita.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              disabled={deleting}
            >
              {deleting ? "Excluindo..." : "Excluir"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
