"use client"

import React, { useState } from "react"
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
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Plus } from "lucide-react"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"

const permissionFormSchema = z.object({
  name: z.string().min(2, {
    message: "Nome deve ter pelo menos 2 caracteres.",
  }),
  slug: z.string().optional(),
  description: z.string().optional(),
  module: z.string().optional(),
  action: z.string().optional(),
  resource: z.string().optional(),
})

interface PermissionFormValues {
  name: string
  slug?: string
  description?: string
  module?: string
  action?: string
  resource?: string
}

interface PermissionFormDialogProps {
  onAddPermission: (permissionData: PermissionFormValues) => void | Promise<void>
  onEditPermission?: (id: number, permissionData: PermissionFormValues) => void | Promise<void>
  editingPermission?: Permission | null
  children?: React.ReactNode
  open?: boolean
  onOpenChange?: (open: boolean) => void
}

interface Permission {
  id: number
  name: string
  slug: string
  description?: string
  module?: string
  action?: string
  resource?: string
  created_at: string
  updated_at: string
}

export function PermissionFormDialog({ 
  onAddPermission, 
  onEditPermission, 
  editingPermission,
  children,
  open: controlledOpen,
  onOpenChange: controlledOnOpenChange,
}: PermissionFormDialogProps) {
  const [internalOpen, setInternalOpen] = useState(false)
  const isControlled = controlledOpen !== undefined
  const open = isControlled ? controlledOpen : internalOpen
  const setOpen = isControlled ? (controlledOnOpenChange || (() => {})) : setInternalOpen
  const isEditing = !!editingPermission

  const form = useForm<PermissionFormValues>({
    resolver: zodResolver(permissionFormSchema),
    defaultValues: {
      name: "",
      slug: "",
      description: "",
      module: "",
      action: "",
      resource: "",
    },
  })

  // Atualizar form quando editingPermission mudar
  React.useEffect(() => {
    if (editingPermission) {
      // Normalizar possíveis objetos vindos do backend
      const toText = (v: any) =>
        typeof v === "string"
          ? v
          : (v?.resource ?? v?.slug ?? v?.name ?? v?.id?.toString?.() ?? "")

      form.reset({
        name: editingPermission.name,
        slug: toText(editingPermission.slug),
        description: editingPermission.description || "",
        module: toText((editingPermission as any).module),
        action: toText((editingPermission as any).action),
        resource: toText((editingPermission as any).resource),
      })
    } else {
      form.reset({
        name: "",
        slug: "",
        description: "",
        module: "",
        action: "",
        resource: "",
      })
    }
  }, [editingPermission, form])

  const onSubmit = (data: PermissionFormValues) => {
    if (isEditing && editingPermission && onEditPermission) {
      onEditPermission(editingPermission.id, data)
    } else {
      onAddPermission(data)
    }
    form.reset()
    setOpen(false)
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {children || (
          <Button>
            <Plus className="mr-2 h-4 w-4" />
            Nova Permissão
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>
            {isEditing ? 'Editar Permissão' : 'Nova Permissão'}
          </DialogTitle>
          <DialogDescription>
            {isEditing 
              ? 'Atualize os dados da permissão abaixo.'
              : 'Adicione uma nova permissão ao sistema. Preencha os dados abaixo.'
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
                    <Input placeholder="Criar Usuários" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="module"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Módulo</FormLabel>
                  <FormControl>
                    <Input placeholder="users" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="action"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Ação</FormLabel>
                  <FormControl>
                    <Input placeholder="create" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="resource"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Recurso</FormLabel>
                  <FormControl>
                    <Input
                      placeholder="user"
                      {...field}
                      value={
                        typeof (field as any).value === "string"
                          ? (field as any).value
                          : ((field as any).value?.resource ?? (field as any).value?.slug ?? (field as any).value?.name ?? "")
                      }
                    />
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
                    <Input placeholder="create_users" {...field} />
                  </FormControl>
                  <FormDescription className="text-xs text-muted-foreground">
                    Se não informado, o slug será gerado automaticamente a partir do nome.
                  </FormDescription>
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
                    <Textarea placeholder="Descrição da permissão..." {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                Cancelar
              </Button>
              <Button type="submit">
                {isEditing ? 'Atualizar Permissão' : 'Criar Permissão'}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
