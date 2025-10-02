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
  FormDescription,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { ComboboxForm, ComboboxOption } from "@/components/ui/combobox"
import { Switch } from "@/components/ui/switch"
import { Textarea } from "@/components/ui/textarea"
import { Plus, UserPlus, Trash2, MapPin } from "lucide-react"
import { useForm, useFieldArray } from "react-hook-form"
import { z } from "zod"
import { zodResolver } from "@hookform/resolvers/zod"
import { useAuthenticatedClients, useAuthenticatedProducts, useAuthenticatedTables, useMutation } from "@/hooks/use-authenticated-api"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { endpoints } from "@/lib/api-client"

const orderFormSchema = z.object({
  clientId: z.string().min(1, {
    message: "Por favor, selecione um cliente.",
  }),
  products: z.array(z.object({
    productId: z.string().min(1, { message: "Selecione um produto." }),
    quantity: z.number().min(1, { message: "Quantidade deve ser maior que 0." }),
    price: z.number().min(0),
  })).min(1, { message: "Adicione pelo menos um produto." }),
  status: z.string().min(1, {
    message: "Por favor, selecione um status.",
  }),
  isDelivery: z.boolean(),
  tableId: z.string().optional(),
  total: z.number().min(0),
  
  // Delivery fields
  useClientAddress: z.boolean(),
  deliveryAddress: z.string().optional(),
  deliveryCity: z.string().optional(),
  deliveryState: z.string().optional(),
  deliveryZipCode: z.string().optional(),
  deliveryNeighborhood: z.string().optional(),
  deliveryNumber: z.string().optional(),
  deliveryComplement: z.string().optional(),
  deliveryNotes: z.string().optional(),
}).refine((data) => {
  // Se não for delivery, é obrigatório ter mesa
  if (!data.isDelivery && (!data.tableId || data.tableId === "")) {
    return false
  }
  return true
}, {
  message: "Por favor, selecione uma mesa.",
  path: ["tableId"],
}).refine((data) => {
  // Se for delivery e não usar endereço do cliente, deve ter endereço de entrega
  if (data.isDelivery && !data.useClientAddress) {
    if (!data.deliveryAddress || !data.deliveryCity) {
      return false
    }
  }
  return true
}, {
  message: "Endereço e cidade são obrigatórios para delivery.",
  path: ["deliveryAddress"],
})

interface OrderFormValues {
  clientId: string
  products: {
    productId: string
    quantity: number
    price: number
  }[]
  status: string
  isDelivery: boolean
  tableId?: string
  total: number
  useClientAddress: boolean
  deliveryAddress?: string
  deliveryCity?: string
  deliveryState?: string
  deliveryZipCode?: string
  deliveryNeighborhood?: string
  deliveryNumber?: string
  deliveryComplement?: string
  deliveryNotes?: string
}

interface OrderFormDialogProps {
  onAddOrder: (orderData: OrderFormValues) => void
}

interface Client {
  id: number
  name: string
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
  isActive: boolean
}

interface Product {
  id: number
  name: string
  price: number
  qtd_stock: number
}

interface Table {
  id: number
  name: string
  identify: string
  capacity: number
}

export function OrderFormDialog({ onAddOrder }: OrderFormDialogProps) {
  const [open, setOpen] = useState(false)
  const [clientDialogOpen, setClientDialogOpen] = useState(false)
  const [newClientName, setNewClientName] = useState("")
  const [newClientEmail, setNewClientEmail] = useState("")
  const [newClientPhone, setNewClientPhone] = useState("")
  const [newClientAddress, setNewClientAddress] = useState("")
  const [newClientCity, setNewClientCity] = useState("")
  const [newClientState, setNewClientState] = useState("")
  const [newClientZipCode, setNewClientZipCode] = useState("")
  const [newClientNeighborhood, setNewClientNeighborhood] = useState("")
  const [newClientNumber, setNewClientNumber] = useState("")
  const [newClientComplement, setNewClientComplement] = useState("")
  
  const { data: clientsData, refetch: refetchClients } = useAuthenticatedClients()
  const { data: productsData } = useAuthenticatedProducts()
  const { data: tablesData } = useAuthenticatedTables()
  const { mutate: createClient } = useMutation()

  // Debug logs
  console.log('OrderFormDialog - clientsData:', clientsData)
  console.log('OrderFormDialog - productsData:', productsData)
  console.log('OrderFormDialog - tablesData:', tablesData)

  // Transformar dados da API e filtrar itens inválidos
  const getArrayFromData = (data: any) => {
    if (!data) return []
    if (Array.isArray(data)) return data
    if (data.data && Array.isArray(data.data)) return data.data
    return []
  }

  const clients = getArrayFromData(clientsData).filter((c: any) => c && c.id)
  const products = getArrayFromData(productsData).filter((p: any) => p && p.id && p.price !== undefined)
  const tables = getArrayFromData(tablesData).filter((t: any) => t && t.id)

  // Debug logs dos dados processados
  console.log('OrderFormDialog - clients processados:', clients)
  console.log('OrderFormDialog - products processados:', products)
  console.log('OrderFormDialog - tables processados:', tables)

  const form = useForm<OrderFormValues>({
    resolver: zodResolver(orderFormSchema),
    defaultValues: {
      clientId: "",
      products: [{ productId: "", quantity: 1, price: 0 }],
      status: "Pendente",
      isDelivery: false,
      tableId: "",
      total: 0,
      useClientAddress: false,
      deliveryAddress: "",
      deliveryCity: "",
      deliveryState: "",
      deliveryZipCode: "",
      deliveryNeighborhood: "",
      deliveryNumber: "",
      deliveryComplement: "",
      deliveryNotes: "",
    },
  })

  const { fields, append, remove } = useFieldArray({
    control: form.control,
    name: "products",
  })

  const isDelivery = form.watch("isDelivery")
  const useClientAddress = form.watch("useClientAddress")
  const selectedClientId = form.watch("clientId")
  const watchProducts = form.watch("products")

  // Buscar dados do cliente selecionado
  const selectedClient = clients.find((c: Client) => c.id.toString() === selectedClientId)

  // Preencher endereço do cliente automaticamente se a opção estiver marcada
  useEffect(() => {
    if (isDelivery && useClientAddress && selectedClient && selectedClient.has_complete_address) {
      form.setValue("deliveryAddress", selectedClient.address || "")
      form.setValue("deliveryCity", selectedClient.city || "")
      form.setValue("deliveryState", selectedClient.state || "")
      form.setValue("deliveryZipCode", selectedClient.zip_code || "")
      form.setValue("deliveryNeighborhood", selectedClient.neighborhood || "")
      form.setValue("deliveryNumber", selectedClient.number || "")
      form.setValue("deliveryComplement", selectedClient.complement || "")
    } else if (isDelivery && !useClientAddress) {
      // Limpar campos quando não usar endereço do cliente
      form.setValue("deliveryAddress", "")
      form.setValue("deliveryCity", "")
      form.setValue("deliveryState", "")
      form.setValue("deliveryZipCode", "")
      form.setValue("deliveryNeighborhood", "")
      form.setValue("deliveryNumber", "")
      form.setValue("deliveryComplement", "")
    }
  }, [useClientAddress, selectedClient, isDelivery, form])

  // Calcular total automaticamente
  useEffect(() => {
    const total = watchProducts.reduce((sum, item) => {
      const product = products.find((p: Product) => p.id.toString() === item.productId)
      if (product) {
        return sum + (product.price * item.quantity)
      }
      return sum
    }, 0)
    form.setValue("total", total)
  }, [watchProducts, products, form])

  const handleProductChange = (index: number, productId: string) => {
    const product = products.find((p: Product) => p.id.toString() === productId)
    if (product) {
      form.setValue(`products.${index}.price`, product.price)
    }
  }

  const handleAddClient = async () => {
    try {
      const clientData = {
        name: newClientName,
        email: newClientEmail,
        phone: newClientPhone,
        address: newClientAddress,
        number: newClientNumber,
        complement: newClientComplement,
        neighborhood: newClientNeighborhood,
        city: newClientCity,
        state: newClientState,
        zip_code: newClientZipCode,
      }
      
      const result = await createClient(
        endpoints.clients.create,
        'POST',
        clientData
      )
      
      if (result) {
        await refetchClients()
        setClientDialogOpen(false)
        // Limpar campos
        setNewClientName("")
        setNewClientEmail("")
        setNewClientPhone("")
        setNewClientAddress("")
        setNewClientCity("")
        setNewClientState("")
        setNewClientZipCode("")
        setNewClientNeighborhood("")
        setNewClientNumber("")
        setNewClientComplement("")
        
        // Mostrar mensagem de sucesso
        alert('Cliente cadastrado com sucesso!')
      }
    } catch (error) {
      console.error("Erro ao criar cliente:", error)
      alert('Erro ao cadastrar cliente. Verifique os dados e tente novamente.')
    }
  }

  const onSubmit = (data: OrderFormValues) => {
    onAddOrder(data)
    form.reset()
    setOpen(false)
  }

  return (
    <>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogTrigger asChild>
          <Button>
            <Plus className="mr-2 h-4 w-4" />
            Novo Pedido
          </Button>
        </DialogTrigger>
        <DialogContent className="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Novo Pedido</DialogTitle>
            <DialogDescription>
              Adicione um novo pedido ao sistema. Preencha os dados abaixo.
            </DialogDescription>
          </DialogHeader>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
              {/* Cliente */}
              <FormField
                control={form.control}
                name="clientId"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Cliente</FormLabel>
                    <div className="flex gap-2">
                      <FormControl>
                        <ComboboxForm
                          field={field}
                          options={clients.filter((c: Client) => c.isActive).length > 0 
                            ? clients.filter((c: Client) => c.isActive).map((client: Client) => ({
                                value: client.id.toString(),
                                label: `${client.name} - ${client.email}`,
                              }))
                            : [{ value: "no-clients", label: "Nenhum cliente cadastrado", disabled: true }]
                          }
                          placeholder="Selecione o cliente"
                          searchPlaceholder="Buscar cliente..."
                          emptyText="Nenhum cliente encontrado"
                          className="flex-1"
                        />
                      </FormControl>
                      <Button
                        type="button"
                        size="icon"
                        variant="outline"
                        onClick={() => setClientDialogOpen(true)}
                        title="Adicionar novo cliente"
                      >
                        <UserPlus className="h-4 w-4" />
                      </Button>
                    </div>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {/* Produtos */}
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <FormLabel>Produtos</FormLabel>
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={() => append({ productId: "", quantity: 1, price: 0 })}
                  >
                    <Plus className="mr-1 h-3 w-3" />
                    Adicionar Produto
                  </Button>
                </div>
                
                {fields.map((field, index) => (
                  <Card key={field.id} className="p-4">
                    <div className="space-y-3">
                      <div className="flex items-start gap-2">
                        <FormField
                          control={form.control}
                          name={`products.${index}.productId`}
                          render={({ field }) => (
                            <FormItem className="flex-1">
                              <FormLabel>Produto {index + 1}</FormLabel>
                              <FormControl>
                                <ComboboxForm
                                  field={{
                                    ...field,
                                    onChange: (value: string) => {
                                      field.onChange(value)
                                      handleProductChange(index, value)
                                    }
                                  }}
                                  options={products.length > 0 
                                    ? products.map((product: Product) => ({
                                        value: product.id.toString(),
                                        label: `${product.name} - R$ ${product.price.toFixed(2)}`,
                                      }))
                                    : [{ value: "no-products", label: "Nenhum produto cadastrado", disabled: true }]
                                  }
                                  placeholder="Selecione o produto"
                                  searchPlaceholder="Buscar produto..."
                                  emptyText="Nenhum produto encontrado"
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                        
                        {fields.length > 1 && (
                          <Button
                            type="button"
                            size="icon"
                            variant="destructive"
                            onClick={() => remove(index)}
                            className="mt-8"
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        )}
                      </div>
                      
                      <div className="grid grid-cols-2 gap-2">
                        <FormField
                          control={form.control}
                          name={`products.${index}.quantity`}
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>Quantidade</FormLabel>
                              <FormControl>
                                <Input
                                  type="number"
                                  min="1"
                                  {...field}
                                  onChange={(e) => field.onChange(parseInt(e.target.value) || 1)}
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                        
                        <FormField
                          control={form.control}
                          name={`products.${index}.price`}
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>Preço Unit.</FormLabel>
                              <FormControl>
                                <Input
                                  type="number"
                                  step="0.01"
                                  disabled
                                  {...field}
                                  value={field.value.toFixed(2)}
                                />
                              </FormControl>
                            </FormItem>
                          )}
                        />
                      </div>
                      
                      {watchProducts[index]?.productId && watchProducts[index]?.quantity && (
                        <div className="text-sm text-muted-foreground text-right">
                          Subtotal: R$ {(watchProducts[index].price * watchProducts[index].quantity).toFixed(2)}
                        </div>
                      )}
                    </div>
                  </Card>
                ))}
              </div>

              {/* Status */}
              <FormField
                control={form.control}
                name="status"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Status</FormLabel>
                    <FormControl>
                      <ComboboxForm
                        field={field}
                        options={[
                          { value: "Pendente", label: "Pendente" },
                          { value: "Em Preparo", label: "Em Preparo" },
                          { value: "Pronto", label: "Pronto" },
                          { value: "Em Entrega", label: "Em Entrega" },
                          { value: "Entregue", label: "Entregue" },
                          { value: "Cancelado", label: "Cancelado" },
                        ]}
                        placeholder="Selecione o status"
                        searchPlaceholder="Buscar status..."
                        emptyText="Nenhum status encontrado"
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {/* Delivery */}
              <FormField
                control={form.control}
                name="isDelivery"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                      <FormLabel className="text-base">Delivery</FormLabel>
                      <FormDescription>
                        Este pedido é para entrega?
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

              {/* Campos de Delivery */}
              {isDelivery && (
                <Card className="p-4">
                  <CardHeader className="px-0 pt-0">
                    <CardTitle className="text-lg">Informações de Entrega</CardTitle>
                  </CardHeader>
                  <CardContent className="px-0 space-y-4">
                    {/* Usar endereço do cliente */}
                    <FormField
                      control={form.control}
                      name="useClientAddress"
                      render={({ field }) => (
                        <FormItem className="flex flex-row items-center justify-between rounded-lg border p-3">
                          <div className="space-y-0.5">
                            <FormLabel className="text-sm">Usar Endereço do Cliente</FormLabel>
                            <FormDescription className="text-xs">
                              {selectedClient && selectedClient.has_complete_address 
                                ? "Cliente possui endereço completo cadastrado"
                                : "Cliente não possui endereço completo cadastrado"
                              }
                            </FormDescription>
                          </div>
                          <FormControl>
                            <Switch
                              checked={field.value}
                              onCheckedChange={field.onChange}
                              disabled={!selectedClient || !selectedClient.has_complete_address}
                            />
                          </FormControl>
                        </FormItem>
                      )}
                    />

                    {/* Endereço do cliente (readonly) */}
                    {useClientAddress && selectedClient && (
                      <div className="p-3 bg-muted rounded-lg">
                        <h4 className="font-medium mb-2 flex items-center">
                          <MapPin className="w-4 h-4 mr-2" />
                          Endereço do Cliente
                        </h4>
                        <p className="text-sm text-muted-foreground">
                          {selectedClient.full_address}
                        </p>
                      </div>
                    )}

                    {/* Campos de endereço manual */}
                    {!useClientAddress && (
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormField
                          control={form.control}
                          name="deliveryAddress"
                          render={({ field }) => (
                            <FormItem className="md:col-span-2">
                              <FormLabel>Endereço *</FormLabel>
                              <FormControl>
                                <Input placeholder="Rua das Flores, 123" {...field} />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="deliveryNumber"
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
                          name="deliveryComplement"
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
                          name="deliveryNeighborhood"
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
                          name="deliveryCity"
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>Cidade *</FormLabel>
                              <FormControl>
                                <Input placeholder="São Paulo" {...field} />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="deliveryState"
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
                          name="deliveryZipCode"
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

                        <FormField
                          control={form.control}
                          name="deliveryNotes"
                          render={({ field }) => (
                            <FormItem className="md:col-span-2">
                              <FormLabel>Observações da Entrega</FormLabel>
                              <FormControl>
                                <Textarea 
                                  placeholder="Portão azul, interfone 101..."
                                  {...field} 
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                      </div>
                    )}
                  </CardContent>
                </Card>
              )}

              {/* Mesa (apenas se não for delivery) */}
              {!isDelivery && (
                <FormField
                  control={form.control}
                  name="tableId"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Mesa</FormLabel>
                      <FormControl>
                        <ComboboxForm
                          field={field}
                          options={tables.length > 0 
                            ? tables.map((table: Table) => ({
                                value: table.id.toString(),
                                label: `Mesa ${table.name || table.identify} (${table.identify}) - Cap: ${table.capacity}`,
                              }))
                            : [{ value: "no-tables", label: "Nenhuma mesa disponível", disabled: true }]
                          }
                          placeholder="Selecione a mesa"
                          searchPlaceholder="Buscar mesa..."
                          emptyText="Nenhuma mesa encontrada"
                        />
                      </FormControl>
                      <FormDescription>
                        Selecione a mesa para este pedido
                      </FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              )}

              {/* Total */}
              <FormField
                control={form.control}
                name="total"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Total (R$)</FormLabel>
                    <FormControl>
                      <Input
                        type="text"
                        disabled
                        value={`R$ ${field.value.toFixed(2)}`}
                        className="font-bold text-lg bg-muted"
                      />
                    </FormControl>
                    <FormDescription>
                      Calculado automaticamente com base nos produtos
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                  Cancelar
                </Button>
                <Button type="submit">Criar Pedido</Button>
              </DialogFooter>
            </form>
          </Form>
        </DialogContent>
      </Dialog>

      {/* Dialog de novo cliente */}
      {clientDialogOpen && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50" onClick={() => setClientDialogOpen(false)}>
          <div className="bg-background rounded-lg shadow-lg w-full max-w-2xl mx-4" onClick={(e) => e.stopPropagation()}>
            <div className="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
              <div>
                <h2 className="text-lg font-semibold">Novo Cliente</h2>
                <p className="text-sm text-muted-foreground">
                  Adicione um novo cliente com endereço completo ao sistema.
                </p>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="md:col-span-2">
                  <label className="text-sm font-medium">Nome Completo *</label>
                  <Input 
                    placeholder="João Silva" 
                    value={newClientName}
                    onChange={(e) => setNewClientName(e.target.value)}
                    className="mt-1"
                  />
                </div>
                
                <div>
                  <label className="text-sm font-medium">Email *</label>
                  <Input 
                    type="email"
                    placeholder="joao@example.com" 
                    value={newClientEmail}
                    onChange={(e) => setNewClientEmail(e.target.value)}
                    className="mt-1"
                  />
                </div>
                
                <div>
                  <label className="text-sm font-medium">Telefone *</label>
                  <Input 
                    placeholder="(11) 99999-9999" 
                    value={newClientPhone}
                    onChange={(e) => setNewClientPhone(e.target.value)}
                    className="mt-1"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="text-sm font-medium">Endereço</label>
                  <Input 
                    placeholder="Rua das Flores, 123" 
                    value={newClientAddress}
                    onChange={(e) => setNewClientAddress(e.target.value)}
                    className="mt-1"
                  />
                </div>

                <div>
                  <label className="text-sm font-medium">Número</label>
                  <Input 
                    placeholder="123" 
                    value={newClientNumber}
                    onChange={(e) => setNewClientNumber(e.target.value)}
                    className="mt-1"
                  />
                </div>

                <div>
                  <label className="text-sm font-medium">Complemento</label>
                  <Input 
                    placeholder="Apto 101" 
                    value={newClientComplement}
                    onChange={(e) => setNewClientComplement(e.target.value)}
                    className="mt-1"
                  />
                </div>

                <div>
                  <label className="text-sm font-medium">Bairro</label>
                  <Input 
                    placeholder="Centro" 
                    value={newClientNeighborhood}
                    onChange={(e) => setNewClientNeighborhood(e.target.value)}
                    className="mt-1"
                  />
                </div>

                <div>
                  <label className="text-sm font-medium">Cidade</label>
                  <Input 
                    placeholder="São Paulo" 
                    value={newClientCity}
                    onChange={(e) => setNewClientCity(e.target.value)}
                    className="mt-1"
                  />
                </div>

                <div>
                  <label className="text-sm font-medium">Estado</label>
                  <Input 
                    placeholder="SP" 
                    value={newClientState}
                    onChange={(e) => setNewClientState(e.target.value)}
                    className="mt-1"
                  />
                </div>

                <div>
                  <label className="text-sm font-medium">CEP</label>
                  <Input 
                    placeholder="01234-567" 
                    value={newClientZipCode}
                    onChange={(e) => setNewClientZipCode(e.target.value)}
                    className="mt-1"
                  />
                </div>
              </div>
              
              <div className="flex gap-2 pt-4">
                <Button 
                  variant="outline" 
                  onClick={() => setClientDialogOpen(false)}
                  className="flex-1"
                >
                  Cancelar
                </Button>
                <Button 
                  onClick={handleAddClient}
                  className="flex-1"
                  disabled={!newClientName || !newClientEmail || !newClientPhone}
                >
                  Criar Cliente
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
