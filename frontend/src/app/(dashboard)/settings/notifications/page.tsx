"use client"

import { useState, useEffect } from "react"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import * as z from "zod"
import { Bell, Mail, MessageSquare, Loader2 } from "lucide-react"
import { toast } from "sonner"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Checkbox } from "@/components/ui/checkbox"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Separator } from "@/components/ui/separator"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { useNotificationPreferences } from "@/hooks/use-notification-preferences"
import { OrderNotificationToggle } from "@/components/order-notification-toggle"

const notificationsFormSchema = z.object({
  // Usuários
  user_created_email: z.boolean(),
  user_created_push: z.boolean(),
  
  // Pedidos
  order_created_email: z.boolean(),
  order_created_push: z.boolean(),
  order_status_changed_email: z.boolean(),
  order_status_changed_push: z.boolean(),
  order_completed_email: z.boolean(),
  order_completed_push: z.boolean(),
  order_cancelled_email: z.boolean(),
  order_cancelled_push: z.boolean(),
  
  // Produtos
  product_created_email: z.boolean(),
  product_created_push: z.boolean(),
  product_stock_low_email: z.boolean(),
  product_stock_low_push: z.boolean(),
  product_out_of_stock_email: z.boolean(),
  product_out_of_stock_push: z.boolean(),
  
  // Clientes
  client_created_email: z.boolean(),
  client_created_push: z.boolean(),
  client_first_purchase_email: z.boolean(),
  client_first_purchase_push: z.boolean(),
  
  // Configurações gerais
  frequency: z.string(),
  quiet_hours_start: z.string().optional(),
  quiet_hours_end: z.string().optional(),
})

type NotificationsFormValues = z.infer<typeof notificationsFormSchema>

const eventLabels: Record<string, string> = {
  user_created: 'Novo Usuário Cadastrado',
  order_created: 'Novo Pedido Recebido',
  order_status_changed: 'Status de Pedido Alterado',
  order_completed: 'Pedido Concluído',
  order_cancelled: 'Pedido Cancelado',
  product_created: 'Novo Produto Cadastrado',
  product_stock_low: 'Estoque Baixo',
  product_out_of_stock: 'Produto Esgotado',
  client_created: 'Novo Cliente Cadastrado',
  client_first_purchase: 'Primeira Compra do Cliente',
}

export default function NotificationSettings() {
  const { preferences, loading: prefsLoading, updatePreferences } = useNotificationPreferences()
  const [saving, setSaving] = useState(false)

  const form = useForm<NotificationsFormValues>({
    resolver: zodResolver(notificationsFormSchema),
    defaultValues: {
      user_created_email: true,
      user_created_push: false,
      order_created_email: true,
      order_created_push: true,
      order_status_changed_email: true,
      order_status_changed_push: true,
      order_completed_email: false,
      order_completed_push: true,
      order_cancelled_email: true,
      order_cancelled_push: true,
      product_created_email: false,
      product_created_push: true,
      product_stock_low_email: true,
      product_stock_low_push: true,
      product_out_of_stock_email: true,
      product_out_of_stock_push: true,
      client_created_email: false,
      client_created_push: true,
      client_first_purchase_email: true,
      client_first_purchase_push: true,
      frequency: "instant",
      quiet_hours_start: "22:00",
      quiet_hours_end: "06:00",
    },
  })

  // Carregar preferências do backend
  useEffect(() => {
    if (preferences.length > 0) {
      const formValues: any = {
        frequency: "instant",
        quiet_hours_start: "",
        quiet_hours_end: "",
      }

      // Buscar valores de quiet_hours da primeira preferência que tiver
      let quietHoursFound = false

      preferences.forEach(pref => {
        formValues[`${pref.event_type}_email`] = pref.email_enabled
        formValues[`${pref.event_type}_push`] = pref.push_enabled
        if (pref.frequency) formValues.frequency = pref.frequency
        
        // Pegar quiet_hours apenas da primeira preferência que tiver valores
        if (!quietHoursFound && pref.quiet_hours_start) {
          const start = String(pref.quiet_hours_start)
          formValues.quiet_hours_start = start.includes(':') ? start.substring(0, 5) : start
          quietHoursFound = true
        }
        if (!quietHoursFound && pref.quiet_hours_end) {
          const end = String(pref.quiet_hours_end)
          formValues.quiet_hours_end = end.includes(':') ? end.substring(0, 5) : end
        }
      })

      // Se não encontrou valores, usar padrões
      if (!formValues.quiet_hours_start) formValues.quiet_hours_start = "22:00"
      if (!formValues.quiet_hours_end) formValues.quiet_hours_end = "06:00"

      form.reset(formValues)
    }
  }, [preferences, form])

  async function onSubmit(data: NotificationsFormValues) {
    setSaving(true)
    
    // Converter form data para formato da API
    const preferencesArray = []
    
    const eventTypes = [
      'user_created',
      'order_created',
      'order_status_changed',
      'order_completed',
      'order_cancelled',
      'product_created',
      'product_stock_low',
      'product_out_of_stock',
      'client_created',
      'client_first_purchase',
    ]

    for (const eventType of eventTypes) {
      const preference: any = {
        event_type: eventType,
        email_enabled: data[`${eventType}_email` as keyof NotificationsFormValues] ?? false,
        push_enabled: data[`${eventType}_push` as keyof NotificationsFormValues] ?? false,
        sms_enabled: false,
        frequency: data.frequency || 'instant',
      }
      
      // Apenas adicionar quiet_hours se tiverem valores válidos no formato HH:MM
      if (data.quiet_hours_start && typeof data.quiet_hours_start === 'string') {
        const start = data.quiet_hours_start.trim()
        // Verificar se está no formato HH:MM
        if (/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(start)) {
          preference.quiet_hours_start = start
        }
      }
      
      if (data.quiet_hours_end && typeof data.quiet_hours_end === 'string') {
        const end = data.quiet_hours_end.trim()
        // Verificar se está no formato HH:MM
        if (/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(end)) {
          preference.quiet_hours_end = end
        }
      }
      
      preferencesArray.push(preference)
    }

    const success = await updatePreferences(preferencesArray)
    
    setSaving(false)
    
    if (success) {
      toast.success('Preferências salvas com sucesso!')
    }
  }

  if (prefsLoading) {
    return (
      <div className="space-y-6 px-4 lg:px-6">
        <div>
          <h1 className="text-3xl font-bold">Notificações</h1>
          <p className="text-muted-foreground">Configure como você recebe notificações.</p>
        </div>
        <div className="flex justify-center items-center min-h-[400px]">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6 px-4 lg:px-6">
      <div>
        <h1 className="text-3xl font-bold">Notificações</h1>
        <p className="text-muted-foreground">
          Configure como você recebe notificações sobre eventos do sistema.
        </p>
      </div>

      {/* Notificações Sonoras de Pedidos */}
      <OrderNotificationToggle />

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          {/* Tabela de Preferências por Tipo de Evento */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Bell className="h-5 w-5" />
                Preferências de Notificação
              </CardTitle>
              <CardDescription>
                Escolha quais eventos deseja ser notificado e por quais canais.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-[250px]">Tipo de Evento</TableHead>
                    <TableHead className="text-center w-[100px]">
                      <Mail className="h-4 w-4 inline mr-1" />
                      Email
                    </TableHead>
                    <TableHead className="text-center w-[100px]">
                      <Bell className="h-4 w-4 inline mr-1" />
                      Push
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {/* Usuários */}
                  <TableRow className="bg-muted/30">
                    <TableCell colSpan={3} className="font-semibold">Usuários</TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Novo Usuário Cadastrado</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="user_created_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="user_created_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>

                  {/* Pedidos */}
                  <TableRow className="bg-muted/30">
                    <TableCell colSpan={3} className="font-semibold">Pedidos</TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Novo Pedido Recebido</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="order_created_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="order_created_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Status de Pedido Alterado</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="order_status_changed_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="order_status_changed_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Pedido Concluído</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="order_completed_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="order_completed_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Pedido Cancelado</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="order_cancelled_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="order_cancelled_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>

                  {/* Produtos */}
                  <TableRow className="bg-muted/30">
                    <TableCell colSpan={3} className="font-semibold">Produtos</TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Novo Produto Cadastrado</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="product_created_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="product_created_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Estoque Baixo</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="product_stock_low_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="product_stock_low_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Produto Esgotado</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="product_out_of_stock_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="product_out_of_stock_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>

                  {/* Clientes */}
                  <TableRow className="bg-muted/30">
                    <TableCell colSpan={3} className="font-semibold">Clientes</TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Novo Cliente Cadastrado</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="client_created_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="client_created_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>
                  <TableRow>
                    <TableCell className="font-medium">Primeira Compra do Cliente</TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="client_first_purchase_email"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                    <TableCell className="text-center">
                      <FormField
                        control={form.control}
                        name="client_first_purchase_push"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          {/* Configurações Gerais */}
          <Card>
            <CardHeader>
              <CardTitle>Configurações Gerais</CardTitle>
              <CardDescription>
                Defina frequência e horários para receber notificações.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormField
                  control={form.control}
                  name="frequency"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Frequência de Emails</FormLabel>
                      <Select onValueChange={field.onChange} defaultValue={field.value}>
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="Selecione a frequência" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          <SelectItem value="instant">Instantâneo</SelectItem>
                          <SelectItem value="hourly">A cada hora</SelectItem>
                          <SelectItem value="daily">Diário</SelectItem>
                          <SelectItem value="weekly">Semanal</SelectItem>
                          <SelectItem value="never">Nunca</SelectItem>
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormItem>
                  <FormLabel>Horário Silencioso</FormLabel>
                  <div className="flex items-center gap-2">
                    <FormField
                      control={form.control}
                      name="quiet_hours_start"
                      render={({ field }) => (
                        <Select onValueChange={field.onChange} defaultValue={field.value}>
                          <FormControl>
                            <SelectTrigger className="w-28">
                              <SelectValue placeholder="Início" />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            <SelectItem value="20:00">20:00</SelectItem>
                            <SelectItem value="21:00">21:00</SelectItem>
                            <SelectItem value="22:00">22:00</SelectItem>
                            <SelectItem value="23:00">23:00</SelectItem>
                            <SelectItem value="00:00">00:00</SelectItem>
                          </SelectContent>
                        </Select>
                      )}
                    />
                    <span className="text-muted-foreground">até</span>
                    <FormField
                      control={form.control}
                      name="quiet_hours_end"
                      render={({ field }) => (
                        <Select onValueChange={field.onChange} defaultValue={field.value}>
                          <FormControl>
                            <SelectTrigger className="w-28">
                              <SelectValue placeholder="Fim" />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            <SelectItem value="05:00">05:00</SelectItem>
                            <SelectItem value="06:00">06:00</SelectItem>
                            <SelectItem value="07:00">07:00</SelectItem>
                            <SelectItem value="08:00">08:00</SelectItem>
                            <SelectItem value="09:00">09:00</SelectItem>
                          </SelectContent>
                        </Select>
                      )}
                    />
                  </div>
                  <p className="text-sm text-muted-foreground mt-2">
                    Não receberá notificações push durante este período.
                  </p>
                </FormItem>
              </div>
            </CardContent>
          </Card>

          {/* Botões de Ação */}
          <div className="flex justify-end gap-4">
            <Button
              type="button"
              variant="outline"
              onClick={() => form.reset()}
              disabled={saving}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={saving}>
              {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Salvar Preferências
            </Button>
          </div>
        </form>
      </Form>
    </div>
  )
}
