"use client"

import { useState } from "react"
import React from "react"
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
import { Textarea } from "@/components/ui/textarea"
import { Switch } from "@/components/ui/switch"
import { Plus } from "lucide-react"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"
import { ServiceTypeData, ServiceTypeFormValues } from "../types"

const serviceTypeFormSchema = z.object({
  name: z.string().min(1, {
    message: "Nome do tipo de atendimento é obrigatório.",
  }),
  slug: z.string().optional(),
  description: z.string().optional(),
  is_active: z.boolean().optional().default(true),
  requires_address: z.boolean().optional().default(false),
  requires_table: z.boolean().optional().default(false),
  available_in_menu: z.boolean().optional().default(false),
  order_position: z.number().min(0).optional().default(0),
})

interface ServiceTypeFormDialogProps {
  onAddServiceType: (serviceTypeData: ServiceTypeFormValues) => void
  onEditServiceType?: (id: number, serviceTypeData: ServiceTypeFormValues) => void
  editServiceType?: ServiceTypeData | null
  onStartNew?: () => void
}

export function ServiceTypeFormDialog({ 
  onAddServiceType, 
  onEditServiceType, 
  editServiceType, 
  onStartNew 
}: ServiceTypeFormDialogProps) {
  const [open, setOpen] = useState(false)
  const isEditing = !!editServiceType

  // Controlar abertura do modal quando editando
  React.useEffect(() => {
    if (editServiceType) {
      setOpen(true)
    }
  }, [editServiceType])

  const form = useForm<ServiceTypeFormValues>({
    resolver: zodResolver(serviceTypeFormSchema),
    defaultValues: {
      name: "",
      slug: "",
      description: "",
      is_active: true,
      requires_address: false,
      requires_table: false,
      available_in_menu: false,
      order_position: 0,
    },
  })

  // Reset form when closing or changing between add/edit modes
  React.useEffect(() => {
    if (open && !editServiceType) {
      // Creating new service type - reset to empty values
      setTimeout(() => {
        form.reset({
          name: "",
          slug: "",
          description: "",
          is_active: true,
          requires_address: false,
          requires_table: false,
          available_in_menu: false,
          order_position: 0,
        })
      }, 50)
    } else if (open && editServiceType) {
      // Editing existing service type - fill with service type data  
      form.reset({
        name: editServiceType.name || "",
        slug: editServiceType.slug || "",
        description: editServiceType.description || "",
        is_active: editServiceType.is_active ?? true,
        requires_address: editServiceType.requires_address ?? false,
        requires_table: editServiceType.requires_table ?? false,
        available_in_menu: editServiceType.available_in_menu ?? false,
        order_position: editServiceType.order_position || 0,
      })
    }
  }, [open, editServiceType, form])

  const onSubmit = (data: ServiceTypeFormValues) => {
    if (isEditing && editServiceType && onEditServiceType) {
      onEditServiceType(editServiceType.id, data)
    } else {
      onAddServiceType(data)
    }
    form.reset({
      name: "",
      slug: "",
      description: "",
      is_active: true,
      requires_address: false,
      requires_table: false,
      available_in_menu: false,
      order_position: 0,
    })
    setOpen(false)
    
    // Limpar estado de edição após submissão
    if (onStartNew) {
      onStartNew()
    }
  }

  const handleOpenChange = (newOpen: boolean) => {
    setOpen(newOpen)
    if (!newOpen) {
      // Quando fechando o modal, resetar formulário
      setTimeout(() => {
        form.reset({
          name: "",
          slug: "",
          description: "",
          is_active: true,
          requires_address: false,
          requires_table: false,
          available_in_menu: false,
          order_position: 0,
        })
      }, 100)
    }
  }

  // Handler para quando clicar no botão "Novo Tipo"
  const handleOpenForNew = () => {
    // Limpar estado de edição se existir
    if (onStartNew) {
      onStartNew()
    }
    // Forçar reset do formulário
    form.reset({
      name: "",
      slug: "",
      description: "",
      is_active: true,
      requires_address: false,
      requires_table: false,
      available_in_menu: false,
      order_position: 0,
    })
    // Abrir modal
    setOpen(true)
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        <Button onClick={handleOpenForNew}>
          <Plus className="mr-2 h-4 w-4" />
          Novo Tipo de Atendimento
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Editar Tipo de Atendimento' : 'Novo Tipo de Atendimento'}</DialogTitle>
          <DialogDescription>
            {isEditing 
              ? 'Edite as informações do tipo de atendimento abaixo.' 
              : 'Adicione um novo tipo de atendimento ao sistema. Preencha os dados abaixo.'
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
                  <FormLabel>Nome *</FormLabel>
                  <FormControl>
                    <Input placeholder="Ex: Delivery, Retirada, Balcão" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="slug"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Slug (Opcional)</FormLabel>
                  <FormControl>
                    <Input placeholder="Ex: delivery, retirada, balcao" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Descrição (Opcional)</FormLabel>
                  <FormControl>
                    <Textarea 
                      placeholder="Descrição do tipo de atendimento..." 
                      {...field} 
                      rows={3}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="order_position"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Ordem de Exibição</FormLabel>
                    <FormControl>
                      <Input
                        type="number"
                        placeholder="0"
                        {...field}
                        onChange={(e) => field.onChange(e.target.value ? parseInt(e.target.value) : 0)}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
            <div className="space-y-4">
              <FormField
                control={form.control}
                name="is_active"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                      <FormLabel className="text-base">Ativo</FormLabel>
                      <div className="text-sm text-muted-foreground">
                        Tipo de atendimento está ativo no sistema
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
              <FormField
                control={form.control}
                name="requires_address"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                      <FormLabel className="text-base">Requer Endereço</FormLabel>
                      <div className="text-sm text-muted-foreground">
                        Este tipo de atendimento requer endereço de entrega
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
              <FormField
                control={form.control}
                name="requires_table"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                      <FormLabel className="text-base">Requer Mesa</FormLabel>
                      <div className="text-sm text-muted-foreground">
                        Este tipo de atendimento requer seleção de mesa
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
              <FormField
                control={form.control}
                name="available_in_menu"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                      <FormLabel className="text-base">Disponível no Menu</FormLabel>
                      <div className="text-sm text-muted-foreground">
                        Exibir este tipo de atendimento no menu público
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
            </div>
            <DialogFooter>
              <Button 
                type="button" 
                variant="outline" 
                onClick={() => setOpen(false)}
              >
                Cancelar
              </Button>
              <Button type="submit">{isEditing ? 'Salvar Alterações' : 'Criar Tipo de Atendimento'}</Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}

