"use client"

import { useState } from "react"
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
import { Switch } from "@/components/ui/switch"
import { useForm } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"

interface PaymentMethod {
  id?: number
  uuid: string
  name: string
  description?: string
  pix_key?: string | null
  is_active: boolean
  tenant_id?: number
  created_at: string
  updated_at: string
}

interface PaymentMethodFormValues {
  name: string
  description?: string
  pix_key?: string | null
  is_active?: boolean
}

const paymentMethodFormSchema = z.object({
  name: z.string().min(1, {
    message: "Nome é obrigatório.",
  }).max(255, {
    message: "Nome não pode ter mais de 255 caracteres.",
  }),
  description: z.string().max(1000, {
    message: "Descrição não pode ter mais de 1000 caracteres.",
  }).optional(),
  pix_key: z.string().max(255, {
    message: "Chave PIX não pode ter mais de 255 caracteres.",
  }).optional().nullable(),
  is_active: z.boolean().optional().default(true),
})

interface PaymentMethodFormDialogProps {
  children: React.ReactNode
  paymentMethod?: PaymentMethod
  onSubmit: (data: PaymentMethodFormValues) => Promise<void>
}

export function PaymentMethodFormDialog({ 
  children, 
  paymentMethod, 
  onSubmit 
}: PaymentMethodFormDialogProps) {
  const [open, setOpen] = useState(false)
  const [loading, setLoading] = useState(false)

  const form = useForm<PaymentMethodFormValues>({
    resolver: zodResolver(paymentMethodFormSchema),
    defaultValues: {
      name: paymentMethod?.name || "",
      description: paymentMethod?.description || "",
      pix_key: paymentMethod?.pix_key || "",
      is_active: paymentMethod?.is_active ?? true,
    },
  })

  const isPix = /pix/i.test(form.watch("name") || "")

  const handleSubmit = async (data: PaymentMethodFormValues) => {
    setLoading(true)
    try {
      await onSubmit({ ...data, pix_key: isPix ? data.pix_key?.trim() || null : null })
      form.reset()
      setOpen(false)
    } catch (error) {

    } finally {
      setLoading(false)
    }
  }

  const handleOpenChange = (open: boolean) => {
    setOpen(open)
    if (!open) {
      form.reset({
        name: paymentMethod?.name || "",
        description: paymentMethod?.description || "",
        pix_key: paymentMethod?.pix_key || "",
        is_active: paymentMethod?.is_active ?? true,
      })
    }
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        {children}
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>
            {paymentMethod ? "Editar Forma de Pagamento" : "Nova Forma de Pagamento"}
          </DialogTitle>
          <DialogDescription>
            {paymentMethod 
              ? "Edite as informações da forma de pagamento."
              : "Adicione uma nova forma de pagamento ao seu estabelecimento."
            }
          </DialogDescription>
        </DialogHeader>
        
        <Form {...form}>
          <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Nome *</FormLabel>
                  <FormControl>
                    <Input placeholder="Ex: Cartão de Crédito" {...field} />
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
                      placeholder="Descrição opcional da forma de pagamento..."
                      className="resize-none"
                      {...field}
                    />
                  </FormControl>
                  <FormDescription>
                    Descrição opcional para identificar melhor a forma de pagamento
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />

            {isPix && (
              <FormField
                control={form.control}
                name="pix_key"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Chave PIX</FormLabel>
                    <FormControl>
                      <Input placeholder="CPF, CNPJ, e-mail, telefone ou chave aleatória" {...field} value={field.value ?? ""} />
                    </FormControl>
                    <FormDescription>
                      Exibida no cardápio para o cliente copiar
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
            )}

            <FormField
              control={form.control}
              name="is_active"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                  <div className="space-y-0.5">
                    <FormLabel className="text-base">Ativo</FormLabel>
                    <FormDescription>
                      Forma de pagamento disponível para uso
                    </FormDescription>
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
                disabled={loading}
              >
                Cancelar
              </Button>
              <Button type="submit" disabled={loading}>
                {loading ? "Salvando..." : (paymentMethod ? "Atualizar" : "Criar")}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}