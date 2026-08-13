"use client";

import { useState, useEffect, useCallback } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { ComboboxForm, ComboboxOption } from "@/components/ui/combobox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { ArrowLeft, Plus, Trash2, UserPlus, Calendar, User, ShoppingCart, Truck, ClipboardCheck, ChevronLeft, ChevronRight, Package, Minus, Wallet } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { OrderStepper } from "@/components/order-stepper";
import { resolveImageUrl } from "@/lib/resolve-image-url";
import { useForm, useFieldArray } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useAuthenticatedClients, useAuthenticatedCatalogProducts, useAuthenticatedTables, useAuthenticatedActivePaymentMethods, useMutation } from "@/hooks/use-authenticated-api";
import { apiClient, endpoints } from "@/lib/api-client";
import { toast } from "sonner";
import { useOrderRefresh } from "@/hooks/use-order-refresh";
import { useAuth } from "@/contexts/auth-context";
import { useBackendValidation } from "@/hooks/use-backend-validation";
import { ClientFormDialog } from "../../clients/components/client-form-dialog";
import { StateCityFormFields } from "@/components/location/state-city-form-fields";
import { useViaCEP } from "@/hooks/use-viacep";
import { applyCepToForm } from "@/lib/apply-cep-to-form";
import { maskZipCode } from "@/lib/masks";
import { SuccessAlert } from "../components/success-alert";

const orderFormSchema = z.object({
  clientId: z.string().min(1, "Por favor, selecione um cliente."),
  products: z.array(z.object({
    productId: z.string().min(1, { message: "Selecione um produto." }),
    quantity: z.number().min(1, { message: "Quantidade deve ser maior que 0." }),
    price: z.number().min(0),
  })).min(1, { message: "Adicione pelo menos um produto." }),
  status: z.string().min(1, "Por favor, selecione um status."),
  isDelivery: z.boolean(),
  tableId: z.string().optional(),
  total: z.number().min(0),
  discountValue: z.number().min(0).optional(),
  discountType: z.enum(["percentage", "fixed"]).optional(),
  paymentMethodId: z.string().min(1, "Por favor, selecione uma forma de pagamento."),
  
  // Scheduling fields
  isScheduled: z.boolean(),
  scheduledAt: z.string().optional(),

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
  if (!data.isDelivery && (!data.tableId || data.tableId === "")) {
    return false;
  }
  return true;
}, {
  message: "Por favor, selecione uma mesa.",
  path: ["tableId"],
}).refine((data) => {
  if (data.isDelivery && !data.useClientAddress) {
    if (!data.deliveryAddress || !data.deliveryCity) {
      return false;
    }
  }
  return true;
}, {
  message: "Endereço e cidade são obrigatórios para delivery.",
  path: ["deliveryAddress"],
});

interface OrderFormValues {
  clientId: string;
  products: {
    productId: string;
    quantity: number;
    price: number;
  }[];
  status: string;
  isDelivery: boolean;
  tableId?: string;
  total: number;
  discountValue?: number;
  discountType?: "percentage" | "fixed";
  paymentMethodId: string;
  isScheduled: boolean;
  scheduledAt?: string;
  useClientAddress: boolean;
  deliveryAddress?: string;
  deliveryCity?: string;
  deliveryState?: string;
  deliveryZipCode?: string;
  deliveryNeighborhood?: string;
  deliveryNumber?: string;
  deliveryComplement?: string;
  deliveryNotes?: string;
}

interface Client {
  id: number;
  identify: string;
  uuid: string;
  name: string;
  email: string;
  phone: string;
  address?: string;
  city?: string;
  state?: string;
  zip_code?: string;
  neighborhood?: string;
  number?: string;
  complement?: string;
  has_complete_address?: boolean;
}

interface Product {
  id?: number
  identify: string
  uuid: string
  name: string
  description: string
  price: string | number
  price_cost?: string | number
  promotional_price?: string | number | null
  brand?: string | null
  sku?: string | null
  weight?: string | number | null
  height?: string | number | null
  width?: string | number | null
  depth?: string | number | null
  shipping_info?: string | null
  warehouse_location?: string | null
  variations?: Array<{
    type: string
    value: string
  }>
  qtd_stock: number
  is_active: boolean
  created_at: string
  categories?: Array<{
    identify: string
    name: string
    description: string
    url: string
    status: string
    created_at: string
  }>
}

interface Table {
  id: number;
  identify: string;
  uuid: string;
  name: string;
  capacity: number;
}

interface PaymentMethod {
  id?: number;
  uuid: string;
  name: string;
  description?: string;
  status: string;
  is_active: boolean;
  tenant_id?: number;
  created_at: string;
  updated_at: string;
}

export default function NewOrderPage() {
  const router = useRouter();
  const { triggerRefresh } = useOrderRefresh();
  const auth = useAuth();
  const { data: clientsData, loading: clientsLoading, refetch: refetchClients } = useAuthenticatedClients();
  const { data: tablesData, loading: tablesLoading } = useAuthenticatedTables();
  const { data: paymentMethodsData, loading: paymentMethodsLoading } = useAuthenticatedActivePaymentMethods();
  const { mutate: createClient } = useMutation();
  const { loading: loadingCEP, searchCEP } = useViaCEP();
  const [creating, setCreating] = useState(false);
  const [validatingStep, setValidatingStep] = useState(false);
  const [currentStep, setCurrentStep] = useState(0);
  const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set());
  const [productSearch, setProductSearch] = useState("");

  // Hook autenticado para produtos (inicializado após os outros)
  const { data: productsDataAuth, loading: productsLoadingAuth } = useAuthenticatedCatalogProducts();
  
  // Estado para controlar modal de adicionar cliente
  const [clientDialogOpen, setClientDialogOpen] = useState(false);
  
  // Estado local para forçar atualização de clientes
  const [localClients, setLocalClients] = useState<Client[]>([]);
  const [successAlertOpen, setSuccessAlertOpen] = useState(false);
  const [successMessage, setSuccessMessage] = useState('Pedido cadastrado com sucesso');
  const [createdOrderId, setCreatedOrderId] = useState<string | null>(null);

  const form = useForm<OrderFormValues>({
    resolver: zodResolver(orderFormSchema),
    defaultValues: {
      clientId: "",
      products: [{ productId: "", quantity: 1, price: 0 }],
      status: "Pendente",
      isDelivery: false,
      tableId: "",
      total: 0,
      discountValue: 0,
      discountType: "percentage",
      paymentMethodId: "",
      isScheduled: false,
      scheduledAt: "",
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
  });

  const { fields, append, remove } = useFieldArray({
    control: form.control,
    name: "products",
  });

  const { handleBackendErrors } = useBackendValidation(form.setError);

  const isDelivery = form.watch("isDelivery");
  const isScheduled = form.watch("isScheduled");
  const useClientAddress = form.watch("useClientAddress");
  const selectedClientId = form.watch("clientId");
  const watchProducts = form.watch("products");
  const discountValue = form.watch("discountValue");
  const discountType = form.watch("discountType");

  const handleDeliveryCepLookup = useCallback(
    async (cepValue: string) => {
      if (!cepValue || useClientAddress) return;

      const cleanCEP = cepValue.replace(/\D/g, "");
      if (cleanCEP.length !== 8) {
        return;
      }

      try {
        const address = await searchCEP(cepValue);
        if (address) {
          applyCepToForm(form.setValue, address, {
            address: "deliveryAddress",
            neighborhood: "deliveryNeighborhood",
            state: "deliveryState",
            city: "deliveryCity",
          });
        }
      } catch {
        // Erro já é tratado pelo useViaCEP com toast
      }
    },
    [form, searchCEP, useClientAddress]
  );

  // Debug dos hooks após todas as declarações

  // Transformar dados da API com logs mais detalhados
  const getArrayFromData = (data: any) => {

    // );
    
    if (!data) {

      return [];
    }
    
    if (Array.isArray(data)) {

      // );
      return data;
    }
    
    if (data.data && Array.isArray(data.data)) {

      // );
      return data.data;
    }
    
    if (data.success && data.data && Array.isArray(data.data)) {

      // );
      return data.data;
    }
    
    // Tentar extrair se for Laravel Resource Collection
    if (data.data && data.data.data && Array.isArray(data.data.data)) {

      return data.data.data;
    }
    
    // );
    return [];
  };

  const clientsFromApi = getArrayFromData(clientsData).filter((c: any) => c && c.id);
  
  // Sincronizar com dados da API
  useEffect(() => {
    // Sincronização de clientes
    if (clientsFromApi.length > 0) {
      setLocalClients(clientsFromApi)
    }
  }, [clientsData]);
  
  const clients = localClients.length > 0 ? localClients : clientsFromApi;
  // Filtrar apenas produtos ativos
  const products = getArrayFromData(productsDataAuth)
    .filter((p: any) => {
      // Verificar se produto tem os campos necessários e está ativo
      const hasRequiredFields = p && p.identify && p.name;
      const isActive = p.is_active === true || p.is_active === 1;
      const hasStock = p.qtd_stock > 0;

      return hasRequiredFields && isActive && hasStock;
    });
  const tables = getArrayFromData(tablesData).filter((t: any) => t && t.id);
  const paymentMethods = getArrayFromData(paymentMethodsData).filter((pm: any) => pm && pm.uuid && pm.is_active);

  // .length);

  const clientOptions: ComboboxOption[] = clients.map((client: Client) => ({
    value: client.uuid || client.identify || client.id.toString(),
    label: client.phone ? `${client.name} - ${client.phone}` : client.name,
  }));

  const tableOptions: ComboboxOption[] = tables
    .filter((table: Table) => Boolean(table.uuid || table.identify))
    .map((table: Table) => ({
      value: table.uuid || table.identify,
      label: `${table.name} (${table.capacity} pessoas)`,
    }));

  const paymentMethodOptions: ComboboxOption[] = paymentMethods.map((paymentMethod: PaymentMethod) => ({
    value: paymentMethod.uuid,
    label: paymentMethod.name,
  }));

  // Buscar dados do cliente selecionado
  const selectedClient = clients.find((c: Client) => 
    c.uuid === selectedClientId || c.identify === selectedClientId || c.id.toString() === selectedClientId
  );

  // Preencher endereço do cliente automaticamente
  useEffect(() => {
    if (isDelivery && useClientAddress && selectedClient && selectedClient.has_complete_address) {
      form.setValue("deliveryAddress", selectedClient.address || "");
      form.setValue("deliveryCity", selectedClient.city || "");
      form.setValue("deliveryState", selectedClient.state || "");
      form.setValue("deliveryZipCode", selectedClient.zip_code || "");
      form.setValue("deliveryNeighborhood", selectedClient.neighborhood || "");
      form.setValue("deliveryNumber", selectedClient.number || "");
      form.setValue("deliveryComplement", selectedClient.complement || "");
    } else if (isDelivery && !useClientAddress) {
      form.setValue("deliveryAddress", "");
      form.setValue("deliveryCity", "");
      form.setValue("deliveryState", "");
      form.setValue("deliveryZipCode", "");
      form.setValue("deliveryNeighborhood", "");
      form.setValue("deliveryNumber", "");
      form.setValue("deliveryComplement", "");
    }
  }, [useClientAddress, selectedClient, isDelivery, form]);

  // Função helper para converter preço para número
  const getPriceAsNumber = (price: string | number | undefined): number => {
    if (typeof price === 'number') {
      return price;
    }
    if (typeof price === 'string') {
      return parseFloat(price) || 0;
    }
    return 0;
  };

  // Calcular total automaticamente
  useEffect(() => {
    const subtotal = watchProducts.reduce((sum, item) => {
      const product = products.find((p: Product) => 
        p.uuid === item.productId || p.identify === item.productId
      );
      if (product) {
        // Usar preço promocional se disponível, senão preço normal
        const price = product.promotional_price 
          ? getPriceAsNumber(product.promotional_price)
          : getPriceAsNumber(product.price);
        return sum + (price * item.quantity);
      }
      return sum + (item.price * item.quantity); // Usar preço do item se produto não encontrado
    }, 0);
    
    let discount = 0;
    if (discountValue && discountValue > 0) {
      if (discountType === "percentage") {
        discount = (subtotal * discountValue) / 100;
      } else {
        discount = discountValue;
      }
    }
    
    const total = Math.max(0, subtotal - discount);
    form.setValue("total", total);
  }, [watchProducts, products, discountValue, discountType, form]);

  // Função para adicionar cliente
  const handleAddClient = async (clientData: any) => {
    try {
      const result = await createClient(
        endpoints.clients.create,
        'POST',
        clientData
      );
      
      if (result && typeof result === 'object' && 'data' in result && result.data && typeof result.data === 'object' && 'id' in result.data) {
        const newClient = (result.data as any);
        
        if (process.env.NODE_ENV === 'development') {

        }
        
        // Adicionar cliente à lista local imediatamente
        setLocalClients(prev => {
          const updated = [newClient, ...prev];
          return updated;
        });
        
        // Recarregar lista de clientes para sincronizar com backend
        setTimeout(async () => {
          if (process.env.NODE_ENV === 'development') {

          }
          await refetchClients();
        }, 100);
        
        // Selecionar automaticamente o cliente criado
        const clientId = newClient.uuid || newClient.identify || newClient.id.toString();
        form.setValue('clientId', clientId);
        
        if (process.env.NODE_ENV === 'development') {

        }
        
        // Fechar o modal de adicionar cliente
        setClientDialogOpen(false);
        
        // Extrair mensagem de sucesso do backend
        const successMessage = (result as any)?.message || `${newClient.name} foi cadastrado e selecionado com sucesso!`;
        
        // Mostrar sucesso
        toast.success(successMessage);
        
        if (process.env.NODE_ENV === 'development') {

        }
      } else {
        if (process.env.NODE_ENV === 'development') {

        }
      }
    } catch (error) {
      // Erro já é tratado pelo ClientFormDialog
      if (process.env.NODE_ENV === 'development') {

      }
      throw error;
    } finally {
      if (process.env.NODE_ENV === 'development') {

      }
    }
  };

  const orderFieldMappings: Record<string, keyof OrderFormValues | string> = {
    client_id: "clientId",
    table: "tableId",
    products: "products",
    "products.*.qty": "products",
    "products.*.identify": "products",
    delivery_address: "deliveryAddress",
    delivery_city: "deliveryCity",
    delivery_state: "deliveryState",
    delivery_zip_code: "deliveryZipCode",
    delivery_neighborhood: "deliveryNeighborhood",
    delivery_number: "deliveryNumber",
    payment_method_id: "paymentMethodId",
    is_delivery: "isDelivery",
    scheduled_at: "scheduledAt",
  };

  const FIELD_STEP: Partial<Record<keyof OrderFormValues | string, number>> = {
    clientId: 0,
    products: 1,
    isDelivery: 2,
    tableId: 2,
    useClientAddress: 2,
    deliveryAddress: 2,
    deliveryCity: 2,
    deliveryState: 2,
    deliveryZipCode: 2,
    deliveryNeighborhood: 2,
    deliveryNumber: 2,
    isScheduled: 2,
    scheduledAt: 2,
    status: 2,
    paymentMethodId: 3,
  };

  const buildStepPayload = (step: number) => {
    const values = form.getValues();
    const tenantId = auth.user?.tenant?.uuid || auth.user?.tenant_id;
    const base = {
      step,
      token_company: tenantId ? String(tenantId) : undefined,
    };

    switch (step) {
      case 0:
        return { ...base, client_id: values.clientId || null };
      case 1:
        return {
          ...base,
          products: values.products
            .filter((p) => p.productId)
            .map((product) => ({
              identify: product.productId,
              qty: product.quantity,
              price: product.price,
            })),
        };
      case 2:
        return {
          ...base,
          is_delivery: values.isDelivery,
          table: values.isDelivery ? null : values.tableId || null,
          use_client_address: values.useClientAddress,
          delivery_address: values.deliveryAddress,
          delivery_city: values.deliveryCity,
          is_scheduled: values.isScheduled,
          scheduled_at: values.isScheduled && values.scheduledAt ? values.scheduledAt : null,
        };
      case 3:
        return { ...base, payment_method_id: values.paymentMethodId };
      default:
        return base;
    }
  };

  const navigateToFirstErrorStep = () => {
    const erroredFields = Object.keys(form.formState.errors);
    const steps = erroredFields
      .map((field) => FIELD_STEP[field])
      .filter((step): step is number => step !== undefined);
    if (steps.length === 0) return;
    setCurrentStep(Math.min(...steps));
  };

  useEffect(() => {
    navigateToFirstErrorStep();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [form.formState.errors]);

  const onSubmit = async (data: OrderFormValues) => {
    try {
      setCreating(true);

      // Obter token da empresa do usuário autenticado
      const user = auth.user;

      const tenantId = user?.tenant?.uuid || user?.tenant_id;

      if (!tenantId) {

        toast.error('Usuário não possui empresa associada. Por favor, faça login novamente.');
        return;
      }
      
      // Converter dados para o formato esperado pelo backend
      const orderData = {
        token_company: tenantId,
        client_id: data.clientId || null,
        table: data.isDelivery ? null : data.tableId,
        payment_method_id: data.paymentMethodId,
        is_scheduled: data.isScheduled,
        scheduled_at: data.isScheduled && data.scheduledAt ? data.scheduledAt : null,
        is_delivery: data.isDelivery,
        use_client_address: data.useClientAddress,
        delivery_address: data.deliveryAddress,
        delivery_city: data.deliveryCity,
        delivery_state: data.deliveryState,
        delivery_zip_code: data.deliveryZipCode,
        delivery_neighborhood: data.deliveryNeighborhood,
        delivery_number: data.deliveryNumber,
        delivery_complement: data.deliveryComplement,
        delivery_notes: data.deliveryNotes,
        comment: '',
        products: data.products.map(product => ({
          identify: product.productId, // Este já será o UUID agora
          qty: product.quantity,
          price: product.price
        }))
      };

      const response = await apiClient.post(endpoints.orders.create, orderData);
      
      if (response.success) {
        const orderResponse: any = response.data ?? {};
        const message = response.message || 'Pedido cadastrado com sucesso';
        const orderId =
          orderResponse.identify ||
          orderResponse.order_id ||
          orderResponse.uuid ||
          (orderResponse.id ? String(orderResponse.id) : null);

        triggerRefresh();
        setCreatedOrderId(orderId);
        setSuccessMessage(message);
        setSuccessAlertOpen(true);
      } else {
        toast.error(response.message || 'Erro ao criar pedido');
      }
    } catch (error: any) {
      const handled = handleBackendErrors(error, orderFieldMappings as any);
      const firstBackendError =
        error?.errors && typeof error.errors === "object"
          ? (Object.values(error.errors)[0] as string[] | string | undefined)
          : undefined;
      const firstMessage = Array.isArray(firstBackendError)
        ? firstBackendError[0]
        : firstBackendError;

      if (firstMessage) {
        toast.error(String(firstMessage));
      } else if (!handled) {
        toast.error(error?.data?.message || error?.message || "Erro ao criar pedido");
      } else {
        toast.error(error?.message || "Corrija os campos destacados e tente novamente.");
      }

      // Volta para a etapa do primeiro campo com erro (mesa => Entrega, etc.)
      const backendFields = error?.errors ? Object.keys(error.errors) : [];
      const mappedSteps = backendFields
        .map((field) => FIELD_STEP[orderFieldMappings[field] || field])
        .filter((step): step is number => step !== undefined);
      if (mappedSteps.length > 0) {
        setCurrentStep(Math.min(...mappedSteps));
      } else {
        navigateToFirstErrorStep();
      }
    } finally {
      setCreating(false);
    }
  };

  // Calcular subtotal para exibição
  const subtotal = watchProducts.reduce((sum, item) => {
    const product = products.find((p: Product) => 
      p.uuid === item.productId || p.identify === item.productId
    );
    if (product) {
      // Usar preço promocional se disponível, senão preço normal
      const price = product.promotional_price 
        ? getPriceAsNumber(product.promotional_price)
        : getPriceAsNumber(product.price);
      return sum + (price * item.quantity);
    }
    return sum + (item.price * item.quantity); // Usar preço do item se produto não encontrado
  }, 0);

  const discount = discountValue && discountValue > 0 ?
    (discountType === "percentage" ? (subtotal * discountValue) / 100 : discountValue) : 0;

  const total = Math.max(0, subtotal - discount);
  const fmt = new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" });

  const steps = [
    { label: "Cliente", icon: User },
    { label: "Produtos", icon: ShoppingCart },
    { label: "Entrega", icon: Truck },
    { label: "Financeiro", icon: Wallet },
    { label: "Revisão", icon: ClipboardCheck },
  ];

  const validateStepFrontend = async (step: number): Promise<boolean> => {
    switch (step) {
      case 0:
        return form.trigger(["clientId"]);
      case 1:
        return form.trigger(["products"]);
      case 2: {
        const fields: (keyof OrderFormValues)[] = ["isDelivery", "tableId", "isScheduled", "scheduledAt"];
        const v = form.getValues();
        if (v.isDelivery && !v.useClientAddress) {
          fields.push("deliveryAddress", "deliveryCity");
        }
        const ok = await form.trigger(fields);
        if (!ok) return false;
        if (!v.isDelivery && !v.tableId) {
          form.setError("tableId", { type: "manual", message: "Por favor, selecione uma mesa." });
          return false;
        }
        if (v.isDelivery && !v.useClientAddress && (!v.deliveryAddress || !v.deliveryCity)) {
          form.setError("deliveryAddress", { type: "manual", message: "Endereço e cidade são obrigatórios para delivery." });
          return false;
        }
        if (v.isScheduled && !v.scheduledAt) {
          form.setError("scheduledAt", { type: "manual", message: "Informe a data e hora do agendamento." });
          return false;
        }
        return true;
      }
      case 3:
        return form.trigger(["paymentMethodId"]);
      default:
        return true;
    }
  };

  const goNext = async () => {
    const frontendOk = await validateStepFrontend(currentStep);
    if (!frontendOk) {
      if (currentStep === 0) toast.error("Selecione um cliente.");
      else if (currentStep === 1) toast.error("Adicione pelo menos um produto.");
      else if (currentStep === 2) toast.error("Preencha os dados de entrega e agendamento.");
      else if (currentStep === 3) toast.error("Selecione uma forma de pagamento.");
      return;
    }

    try {
      setValidatingStep(true);
      await apiClient.post(endpoints.orders.validate, buildStepPayload(currentStep));
      setCompletedSteps((prev) => new Set(prev).add(currentStep));
      setCurrentStep((s) => Math.min(s + 1, 4));
    } catch (error: any) {
      const handled = handleBackendErrors(error, orderFieldMappings as any);
      const firstBackendError =
        error?.errors && typeof error.errors === "object"
          ? (Object.values(error.errors)[0] as string[] | string | undefined)
          : undefined;
      const firstMessage = Array.isArray(firstBackendError)
        ? firstBackendError[0]
        : firstBackendError;

      if (firstMessage) {
        toast.error(String(firstMessage));
      } else if (!handled) {
        toast.error(error?.message || "Não foi possível validar esta etapa.");
      }

      const backendFields = error?.errors ? Object.keys(error.errors) : [];
      const mappedSteps = backendFields
        .map((field) => FIELD_STEP[orderFieldMappings[field] || field])
        .filter((step): step is number => step !== undefined);
      if (mappedSteps.length > 0) {
        setCurrentStep(Math.min(...mappedSteps));
      }
    } finally {
      setValidatingStep(false);
    }
  };

  const goBack = () => setCurrentStep((s) => Math.max(s - 1, 0));

  const goToStep = (step: number) => {
    if (step <= currentStep || completedSteps.has(step)) setCurrentStep(step);
  };

  const filteredProducts = productSearch
    ? products.filter((p: Product) => p.name.toLowerCase().includes(productSearch.toLowerCase()))
    : products;

  const addProductToOrder = (product: Product) => {
    const productId = product.uuid || product.identify;
    const existing = watchProducts.findIndex((p) => p.productId === productId);
    const price = product.promotional_price
      ? getPriceAsNumber(product.promotional_price)
      : getPriceAsNumber(product.price);
    if (existing >= 0) {
      form.setValue(`products.${existing}.quantity`, watchProducts[existing].quantity + 1);
    } else {
      const emptyIndex = watchProducts.findIndex((p) => p.productId === "");
      if (emptyIndex >= 0) {
        form.setValue(`products.${emptyIndex}.productId`, productId);
        form.setValue(`products.${emptyIndex}.quantity`, 1);
        form.setValue(`products.${emptyIndex}.price`, price);
      } else {
        append({ productId, quantity: 1, price });
      }
    }
  };

  const updateQuantity = (index: number, delta: number) => {
    const current = watchProducts[index].quantity;
    const next = current + delta;
    if (next < 1) {
      if (fields.length > 1) {
        remove(index);
      } else {
        form.setValue(`products.${index}.productId`, "");
        form.setValue(`products.${index}.quantity`, 1);
        form.setValue(`products.${index}.price`, 0);
      }
    } else {
      form.setValue(`products.${index}.quantity`, next);
    }
  };

  const cartItems = watchProducts.filter((p) => p.productId !== "");
  const cartCount = cartItems.reduce((sum, p) => sum + p.quantity, 0);

  return (
    <div className="flex flex-col min-h-[calc(100vh-4rem)] py-2 px-3 sm:px-6">
      <SuccessAlert
        open={successAlertOpen}
        onOpenChange={(open) => {
          if (!open) {
            setSuccessAlertOpen(false);
            if (createdOrderId) {
              router.push(`/orders/success?orderId=${createdOrderId}`);
            } else {
              router.push('/orders');
            }
            setCreatedOrderId(null);
          } else {
            setSuccessAlertOpen(true);
          }
        }}
        title="Pedido criado"
        message={successMessage}
      />
      <div className="flex items-center gap-3 mb-4">
        <Button variant="ghost" size="icon" onClick={() => router.back()} className="shrink-0">
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <h1 className="text-lg sm:text-2xl font-bold">Novo Pedido</h1>
      </div>

      <div className="mb-6 px-1">
        <OrderStepper currentStep={currentStep} steps={steps} onStepClick={goToStep} completedSteps={completedSteps} />
      </div>

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="flex-1 flex flex-col">
          <div className="flex-1 flex flex-col lg:flex-row gap-4">
            <div className="flex-1 min-w-0">

              {/* ETAPA 0: CLIENTE */}
              {currentStep === 0 && (
                <Card>
                  <CardHeader>
                    <CardTitle>Selecione o Cliente</CardTitle>
                    <CardDescription>Escolha ou cadastre um novo cliente</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <FormField
                      control={form.control}
                      name="clientId"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Cliente *</FormLabel>
                          <div className="flex gap-2">
                            <FormControl>
                              <ComboboxForm
                                field={field}
                                options={clientOptions}
                                placeholder="Buscar cliente..."
                                searchPlaceholder="Digite o nome..."
                                className="flex-1"
                              />
                            </FormControl>
                            <Button type="button" variant="outline" onClick={() => setClientDialogOpen(true)} className="h-10 px-3">
                              <UserPlus className="h-4 w-4 sm:mr-2" />
                              <span className="hidden sm:inline">Novo</span>
                            </Button>
                          </div>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                    {selectedClient && (
                      <div className="p-4 bg-muted rounded-lg space-y-1">
                        <p className="font-semibold">{selectedClient.name}</p>
                        {selectedClient.phone && <p className="text-sm text-muted-foreground">Tel: {selectedClient.phone}</p>}
                        {selectedClient.email && <p className="text-sm text-muted-foreground">{selectedClient.email}</p>}
                        {selectedClient.has_complete_address && <p className="text-xs text-green-600 mt-1">Endereço completo disponível</p>}
                      </div>
                    )}
                  </CardContent>
                </Card>
              )}

              {/* ETAPA 1: PRODUTOS */}
              {currentStep === 1 && (
                <div className="space-y-4">
                  <div className="relative">
                    <ShoppingCart className="absolute left-3 top-3.5 h-4 w-4 text-muted-foreground" />
                    <Input
                      placeholder="Buscar produto..."
                      value={productSearch}
                      onChange={(e) => setProductSearch(e.target.value)}
                      className="pl-10 h-12 text-base"
                    />
                  </div>
                  <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div className="lg:col-span-2">
                      <p className="text-sm text-muted-foreground mb-3">{filteredProducts.length} produto(s)</p>
                      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-3 max-h-[50vh] lg:max-h-[60vh] overflow-y-auto pr-1">
                        {filteredProducts.map((product: Product) => {
                          const price = product.promotional_price ? getPriceAsNumber(product.promotional_price) : getPriceAsNumber(product.price);
                          const inCart = watchProducts.some((p) => p.productId === (product.uuid || product.identify));
                          return (
                            <button
                              key={product.uuid || product.identify}
                              type="button"
                              onClick={() => addProductToOrder(product)}
                              className={`relative flex flex-col rounded-lg border p-2 sm:p-3 text-left transition-colors hover:bg-accent ${inCart ? "border-primary bg-primary/5" : ""}`}
                            >
                              <div className="aspect-square w-full rounded-md bg-muted mb-2 overflow-hidden">
                                {(product as Product & { url?: string; image?: string }).url || (product as Product & { url?: string; image?: string }).image ? (
                                  <img
                                    src={resolveImageUrl((product as Product & { url?: string; image?: string }).url || (product as Product & { url?: string; image?: string }).image) || ""}
                                    alt={product.name}
                                    className="h-full w-full object-cover"
                                    onError={(e) => { e.currentTarget.style.display = "none"; }}
                                  />
                                ) : (
                                  <div className="flex h-full items-center justify-center"><Package className="h-8 w-8 text-muted-foreground/30" /></div>
                                )}
                              </div>
                              <p className="text-xs sm:text-sm font-medium line-clamp-2 leading-tight">{product.name}</p>
                              <p className="text-xs sm:text-sm font-bold text-primary mt-1">{fmt.format(price)}</p>
                              {inCart && <Badge className="absolute top-1 right-1 text-[10px] px-1.5">No carrinho</Badge>}
                            </button>
                          );
                        })}
                      </div>
                    </div>
                    <Card className="lg:sticky lg:top-4 h-fit">
                      <CardHeader className="pb-3">
                        <CardTitle className="text-base flex items-center gap-2">
                          <ShoppingCart className="h-4 w-4" /> Carrinho
                          {cartCount > 0 && <Badge variant="secondary">{cartCount}</Badge>}
                        </CardTitle>
                      </CardHeader>
                      <CardContent className="space-y-2">
                        {cartItems.length === 0 ? (
                          <p className="text-sm text-muted-foreground text-center py-4">Nenhum produto adicionado</p>
                        ) : (
                          <>
                            {fields.map((field, index) => {
                              if (!watchProducts[index]?.productId) return null;
                              const prod = products.find((p: Product) => (p.uuid || p.identify) === watchProducts[index].productId);
                              if (!prod) return null;
                              return (
                                <div key={field.id} className="flex items-center gap-2 rounded-md border p-2">
                                  <div className="flex-1 min-w-0">
                                    <p className="text-xs sm:text-sm font-medium truncate">{prod.name}</p>
                                    <p className="text-xs text-muted-foreground">{fmt.format(watchProducts[index].price)}</p>
                                  </div>
                                  <div className="flex items-center gap-1 shrink-0">
                                    <Button type="button" variant="outline" size="icon" className="h-7 w-7" onClick={() => updateQuantity(index, -1)}><Minus className="h-3 w-3" /></Button>
                                    <Input type="number" min="1" step="1" value={watchProducts[index].quantity} onChange={(e) => { const v = parseInt(e.target.value, 10); form.setValue(`products.${index}.quantity`, Number.isFinite(v) && v > 0 ? v : 1); }} className="h-7 w-14 text-center px-1 text-sm" aria-label="Quantidade" />
                                    <Button type="button" variant="outline" size="icon" className="h-7 w-7" onClick={() => updateQuantity(index, 1)}><Plus className="h-3 w-3" /></Button>
                                  </div>
                                  <Button type="button" variant="ghost" size="icon" className="h-7 w-7 text-muted-foreground hover:text-destructive shrink-0" onClick={() => { if (fields.length > 1) remove(index); else { form.setValue(`products.${index}.productId`, ""); form.setValue(`products.${index}.quantity`, 1); form.setValue(`products.${index}.price`, 0); } }}><Trash2 className="h-3 w-3" /></Button>
                                </div>
                              );
                            })}
                            <div className="flex justify-between font-bold pt-2 border-t"><span>Subtotal</span><span>{fmt.format(subtotal)}</span></div>
                          </>
                        )}
                      </CardContent>
                    </Card>
                  </div>
                </div>
              )}

              {/* ETAPA 2: ENTREGA */}
              {currentStep === 2 && (
                <div className="space-y-4">
                  <Card>
                    <CardHeader>
                      <CardTitle className="text-base">Tipo de Pedido</CardTitle>
                      <CardDescription>Delivery ou consumo no local</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <FormField control={form.control} name="isDelivery" render={({ field }) => (
                        <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                          <div><FormLabel className="text-base">Delivery</FormLabel><p className="text-sm text-muted-foreground">Pedido para entrega</p></div>
                          <FormControl><Switch checked={field.value} onCheckedChange={field.onChange} /></FormControl>
                        </FormItem>
                      )} />
                      {!isDelivery && (
                        <FormField control={form.control} name="tableId" render={({ field }) => (
                          <FormItem><FormLabel>Mesa *</FormLabel><FormControl><ComboboxForm field={field} options={tableOptions} placeholder="Selecionar mesa..." searchPlaceholder="Buscar mesa..." /></FormControl><FormMessage /></FormItem>
                        )} />
                      )}
                      <FormField control={form.control} name="status" render={({ field }) => (
                        <FormItem><FormLabel>Status</FormLabel>
                          <Select onValueChange={field.onChange} value={field.value}><FormControl><SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger></FormControl><SelectContent>
                            <SelectItem value="Pendente">Pendente</SelectItem>
                            <SelectItem value="Aceito">Aceito</SelectItem>
                            <SelectItem value="Preparo">Preparo</SelectItem>
                            <SelectItem value="Concluído">Concluído</SelectItem>
                            <SelectItem value="Cancelado">Cancelado</SelectItem>
                          </SelectContent></Select>
                        </FormItem>
                      )} />
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader>
                      <CardTitle className="flex items-center gap-2 text-base"><Calendar className="h-5 w-5" /> Agendamento</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <FormField control={form.control} name="isScheduled" render={({ field }) => (
                        <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                          <div><FormLabel className="text-base">Pedido Agendado</FormLabel><p className="text-sm text-muted-foreground">Definir data e hora para este pedido</p></div>
                          <FormControl><Switch checked={field.value} onCheckedChange={field.onChange} /></FormControl>
                        </FormItem>
                      )} />
                      {isScheduled && (
                        <FormField control={form.control} name="scheduledAt" render={({ field }) => (
                          <FormItem>
                            <FormLabel>Data e Hora do Agendamento *</FormLabel>
                            <FormControl>
                              <Input type="datetime-local" min={new Date(Date.now() + 60000).toISOString().slice(0, 16)} {...field} value={field.value || ""} />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )} />
                      )}
                    </CardContent>
                  </Card>

                  {isDelivery && (
                    <Card>
                      <CardHeader><CardTitle className="text-base">Endereço de Entrega</CardTitle></CardHeader>
                      <CardContent className="space-y-4">
                        {selectedClient?.has_complete_address && (
                          <FormField control={form.control} name="useClientAddress" render={({ field }) => (
                            <FormItem className="flex items-center justify-between rounded-lg border p-3"><FormLabel>Usar endereço do cliente</FormLabel><FormControl><Switch checked={field.value} onCheckedChange={field.onChange} /></FormControl></FormItem>
                          )} />
                        )}
                        {(!useClientAddress || !selectedClient?.has_complete_address) && (
                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormField control={form.control} name="deliveryZipCode" render={({ field }) => (
                              <FormItem><FormLabel>CEP</FormLabel><FormControl><Input placeholder="01000-000" value={field.value || ""} onChange={(e) => field.onChange(maskZipCode(e.target.value))} onBlur={(e) => { field.onBlur(); handleDeliveryCepLookup(e.target.value); }} maxLength={9} disabled={loadingCEP} /></FormControl></FormItem>
                            )} />
                            <FormField control={form.control} name="deliveryAddress" render={({ field }) => (
                              <FormItem className="sm:col-span-2"><FormLabel>Endereço *</FormLabel><FormControl><Input placeholder="Rua..." {...field} value={field.value || ""} /></FormControl><FormMessage /></FormItem>
                            )} />
                            <FormField control={form.control} name="deliveryNumber" render={({ field }) => (
                              <FormItem><FormLabel>Número</FormLabel><FormControl><Input placeholder="123" {...field} value={field.value || ""} /></FormControl></FormItem>
                            )} />
                            <FormField control={form.control} name="deliveryNeighborhood" render={({ field }) => (
                              <FormItem><FormLabel>Bairro</FormLabel><FormControl><Input placeholder="Centro" {...field} value={field.value || ""} /></FormControl></FormItem>
                            )} />
                            <StateCityFormFields control={form.control} stateFieldName="deliveryState" cityFieldName="deliveryCity" stateLabel="Estado" cityLabel="Cidade" required gridCols="equal" />
                            <FormField control={form.control} name="deliveryComplement" render={({ field }) => (
                              <FormItem className="sm:col-span-2"><FormLabel>Complemento</FormLabel><FormControl><Input placeholder="Apto 101" {...field} value={field.value || ""} /></FormControl></FormItem>
                            )} />
                            <FormField control={form.control} name="deliveryNotes" render={({ field }) => (
                              <FormItem className="sm:col-span-2"><FormLabel>Observações</FormLabel><FormControl><Textarea placeholder="Observações..." {...field} value={field.value || ""} /></FormControl></FormItem>
                            )} />
                          </div>
                        )}
                      </CardContent>
                    </Card>
                  )}
                </div>
              )}

              {/* ETAPA 3: FINANCEIRO */}
              {currentStep === 3 && (
                <Card>
                  <CardHeader><CardTitle className="text-base">Pagamento e Desconto</CardTitle></CardHeader>
                  <CardContent className="space-y-4">
                    <FormField control={form.control} name="paymentMethodId" render={({ field }) => (
                      <FormItem><FormLabel>Forma de Pagamento *</FormLabel><FormControl>
                        <ComboboxForm field={field} options={paymentMethodsLoading ? [{ value: "loading", label: "Carregando...", disabled: true }] : paymentMethodOptions.length > 0 ? paymentMethodOptions : [{ value: "none", label: "Nenhuma disponível", disabled: true }]} placeholder="Selecionar..." searchPlaceholder="Buscar..." />
                      </FormControl><FormMessage /></FormItem>
                    )} />
                    <div className="grid grid-cols-2 gap-3">
                      <FormField control={form.control} name="discountType" render={({ field }) => (
                        <FormItem><FormLabel>Desconto</FormLabel><Select onValueChange={field.onChange} value={field.value}><FormControl><SelectTrigger><SelectValue /></SelectTrigger></FormControl><SelectContent><SelectItem value="percentage">% Percentual</SelectItem><SelectItem value="fixed">R$ Fixo</SelectItem></SelectContent></Select></FormItem>
                      )} />
                      <FormField control={form.control} name="discountValue" render={({ field }) => (
                        <FormItem><FormLabel>Valor</FormLabel><FormControl><Input type="number" step="0.01" min="0" {...field} onChange={(e) => field.onChange(Number(e.target.value))} /></FormControl></FormItem>
                      )} />
                    </div>
                  </CardContent>
                </Card>
              )}

              {/* ETAPA 4: REVISÃO */}
              {currentStep === 4 && (
                <div className="space-y-4">
                  <Card>
                    <CardContent className="pt-6">
                      <div className="flex items-center gap-3">
                        <div className="flex items-center justify-center h-10 w-10 rounded-full bg-primary/10 text-primary shrink-0"><User className="h-5 w-5" /></div>
                        <div>
                          <p className="font-semibold">{selectedClient?.name || "Cliente não selecionado"}</p>
                          {selectedClient?.phone && <p className="text-sm text-muted-foreground">{selectedClient.phone}</p>}
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardHeader className="pb-3"><CardTitle className="text-base">Itens ({cartCount})</CardTitle></CardHeader>
                    <CardContent className="space-y-2">
                      {fields.map((field, index) => {
                        if (!watchProducts[index]?.productId) return null;
                        const prod = products.find((p: Product) => (p.uuid || p.identify) === watchProducts[index].productId);
                        if (!prod) return null;
                        const lineTotal = watchProducts[index].price * watchProducts[index].quantity;
                        return (
                          <div key={field.id} className="flex justify-between items-center text-sm py-1">
                            <div className="flex-1 min-w-0"><span className="font-medium">{watchProducts[index].quantity}x</span> {prod.name}</div>
                            <span className="font-medium shrink-0 ml-2">{fmt.format(lineTotal)}</span>
                          </div>
                        );
                      })}
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent className="pt-6 space-y-2">
                      <div className="flex justify-between text-sm"><span>Subtotal</span><span>{fmt.format(subtotal)}</span></div>
                      {discount > 0 && <div className="flex justify-between text-sm text-red-600"><span>Desconto</span><span>-{fmt.format(discount)}</span></div>}
                      <div className="flex justify-between font-bold text-lg border-t pt-2"><span>Total</span><span className="text-primary">{fmt.format(total)}</span></div>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent className="pt-6 space-y-1 text-sm">
                      <p><strong>Tipo:</strong> {isDelivery ? "Delivery" : "Mesa"}</p>
                      {!isDelivery && form.getValues("tableId") && <p><strong>Mesa:</strong> {tables.find((t: Table) => (t.uuid || t.identify) === form.getValues("tableId"))?.name || form.getValues("tableId")}</p>}
                      {isDelivery && <p><strong>Endereço:</strong> {form.getValues("deliveryAddress") || "Endereço do cliente"}</p>}
                      {isScheduled && form.getValues("scheduledAt") && <p><strong>Agendado:</strong> {form.getValues("scheduledAt")}</p>}
                      <p><strong>Pagamento:</strong> {paymentMethods.find((pm: PaymentMethod) => pm.uuid === form.getValues("paymentMethodId"))?.name || "—"}</p>
                    </CardContent>
                  </Card>
                </div>
              )}
            </div>

            {/* SIDEBAR RESUMO (DESKTOP) */}
            <div className="hidden lg:block w-72 shrink-0">
              <Card className="sticky top-4">
                <CardHeader className="pb-3"><CardTitle className="text-base">Resumo do Pedido</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  <div className="flex justify-between text-sm"><span>Itens</span><span>{cartCount}</span></div>
                  <div className="flex justify-between text-sm"><span>Subtotal</span><span>{fmt.format(subtotal)}</span></div>
                  {discount > 0 && <div className="flex justify-between text-sm text-red-600"><span>Desconto</span><span>-{fmt.format(discount)}</span></div>}
                  <div className="flex justify-between font-bold text-lg border-t pt-2"><span>Total</span><span className="text-primary">{fmt.format(total)}</span></div>
                  {currentStep === 4 && (
                    <Button type="submit" disabled={creating} className="w-full h-12">{creating ? "Criando..." : "Confirmar Pedido"}</Button>
                  )}
                </CardContent>
              </Card>
            </div>
          </div>

          {/* NAVEGAÇÃO STICKY */}
          <div className="sticky bottom-0 bg-background border-t pt-3 pb-4 mt-4 -mx-3 px-3 sm:-mx-6 sm:px-6">
            <div className="flex items-center justify-between mb-2 text-sm font-bold lg:hidden">
              <span>Total: {fmt.format(total)}</span>
              <span className="text-muted-foreground">{cartCount} item(ns)</span>
            </div>
            {currentStep < 4 ? (
              <div className="flex gap-3">
                {currentStep > 0 && (
                  <Button type="button" variant="outline" onClick={goBack} className="h-12 flex-1 sm:flex-none sm:w-32"><ChevronLeft className="h-4 w-4 mr-1" /> Voltar</Button>
                )}
                <Button type="button" onClick={goNext} disabled={validatingStep || creating} className="h-12 flex-1">
                  {validatingStep ? "Validando..." : "Próximo"} <ChevronRight className="h-4 w-4 ml-1" />
                </Button>
              </div>
            ) : (
              <div className="flex gap-3">
                <Button type="button" variant="outline" onClick={goBack} className="h-12 sm:w-32"><ChevronLeft className="h-4 w-4 mr-1" /> Voltar</Button>
                <Button type="submit" disabled={creating} className="h-14 flex-1 text-base font-semibold lg:hidden">{creating ? "Criando Pedido..." : "Confirmar Pedido"}</Button>
              </div>
            )}
          </div>
        </form>
      </Form>

      {/* Modal de Adicionar Cliente */}
      <ClientFormDialog
        onAddClient={handleAddClient}
        onEditClient={() => {}}
        editingClient={null}
        open={clientDialogOpen}
        onOpenChange={setClientDialogOpen}
        hideTrigger={true}
      />
    </div>
  );
}

