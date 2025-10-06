"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
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
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"
import { useMutationWithValidation } from "@/hooks/use-authenticated-api"
import { useBackendValidation, commonFieldMappings } from "@/hooks/use-backend-validation"
import { endpoints } from "@/lib/api-client"

const permissionFormSchema = z.object({
  name: z.string().min(2, {
    message: "Nome deve ter pelo menos 2 caracteres.",
  }),
  slug: z.string().min(2, {
    message: "Slug deve ter pelo menos 2 caracteres.",
  }),
  description: z.string().min(5, {
    message: "Descrição deve ter pelo menos 5 caracteres.",
  }),
  module: z.string().min(1, {
    message: "Módulo é obrigatório.",
  }),
  action: z.string().min(1, {
    message: "Ação é obrigatória.",
  }),
  resource: z.string().min(1, {
    message: "Recurso é obrigatório.",
  }),
  is_active: z.boolean(),
})

interface PermissionFormValues {
  name: string
  slug: string
  description: string
  module: string
  action: string
  resource: string
  is_active: boolean
}

interface PermissionFormProps {
  onSuccess?: () => void
  initialData?: Partial<PermissionFormValues>
}

export function PermissionForm({ onSuccess, initialData }: PermissionFormProps) {
  const form = useForm<PermissionFormValues>({
    resolver: zodResolver(permissionFormSchema),
    defaultValues: {
      name: initialData?.name || "",
      slug: initialData?.slug || "",
      description: initialData?.description || "",
      module: initialData?.module || "",
      action: initialData?.action || "",
      resource: initialData?.resource || "",
      is_active: initialData?.is_active ?? true,
    },
    mode: "onChange",
  })

  // Hook para tratamento de erros de validação do backend
  const { handleBackendErrors } = useBackendValidation(form.setError)

  const onSubmit = async (data: PermissionFormValues) => {
    try {
      console.log('Dados da permissão:', data)
      
      // Aqui você faria a chamada para a API
      // const result = await createPermission(data)
      
      // Simular sucesso
      console.log('Permissão criada com sucesso:', data)
      
      if (onSuccess) {
        onSuccess()
      }
      
      form.reset()
    } catch (error: any) {
      console.error('Erro ao criar permissão:', error)
      
      // Tratar erros de validação do backend
      const handled = handleBackendErrors(error, commonFieldMappings as any)
      
      if (!handled) {
        // Se não conseguiu mapear para campos específicos, mostrar erro geral
        form.setError('root', {
          type: 'server',
          message: error.message || 'Erro ao criar permissão'
        })
      }
    }
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Nome</FormLabel>
              <FormControl>
                <Input placeholder="Ex: Visualizar Usuários" {...field} />
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
              <FormLabel>Slug</FormLabel>
              <FormControl>
                <Input placeholder="Ex: users.view" {...field} />
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
              <FormLabel>Descrição</FormLabel>
              <FormControl>
                <Textarea 
                  placeholder="Descrição da permissão..."
                  className="resize-none"
                  {...field} 
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <div className="grid grid-cols-3 gap-4">
          <FormField
            control={form.control}
            name="module"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Módulo</FormLabel>
                <FormControl>
                  <Input placeholder="Ex: users" {...field} />
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
                  <Input placeholder="Ex: view" {...field} />
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
                  <Input placeholder="Ex: user" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </div>

        <FormField
          control={form.control}
          name="is_active"
          render={({ field }) => (
            <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
              <div className="space-y-0.5">
                <FormLabel className="text-base">
                  Ativo
                </FormLabel>
                <div className="text-sm text-muted-foreground">
                  Esta permissão está ativa no sistema
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

        {/* Exibir erro geral se houver */}
        {form.formState.errors.root && (
          <div className="text-sm text-red-600 bg-red-50 p-3 rounded-md">
            {form.formState.errors.root.message}
          </div>
        )}

        <Button type="submit" className="w-full">
          Criar Permissão
        </Button>
      </form>
    </Form>
  )
}
