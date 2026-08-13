"use client"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Plus } from "lucide-react"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"
import { Switch } from "@/components/ui/switch"

const userFormSchema = z.object({
  name: z.string().min(2, {
    message: "O nome deve ter pelo menos 2 caracteres.",
  }),
  email: z.string().email({
    message: "Digite um endereço de e-mail válido.",
  }),
  password: z.string().min(6, {
    message: "A senha deve ter pelo menos 6 caracteres.",
  }),
  password_confirmation: z.string().min(6, {
    message: "A confirmação da senha deve ter pelo menos 6 caracteres.",
  }),
  phone: z.string().optional(),
  is_active: z.boolean().default(true),
}).refine((data) => data.password === data.password_confirmation, {
  message: "As senhas não coincidem.",
  path: ["password_confirmation"],
})

type UserFormValues = z.infer<typeof userFormSchema>

interface UserFormDialogProps {
  onAddUser: (user: UserFormValues) => void
  onEditUser?: (id: number, user: UserFormValues) => void
  editingUser?: User | null
  children?: React.ReactNode
  open?: boolean
  onOpenChange?: (open: boolean) => void
}

interface User {
  id: number
  name: string
  email: string
  phone?: string
  avatar?: string
  is_active: boolean
  created_at: string
  updated_at: string
  profiles?: any[]
}

export function UserFormDialog({ onAddUser, onEditUser, editingUser, children, open: controlledOpen, onOpenChange }: UserFormDialogProps) {
  const [internalOpen, setInternalOpen] = useState(false)
  const [passwordMismatch, setPasswordMismatch] = useState(false)
  const isEditing = !!editingUser
  
  // Use controlled or internal state
  const open = controlledOpen !== undefined ? controlledOpen : internalOpen
  const setOpen = onOpenChange || setInternalOpen

  const form = useForm({
    resolver: zodResolver(userFormSchema),
    defaultValues: {
      name: "",
      email: "",
      password: "",
      password_confirmation: "",
      phone: "",
      is_active: true,
    },
  })

  // Monitorar mudanças nas senhas para validação em tempo real
  const password = form.watch("password")
  const passwordConfirmation = form.watch("password_confirmation")

  useEffect(() => {
    if (password && passwordConfirmation) {
      setPasswordMismatch(password !== passwordConfirmation)
    } else {
      setPasswordMismatch(false)
    }
  }, [password, passwordConfirmation])

  // Verificar se o formulário está válido para habilitar o botão
  const isFormValid = !passwordMismatch && password && passwordConfirmation && password.length >= 6

  // Preencher formulário quando estiver editando
  useEffect(() => {
    if (editingUser) {
      form.reset({
        name: editingUser.name,
        email: editingUser.email,
        password: "",
        password_confirmation: "",
        phone: editingUser.phone || "",
        is_active: editingUser.is_active,
      })
    } else {
      form.reset({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        phone: "",
        is_active: true,
      })
    }
  }, [editingUser, form])

  function onSubmit(data: UserFormValues) {
    if (isEditing && editingUser && onEditUser) {
      onEditUser(editingUser.id, data)
    } else {
      onAddUser(data)
    }
    form.reset()
    setOpen(false)
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {children || (
          <Button className="cursor-pointer">
            <Plus className="mr-2 h-4 w-4" />
            Adicionar Usuário
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Editar Usuário' : 'Adicionar Usuário'}</DialogTitle>
          <DialogDescription>
            {isEditing 
              ? 'Edite as informações do usuário. Clique em "Salvar" quando terminar.'
              : 'Crie uma nova conta de usuário. Clique em "Salvar" quando terminar.'
            }
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Nome</FormLabel>
                  <FormControl>
                    <Input placeholder="Digite o nome completo" {...field} />
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
                  <FormLabel>E-mail</FormLabel>
                  <FormControl>
                    <Input placeholder="Digite o endereço de e-mail" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="password"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Senha</FormLabel>
                    <FormControl>
                      <Input
                        type="password"
                        placeholder="Digite a senha"
                        className={passwordMismatch ? "border-red-500 focus:border-red-500" : ""}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="password_confirmation"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Confirmar Senha</FormLabel>
                    <FormControl>
                      <Input
                        type="password"
                        placeholder="Confirme a senha"
                        className={passwordMismatch ? "border-red-500 focus:border-red-500" : ""}
                        {...field}
                      />
                    </FormControl>
                    {passwordMismatch && (
                      <p className="text-sm text-red-500">
                        As senhas não coincidem
                      </p>
                    )}
                    {password && passwordConfirmation && !passwordMismatch && (
                      <p className="text-sm text-green-500">
                        ✓ Senhas coincidem
                      </p>
                    )}
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
            <FormField
              control={form.control}
              name="phone"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Telefone (Opcional)</FormLabel>
                  <FormControl>
                    <Input placeholder="(00) 00000-0000" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="is_active"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                  <div className="space-y-0.5">
                    <FormLabel className="text-base">
                      Status do Usuário
                    </FormLabel>
                    <div className="text-sm text-muted-foreground">
                      {field.value ? "Usuário ativo" : "Usuário inativo"}
                    </div>
                  </div>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                </FormItem>
              )}
            />
            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => setOpen(false)}
              >
                Cancelar
              </Button>
              <Button 
                type="submit" 
                className="cursor-pointer"
                disabled={!isFormValid}
                title={!isFormValid ? "Preencha todos os campos corretamente para salvar" : ""}
              >
                {passwordMismatch ? "Senhas não coincidem" : (isEditing ? "Atualizar Usuário" : "Salvar Usuário")}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
