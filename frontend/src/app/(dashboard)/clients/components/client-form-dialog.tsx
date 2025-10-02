"use client"

import { useState, useEffect } from "react"
import * as React from "react"
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
import { Switch } from "@/components/ui/switch"
import { Plus } from "lucide-react"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"

const clientFormSchema = z.object({
  name: z.string().min(3, {
    message: "O nome completo deve ter pelo menos 3 caracteres.",
  }).max(255, {
    message: "O nome completo não pode ter mais de 255 caracteres.",
  }),
  cpf: z.string().min(11, {
    message: "O CPF deve ter pelo menos 11 caracteres.",
  }).max(14, {
    message: "O CPF não pode ter mais de 14 caracteres.",
  }),
  email: z.string().email({
    message: "Por favor, insira um email válido.",
  }).min(3, {
    message: "O email deve ter pelo menos 3 caracteres.",
  }).max(255, {
    message: "O email não pode ter mais de 255 caracteres.",
  }),
  phone: z.string().min(10, {
    message: "O telefone deve ter pelo menos 10 caracteres.",
  }).max(20, {
    message: "O telefone não pode ter mais de 20 caracteres.",
  }),
  address: z.string().optional(),
  city: z.string().optional(),
  state: z.string().optional(),
  zip_code: z.string().optional(),
  neighborhood: z.string().optional(),
  number: z.string().optional(),
  complement: z.string().optional(),
  isActive: z.boolean(),
})

interface ClientFormValues {
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
  isActive: boolean
}

interface ClientFormDialogProps {
  onAddClient: (clientData: ClientFormValues) => void
  onEditClient?: (id: number, clientData: ClientFormValues) => void
  editingClient?: ClientFormValues & { id: number } | null
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function ClientFormDialog({ 
  onAddClient, 
  onEditClient, 
  editingClient, 
  open, 
  onOpenChange 
}: ClientFormDialogProps) {
  const isEditing = !!editingClient

  const form = useForm<ClientFormValues>({
    resolver: zodResolver(clientFormSchema),
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
      isActive: true,
    },
  })

  // Preencher o formulário quando editingClient mudar
  React.useEffect(() => {
    if (editingClient) {
      form.reset({
        name: editingClient.name,
        cpf: editingClient.cpf,
        email: editingClient.email,
        phone: editingClient.phone,
        address: editingClient.address || "",
        city: editingClient.city || "",
        state: editingClient.state || "",
        zip_code: editingClient.zip_code || "",
        neighborhood: editingClient.neighborhood || "",
        number: editingClient.number || "",
        complement: editingClient.complement || "",
        isActive: editingClient.isActive,
      })
    } else {
      form.reset({
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
        isActive: true,
      })
    }
  }, [editingClient, form])

  const onSubmit = (data: ClientFormValues) => {
    if (isEditing && editingClient && onEditClient) {
      onEditClient(editingClient.id, data)
    } else {
      onAddClient(data)
    }
    form.reset()
    onOpenChange(false)
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      {!isEditing && (
        <DialogTrigger asChild>
          <Button>
            <Plus className="mr-2 h-4 w-4" />
            Novo Cliente
          </Button>
        </DialogTrigger>
      )}
      <DialogContent className="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Editar Cliente' : 'Novo Cliente'}</DialogTitle>
          <DialogDescription>
            {isEditing 
              ? 'Edite os dados do cliente abaixo.' 
              : 'Adicione um novo cliente ao sistema. Preencha os dados abaixo.'
            }
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            {/* Dados básicos */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem className="md:col-span-2">
                    <FormLabel>Nome Completo *</FormLabel>
                    <FormControl>
                      <Input placeholder="João Silva" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              
              <FormField
                control={form.control}
                name="cpf"
                render={({ field }) => (
                  <FormItem className="md:col-span-2">
                    <FormLabel>CPF *</FormLabel>
                    <FormControl>
                      <Input placeholder="000.000.000-00" {...field} />
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
                    <FormLabel>Email *</FormLabel>
                    <FormControl>
                      <Input type="email" placeholder="joao@example.com" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              
              <FormField
                control={form.control}
                name="phone"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Telefone *</FormLabel>
                    <FormControl>
                      <Input placeholder="(11) 99999-9999" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            {/* Endereço */}
            <div className="space-y-4">
              <h3 className="text-lg font-medium">Endereço (Opcional)</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="address"
                  render={({ field }) => (
                    <FormItem className="md:col-span-2">
                      <FormLabel>Logradouro</FormLabel>
                      <FormControl>
                        <Input placeholder="Rua das Flores" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="number"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Número</FormLabel>
                      <FormControl>
                        <Input placeholder="123" {...field} />
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
                        <Input placeholder="Apto 101" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="neighborhood"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Bairro</FormLabel>
                      <FormControl>
                        <Input placeholder="Centro" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="city"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Cidade</FormLabel>
                      <FormControl>
                        <Input placeholder="São Paulo" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="state"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Estado</FormLabel>
                      <FormControl>
                        <Input placeholder="SP" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="zip_code"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>CEP</FormLabel>
                      <FormControl>
                        <Input placeholder="01234-567" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </div>

            {/* Status */}
            <FormField
              control={form.control}
              name="isActive"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                  <div className="space-y-0.5">
                    <FormLabel className="text-base">Cliente Ativo</FormLabel>
                    <div className="text-sm text-muted-foreground">
                      Este cliente poderá fazer pedidos
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
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                Cancelar
              </Button>
              <Button type="submit">
                {isEditing ? 'Salvar Alterações' : 'Criar Cliente'}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
