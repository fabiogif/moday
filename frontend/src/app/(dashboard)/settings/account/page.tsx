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
import { Separator } from "@/components/ui/separator"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog"
import { useAuth } from "@/contexts/auth-context"
import { apiClient } from "@/lib/api-client"
import { toast } from "sonner"
import { Loader2 } from "lucide-react"

const accountFormSchema = z.object({
  name: z.string().min(1, "Nome é obrigatório"),
  email: z.string().email("Endereço de email inválido"),
  currentPassword: z.string().optional(),
  newPassword: z.string().optional(),
  confirmPassword: z.string().optional(),
}).refine((data) => {
  if (data.newPassword && !data.currentPassword) {
    return false
  }
  return true
}, {
  message: "Senha atual é obrigatória para alterar a senha",
  path: ["currentPassword"],
}).refine((data) => {
  if (data.newPassword && data.newPassword !== data.confirmPassword) {
    return false
  }
  return true
}, {
  message: "Confirmação de senha não confere",
  path: ["confirmPassword"],
})

type AccountFormValues = z.infer<typeof accountFormSchema>

interface UserData {
  id: number
  name: string
  email: string
  is_active: boolean
  tenant_id?: number
  tenant?: {
    id: number
    name: string
    slug: string
    is_active: boolean
  }
  created_at: string
  updated_at: string
}

export default function AccountSettings() {
  const { user, logout } = useAuth()
  const [userData, setUserData] = useState<UserData | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [deleting, setDeleting] = useState(false)

  const form = useForm<AccountFormValues>({
    resolver: zodResolver(accountFormSchema),
    defaultValues: {
      name: "",
      email: "",
      currentPassword: "",
      newPassword: "",
      confirmPassword: "",
    },
  })

  // Carregar dados do usuário
  useEffect(() => {
    const loadUserData = async () => {
      try {
        setLoading(true)
        const response = await apiClient.get('/api/auth/me')
        if (response.success && response.data) {
          const userInfo = response.data as UserData
          setUserData(userInfo)
          
          // Preencher formulário com dados do usuário
          form.reset({
            name: userInfo.name,
            email: userInfo.email,
            currentPassword: "",
            newPassword: "",
            confirmPassword: "",
          })
        }
      } catch (error) {
        console.error('Erro ao carregar dados do usuário:', error)
        toast.error('Erro ao carregar informações da conta')
      } finally {
        setLoading(false)
      }
    }

    loadUserData()
  }, [form])

  async function onSubmit(data: AccountFormValues) {
    try {
      setSaving(true)
      
      // Preparar dados para envio
      const updateData: any = {
        name: data.name,
        email: data.email,
      }
      
      // Incluir nova senha apenas se fornecida
      if (data.newPassword) {
        updateData.current_password = data.currentPassword
        updateData.new_password = data.newPassword
        updateData.new_password_confirmation = data.confirmPassword
      }
      
      const response = await apiClient.put('/api/auth/profile', updateData)
      
      if (response.success) {
        toast.success('Informações da conta atualizadas com sucesso')
        
        // Atualizar dados locais
        if (response.data) {
          setUserData(response.data as UserData)
        }
      }
    } catch (error: any) {
      console.error('Erro ao atualizar conta:', error)
      toast.error(error.message || 'Erro ao atualizar informações da conta')
    } finally {
      setSaving(false)
    }
  }

  async function handleDeleteAccount() {
    try {
      setDeleting(true)
      
      // Aqui você implementaria a chamada para deletar a conta
      // const response = await apiClient.delete('/api/auth/account')
      
      // Por enquanto, vamos simular o processo
      await new Promise(resolve => setTimeout(resolve, 2000))
      
      toast.success('Conta removida com sucesso')
      
      // Fazer logout após remoção
      await logout()
      
    } catch (error: any) {
      console.error('Erro ao remover conta:', error)
      toast.error(error.message || 'Erro ao remover conta')
    } finally {
      setDeleting(false)
    }
  }

  if (loading) {
    return (
      <div className="space-y-6 px-4 lg:px-6">
        <div>
          <h1 className="text-3xl font-bold">Configurações de Conta</h1>
          <p className="text-muted-foreground">
            Gerencie as configurações e preferências da sua conta.
          </p>
        </div>
        <div className="flex items-center justify-center py-8">
          <Loader2 className="h-8 w-8 animate-spin" />
          <span className="ml-2">Carregando informações da conta...</span>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6 px-4 lg:px-6">
        <div>
          <h1 className="text-3xl font-bold">Configurações de Conta</h1>
          <p className="text-muted-foreground">
            Gerencie as configurações e preferências da sua conta.
          </p>
        </div>

        {/* Informações da conta */}
        {userData && (
          <Card>
            <CardHeader>
              <CardTitle>Informações da Conta</CardTitle>
              <CardDescription>Dados atuais da sua conta</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Nome</label>
                  <p className="text-sm">{userData.name}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Email</label>
                  <p className="text-sm">{userData.email}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Status</label>
                  <p className="text-sm">{userData.is_active ? 'Ativo' : 'Inativo'}</p>
                </div>
                {userData.tenant && (
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">1Empresa</label>
                    <p className="text-sm">{userData.tenant.name}</p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        )}

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Informações pessoais</CardTitle>
                <CardDescription>
                Atualize suas informações pessoais que serão exibidas em seu perfil.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Nome Completo</FormLabel>
                      <FormControl>
                        <Input placeholder="Digite seu nome completo" {...field} />
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
                      <FormLabel>Endereço de email</FormLabel>
                      <FormControl>
                        <Input type="email" placeholder="Digite seu e-mail" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Alterar a senha</CardTitle>
                <CardDescription>
                Atualize sua senha para manter sua conta segura.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <FormField
                  control={form.control}
                  name="currentPassword"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Senha atual</FormLabel>
                      <FormControl>
                        <Input type="password" placeholder="Digite sua senha atual" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="newPassword"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Nova senha</FormLabel>
                      <FormControl>
                        <Input type="password" placeholder="Digite sua nova senha" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="confirmPassword"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Confirme nova senha</FormLabel>
                      <FormControl>
                        <Input type="password" placeholder="Confirme nova senha" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </CardContent>
            </Card>

            <Card>
              <CardContent className="space-y-4">
                <Separator />
                <div className="flex flex-wrap gap-2 items-center justify-between">
                  <div>
                    <h4 className="font-semibold">Remover conta</h4>
                    <p className="text-sm text-muted-foreground">
                      Remova permanentemente sua conta e todos os dados associados.
                    </p>
                  </div>
                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <Button variant="destructive" type="button" className="cursor-pointer">
                        Remover Conta
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Remover Conta</AlertDialogTitle>
                        <AlertDialogDescription>
                          Esta ação não pode ser desfeita. Isso removerá permanentemente sua conta
                          e todos os dados associados de nossos servidores.
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                          onClick={handleDeleteAccount}
                          disabled={deleting}
                        >
                          {deleting ? (
                            <>
                              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                              Removendo...
                            </>
                          ) : (
                            'Continuar'
                          )}
                        </AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>
                </div>
              </CardContent>
            </Card>

            <div className="flex space-x-2">
              <Button type="submit" className="cursor-pointer" disabled={saving}>
                {saving ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Salvando...
                  </>
                ) : (
                  'Salvar alterações'
                )}
              </Button>
              <Button variant="outline" type="reset" className="cursor-pointer" disabled={saving}>
                Cancelar
              </Button>
            </div>
          </form>
        </Form>
      </div>
  )
}
