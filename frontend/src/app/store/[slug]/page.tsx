"use client"

import { useParams } from "next/navigation"
import Link from "next/link"
import { useEffect, useState, useCallback, useRef, type MouseEvent } from "react"
import { ShoppingCart, Plus, Minus, Store, MapPin, Phone, Image as ImageIcon, Loader2, Search, Package, Menu, X, MessageCircle, Check, Clock, CreditCard, User, Truck, ClipboardCheck, ChevronLeft, ChevronRight } from "lucide-react"
import { OrderStepper } from "@/components/order-stepper"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { Textarea } from "@/components/ui/textarea"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet"
import { toast } from "sonner"
import Image from "next/image"
import { maskCPF, maskCNPJ, maskPhone, maskZipCode } from '@/lib/masks'
import { useViaCEP } from '@/hooks/use-viacep'
import { StateCitySelect } from '@/components/location/state-city-select'
import { applyCepToStateHandlers } from '@/lib/apply-cep-to-form'
import { StoreHoursBanner } from './components/store-hours-banner'
import { SiteFooter } from '@/components/site-footer'
import { ReviewModal } from './components/review-modal'
import { resolveImageUrl } from '@/lib/resolve-image-url'
import { ReviewsSection } from './components/reviews-section'
import { apiClient, endpoints } from '@/lib/api-client'
import { buildApiUrl } from '@/lib/api-config'
import { ProductRecommendations } from './components/product-recommendations'

interface ProductVariation {
  id: string
  name: string
  price: number
}

interface ProductOptional {
  id: string
  name: string
  price: number
}

interface Product {
  uuid: string
  name: string
  description: string
  price: number | string
  promotional_price?: number | string
  image: string
  qtd_stock: number
  brand: string
  categories: Array<{ uuid: string; name: string }>
  variations?: ProductVariation[]   // Seleção única (tamanhos)
  optionals?: ProductOptional[]     // Múltipla escolha com quantidade
}

interface StoreInfo {
  id?: number
  tenant_id?: number
  name: string
  slug: string
  email: string
  phone: string
  address: string
  city: string
  state: string
  zipcode: string
  logo: string
  whatsapp: string
  settings?: {
    delivery_pickup?: {
      pickup_enabled?: boolean
      pickup_time_minutes?: number
      pickup_discount_enabled?: boolean
      pickup_discount_percent?: number
      delivery_enabled?: boolean
      delivery_minimum_order_value?: number
      delivery_free_above_value?: number
    }
    invoice_document?: {
      ask_enabled?: boolean
      required?: boolean
    }
  }
}

interface CartItem extends Product {
  quantity: number
  selectedVariation?: ProductVariation    // Variação escolhida (apenas 1)
  selectedOptionals?: Array<{             // Opcionais com quantidade
    id: string
    name: string
    price: number
    quantity: number
  }>
}

export default function PublicStorePage() {
  const params = useParams()
  const slug = params.slug as string

  // Helper function to convert price to number and format
  const formatPrice = (price: number | string): string => {
    const numPrice = typeof price === 'string' ? parseFloat(price) : price
    if (isNaN(numPrice)) {
      return '0,00'
    }
    return numPrice.toLocaleString('pt-BR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  }

  // Helper function to get numeric price
  const getNumericPrice = (price: number | string): number => {
    return typeof price === 'string' ? parseFloat(price) || 0 : price
  }

  const [storeInfo, setStoreInfo] = useState<StoreInfo | null>(null)
  const [products, setProducts] = useState<Product[]>([])
  const [cart, setCart] = useState<CartItem[]>([])
  const [loading, setLoading] = useState(true)
  const [currentStep, setCurrentStep] = useState(0)
  const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set())
  const [orderSuccess, setOrderSuccess] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [selectedCategory, setSelectedCategory] = useState<string>("all")
  
  // Estados para avaliação
  const [showReviewModal, setShowReviewModal] = useState(false)
  const [completedOrderId, setCompletedOrderId] = useState<string | null>(null)
  
  // Estados para variações e opcionais
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null)
  const [selectedVariation, setSelectedVariation] = useState<string>('') // ID da variação (radio)
  const [selectedOptionalsQty, setSelectedOptionalsQty] = useState<Record<string, number>>({}) // {optionalId: quantidade}
  const [showSelectionDialog, setShowSelectionDialog] = useState(false)

  // Form state
  const [clientData, setClientData] = useState({
    name: "",
    email: "",
    phone: "",
    cpf: "",
    cnpj: "",
  })

  const [deliveryFeeInfo, setDeliveryFeeInfo] = useState<{
    fee_type: string
    fee_value: number
    estimated_delivery_minutes: number
    note: string | null
  } | null>(null)

  const [deliveryData, setDeliveryData] = useState({
    is_delivery: true,
    address: "",
    number: "",
    neighborhood: "",
    city: "",
    state: "",
    zip_code: "",
    complement: "",
    notes: "",
  })

  const [paymentMethod, setPaymentMethod] = useState("")
  const [paymentMethodName, setPaymentMethodName] = useState("")
  const [paymentMethods, setPaymentMethods] = useState<any[]>([])
  const [shippingMethod, setShippingMethod] = useState("delivery")
  const [serviceTypes, setServiceTypes] = useState<any[]>([])
  const [selectedServiceType, setSelectedServiceType] = useState<string | null>(null)
  const [orderResult, setOrderResult] = useState<{
    order_id: string
    total: string
    whatsapp_message: string
    whatsapp_link?: string | null
    delivery_fee?: string
    delivery_fee_type?: string | null
    estimated_delivery_minutes?: number | null
  } | null>(null)
  const [showSuccessModal, setShowSuccessModal] = useState(false)
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({})
  const [mobileSummaryOpen, setMobileSummaryOpen] = useState(false)
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false)
  const [contactExpanded, setContactExpanded] = useState<{ whatsapp: boolean; location: boolean }>({
    whatsapp: false,
    location: false,
  })
  const [couponCode, setCouponCode] = useState("")
  const [isStoreOpen, setIsStoreOpen] = useState(true) // Default true para não bloquear até carregar
  const clientLookupTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const lastClientLookupKeyRef = useRef<string>("")

  const lookupExistingClient = useCallback(async (cpf?: string, phone?: string) => {
    const cpfDigits = cpf?.replace(/\D/g, "") ?? ""
    const phoneDigits = phone?.replace(/\D/g, "") ?? ""

    if (cpfDigits.length < 11 && phoneDigits.length < 10) {
      return
    }

    const lookupKey = `${cpfDigits}|${phoneDigits}`
    if (lookupKey === lastClientLookupKeyRef.current) {
      return
    }

    try {
      const params = new URLSearchParams()
      if (cpfDigits.length === 11) params.set("cpf", cpfDigits)
      if (phoneDigits.length >= 10) params.set("phone", phoneDigits)

      const response = await fetch(
        buildApiUrl(`/api/store/${slug}/clients/lookup?${params.toString()}`),
        {
          method: "GET",
          headers: { Accept: "application/json" },
          mode: "cors",
        }
      )

      if (!response.ok) return

      const result = await response.json()
      if (!result.success || !result.data?.exists) {
        lastClientLookupKeyRef.current = lookupKey
        return
      }

      lastClientLookupKeyRef.current = lookupKey

      const foundClient = result.data.client
      const foundAddress = result.data.address

      if (foundClient) {
        setClientData((prev) => ({
          ...prev,
          name: foundClient.name || prev.name,
          email: foundClient.email || prev.email,
          phone: foundClient.phone ? maskPhone(foundClient.phone) : prev.phone,
          cpf: foundClient.cpf ? maskCPF(foundClient.cpf) : prev.cpf,
        }))
      }

      if (foundAddress) {
        setDeliveryData((prev) => ({
          ...prev,
          address: foundAddress.address || prev.address,
          number: foundAddress.number || prev.number,
          neighborhood: foundAddress.neighborhood || prev.neighborhood,
          city: foundAddress.city || prev.city,
          state: foundAddress.state || prev.state,
          zip_code: foundAddress.zip_code ? maskZipCode(foundAddress.zip_code) : prev.zip_code,
          complement: foundAddress.complement || prev.complement,
          notes: foundAddress.notes || prev.notes,
        }))
        toast.success("Cliente encontrado! Dados preenchidos automaticamente.")
      } else if (foundClient) {
        toast.success("Cliente encontrado! Dados pessoais preenchidos.")
      }
    } catch {
      // Silencioso — lookup é opcional
    }
  }, [slug])

  const scheduleClientLookup = useCallback((cpf?: string, phone?: string) => {
    if (clientLookupTimeoutRef.current) {
      clearTimeout(clientLookupTimeoutRef.current)
    }

    clientLookupTimeoutRef.current = setTimeout(() => {
      lookupExistingClient(cpf, phone)
    }, 600)
  }, [lookupExistingClient])

  useEffect(() => {
    return () => {
      if (clientLookupTimeoutRef.current) {
        clearTimeout(clientLookupTimeoutRef.current)
      }
    }
  }, [])

  // Hook para buscar CEP
  const { searchCEP, loading: cepLoading } = useViaCEP()

  // Extrair categorias únicas dos produtos
  const categories = Array.from(
    new Set(
      products.flatMap(product => 
        product.categories?.map(cat => cat.name) || []
      )
    )
  ).sort()

  // Filtrar produtos por categoria
  const filteredProducts = selectedCategory === "all" 
    ? products 
    : products.filter(product => 
        product.categories?.some(cat => cat.name === selectedCategory)
      )

  // Produtos com ofertas (têm promotional_price)
  const productsWithOffers = products
    .filter(product => product.promotional_price && product.promotional_price < product.price)
    .map(product => ({
      ...product,
      discountPercent: Math.round((1 - (getNumericPrice(product.promotional_price!) / getNumericPrice(product.price))) * 100)
    }))
    .sort((a, b) => b.discountPercent - a.discountPercent)
    .slice(0, 4)

  // 4 Melhores ofertas (maior desconto)
  const bestOffers = productsWithOffers


  const loadPaymentMethods = useCallback(async () => {
    try {
      const response = await fetch(buildApiUrl(`/api/store/${slug}/payment-methods`), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        mode: 'cors',
      })

      if (!response.ok) {
        throw new Error('Erro ao carregar formas de pagamento')
      }

      const data = await response.json()
      
      if (data.success && data.data) {

        setPaymentMethods(data.data)
        // Selecionar primeiro método por padrão
        if (data.data.length > 0) {
          // Usar UUID da forma de pagamento
          setPaymentMethod(data.data[0].uuid)
          setPaymentMethodName(data.data[0].name) // ← CORRIGIDO: Setar o nome também
        }
      } else {
        setPaymentMethods([])
      }
    } catch (error) {

      toast.error('Erro ao carregar formas de pagamento')
      setPaymentMethods([])
    }
  }, [slug])

  const loadServiceTypes = useCallback(async () => {
    try {
      const response = await fetch(buildApiUrl('/api/service-type/menu'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        mode: 'cors',
      })

      if (!response.ok) {
        throw new Error('Erro ao carregar tipos de atendimento')
      }

      const data = await response.json()
      
      if (data.success && data.data) {
        const menuTypes = Array.isArray(data.data) ? data.data : []
        setServiceTypes(menuTypes)
        
        // Selecionar primeiro tipo por padrão (preferir Delivery, depois Retirada)
        if (menuTypes.length > 0) {
          const deliveryType = menuTypes.find((st: any) => (st.slug || st.identify) === 'delivery')
          const pickupType = menuTypes.find((st: any) => (st.slug || st.identify) === 'pickup')
          
          const defaultType = deliveryType || pickupType || menuTypes[0]
          const typeSlug = (defaultType.slug || defaultType.identify || '').toLowerCase()
          setSelectedServiceType(defaultType.identify || defaultType.slug)
          setShippingMethod(typeSlug === 'delivery' ? 'delivery' : 'pickup')
        }
      } else {
        // Fallback para tipos padrão
        setServiceTypes([
          { identify: 'delivery', slug: 'delivery', name: 'Delivery', requires_address: true },
          { identify: 'pickup', slug: 'pickup', name: 'Retirada', requires_address: false },
        ])
        setSelectedServiceType('delivery')
        setShippingMethod('delivery')
      }
    } catch (error) {
      // Fallback para tipos padrão em caso de erro
      setServiceTypes([
        { identify: 'delivery', slug: 'delivery', name: 'Delivery', requires_address: true },
        { identify: 'pickup', slug: 'pickup', name: 'Retirada', requires_address: false },
      ])
      setSelectedServiceType('delivery')
      setShippingMethod('delivery')
    }
  }, [])

  const loadStoreData = useCallback(async () => {
    try {
      setLoading(true)

      const [storeRes, productsRes] = await Promise.all([
        fetch(buildApiUrl(`/api/store/${slug}/info`), { mode: 'cors' }),
        fetch(buildApiUrl(`/api/store/${slug}/products`), { mode: 'cors' }),
      ])
      
      // Carregar tipos de atendimento do menu
      await loadServiceTypes()

      // Check if response is JSON
      const storeContentType = storeRes.headers.get("content-type")
      const productsContentType = productsRes.headers.get("content-type")

      if (!storeContentType || !storeContentType.includes("application/json")) {
        throw new Error("API retornou resposta inválida. Verifique se o servidor Laravel está rodando.")
      }

      const storeData = await storeRes.json()
      const productsData = await productsRes.json()

        if (storeData.success) {
          setStoreInfo(storeData.data)
          // Carregar formas de pagamento após obter info da loja
          await loadPaymentMethods()
        } else {
          toast.error(storeData.message || "Loja não encontrada")
      }

      if (productsData.success) {
        setProducts(productsData.data)
      }
    } catch (error) {

      const errorMessage = error instanceof Error ? error.message : "Erro ao carregar dados da loja"
      toast.error(errorMessage)
        } finally {
      setLoading(false)
    }
  }, [slug, loadPaymentMethods, loadServiceTypes])

  useEffect(() => {
    loadStoreData()
  }, [loadStoreData])

  useEffect(() => {
    setMobileSummaryOpen(false)
  }, [currentStep])

  function addToCart(
    product: Product, 
    variation?: ProductVariation,
    optionalsWithQty: Array<{ id: string; name: string; price: number; quantity: number }> = []
  ) {
    setCart((prev) => {
      // Não mesclar itens com configurações diferentes - cada um é único
      return [...prev, { 
        ...product, 
        quantity: 1, 
        selectedVariation: variation,
        selectedOptionals: optionalsWithQty 
      }]
    })
    toast.success("Produto adicionado ao carrinho")
  }

  function productNeedsCustomization(product: Product) {
    const hasVariations = !!(product.variations && product.variations.length > 0)
    const hasOptionals = !!(product.optionals && product.optionals.length > 0)
    return hasVariations || hasOptionals
  }

  function openProductDetail(product: Product) {
    if (product.qtd_stock === 0) {
      toast.error('Produto esgotado')
      return
    }
    setSelectedProduct(product)
    setSelectedVariation(product.variations?.[0]?.id || '')
    setSelectedOptionalsQty({})
    setShowSelectionDialog(true)
  }

  /** Adiciona ao carrinho só pelo botão +. Produtos personalizáveis abrem o modal. */
  function handleAddProduct(product: Product, event?: MouseEvent) {
    event?.stopPropagation()
    event?.preventDefault()

    if (product.qtd_stock === 0) {
      toast.error('Produto esgotado')
      return
    }

    if (productNeedsCustomization(product)) {
      openProductDetail(product)
      return
    }

    addToCart(product)
  }

  function handleOptionalQuantityChange(optionalId: string, delta: number) {
    setSelectedOptionalsQty((prev) => {
      const currentQty = prev[optionalId] || 0
      const newQty = Math.max(0, currentQty + delta)
      
      if (newQty === 0) {
        const { [optionalId]: _, ...rest } = prev
        return rest
      }
      
      return { ...prev, [optionalId]: newQty }
    })
  }

  function confirmAddToCart() {
    if (!selectedProduct) return
    
    // Validar: Se tem variações, é obrigatório selecionar uma
    const hasVariations = selectedProduct.variations && selectedProduct.variations.length > 0
    if (hasVariations && !selectedVariation) {
      toast.error('Por favor, selecione uma variação')
      return
    }
    
    // Buscar variação selecionada
    const variation = selectedProduct.variations?.find(v => v.id === selectedVariation)
    
    // Montar opcionais com quantidade
    const optionalsWithQty = Object.entries(selectedOptionalsQty)
      .map(([optId, qty]) => {
        const optional = selectedProduct.optionals?.find(opt => opt.id === optId)
        return optional ? { ...optional, quantity: qty } : null
      })
      .filter((opt): opt is { id: string; name: string; price: number; quantity: number } => opt !== null)
    
    addToCart(selectedProduct, variation, optionalsWithQty)
    setShowSelectionDialog(false)
    resetSelectionState()
  }

  function calculateSelectionTotal(): number {
    if (!selectedProduct) return 0
    
    const basePrice = getNumericPrice(selectedProduct.promotional_price || selectedProduct.price)
    
    // Adicionar preço da variação selecionada
    const variation = selectedProduct.variations?.find(v => v.id === selectedVariation)
    const variationPrice = variation ? variation.price : 0
    
    // Adicionar preço dos opcionais (preço × quantidade)
    const optionalsTotal = Object.entries(selectedOptionalsQty).reduce((sum, [optId, qty]) => {
      const optional = selectedProduct.optionals?.find(opt => opt.id === optId)
      return sum + (optional ? optional.price * qty : 0)
    }, 0)
    
    return basePrice + variationPrice + optionalsTotal
  }

  function updateQuantity(key: string, delta: number) {
    const index = parseInt(key.split('-').pop() || '0')
    setCart((prev) => {
      const newCart = [...prev]
      const item = newCart[index]
      
      if (!item) return prev
      
      const newQty = item.quantity + delta
      
      if (newQty > item.qtd_stock) {
        toast.error("Estoque insuficiente")
        return prev
      }
      
      if (newQty <= 0) {
        newCart.splice(index, 1)
        return newCart
      }
      
      newCart[index] = { ...item, quantity: newQty }
      return newCart
    })
  }

  function removeFromCart(key: string) {
    const index = parseInt(key.split('-').pop() || '0')
    setCart((prev) => {
      const newCart = [...prev]
      newCart.splice(index, 1)
      return newCart
    })
    toast.success("Produto removido do carrinho")
  }

  function getCartTotal() {
    return cart.reduce((sum, item) => {
      const basePrice = getNumericPrice(item.promotional_price || item.price)
      
      // Adicionar preço da variação (se selecionada)
      const variationPrice = item.selectedVariation ? item.selectedVariation.price : 0
      
      // Adicionar preço dos opcionais (preço × quantidade de cada opcional)
      const optionalsPrice = item.selectedOptionals?.reduce(
        (optSum, opt) => optSum + (opt.price * opt.quantity), 
        0
      ) || 0
      
      // Total do item = (base + variação + opcionais) × quantidade do produto
      const itemTotal = (basePrice + variationPrice + optionalsPrice) * item.quantity
      return sum + itemTotal
    }, 0)
  }

  // Validation function for delivery fields
  const validateDeliveryFields = () => {
    if (shippingMethod !== "delivery") return true
    
    const requiredFields = ['address', 'number', 'neighborhood', 'city', 'state', 'zip_code']
    
    for (const field of requiredFields) {
      if (!deliveryData[field as keyof typeof deliveryData]?.toString().trim()) {
        toast.error(`Campo ${getFieldLabel(field)} é obrigatório para entrega`)
        return false
      }
    }
    
    // Validate CEP format (basic validation)
    if (deliveryData.zip_code && !/^\d{5}-?\d{3}$/.test(deliveryData.zip_code.replace(/\D/g, ''))) {
      toast.error('CEP deve ter o formato 00000-000')
      return false
    }
    
    // Validate State format (2 characters)
    if (deliveryData.state && deliveryData.state.length !== 2) {
      toast.error('Estado deve ter 2 caracteres (ex: SP, RJ)')
      return false
    }
    
    return true
  }

  // Helper function to get field labels
  const getFieldLabel = (field: string) => {
    const labels: Record<string, string> = {
      address: 'Endereço',
      number: 'Número',
      neighborhood: 'Bairro',
      city: 'Cidade',
      state: 'Estado',
      zip_code: 'CEP'
    }
    return labels[field] || field
  }

  // Helper function to format CEP
  const formatCEP = (value: string) => {
    const numbers = value.replace(/\D/g, '')
    return numbers.replace(/(\d{5})(\d{3})/, '$1-$2')
  }

  // Helper function to handle CEP input
  const handleCEPChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const formatted = formatCEP(e.target.value)
    setDeliveryData({ ...deliveryData, zip_code: formatted })
  }

  // Buscar endereço por CEP
  const handleSearchCEP = async () => {
    const cep = deliveryData.zip_code.replace(/\D/g, '')
    
    if (cep.length !== 8) {
      return
    }

    const result = await searchCEP(cep)
    
    if (result) {
      applyCepToStateHandlers(result, {
        setAddress: (v) => setDeliveryData((prev) => ({ ...prev, address: v })),
        setNeighborhood: (v) => setDeliveryData((prev) => ({ ...prev, neighborhood: v })),
        setState: (v) => setDeliveryData((prev) => ({ ...prev, state: v })),
        setCity: (v) => setDeliveryData((prev) => ({ ...prev, city: v })),
      })
    }
  }

  // Helper function to translate field names from backend
  const translateFieldName = (field: string): string => {
    const fieldTranslations: Record<string, string> = {
      'client.name': 'Nome',
      'client.email': 'Email',
      'client.phone': 'Telefone',
      'client.cpf': 'CPF',
      'delivery.address': 'Endereço',
      'delivery.number': 'Número',
      'delivery.neighborhood': 'Bairro',
      'delivery.city': 'Cidade',
      'delivery.state': 'Estado',
      'delivery.zip_code': 'CEP',
      'delivery.complement': 'Complemento',
      'delivery.notes': 'Observações',
      'products': 'Produtos',
      'payment_method': 'Forma de Pagamento',
      'shipping_method': 'Método de Entrega'
    }
    return fieldTranslations[field] || field
  }

  // Helper function to translate payment method
  const translatePaymentMethod = (method: string): string => {
    const paymentMethods: Record<string, string> = {
      'pix': 'PIX',
      'credit_card': 'Cartão de Crédito',
      'debit_card': 'Cartão de Débito',
      'money': 'Dinheiro',
      'bank_transfer': 'Transferência Bancária'
    }
    return paymentMethods[method] || method
  }

  // Helper function to translate shipping method
  const translateShippingMethod = (method: string): string => {
    const shippingMethods: Record<string, string> = {
      'delivery': 'Entrega no endereço',
      'pickup': 'Retirar no local'
    }
    return shippingMethods[method] || method
  }

  // Helper function to translate error messages
  const translateErrorMessage = (message: string): string => {
    const messageTranslations: Record<string, string> = {
      'The client.name field is required.': 'Nome é obrigatório',
      'The client.email field is required.': 'Email é obrigatório',
      'The client.email must be a valid email address.': 'Email deve ser válido',
      'The client.phone field is required.': 'Telefone é obrigatório',
      'The delivery.address field is required when delivery.is delivery is true.': 'Endereço é obrigatório para entrega',
      'The delivery.number field is required when delivery.is delivery is true.': 'Número é obrigatório para entrega',
      'The delivery.neighborhood field is required when delivery.is delivery is true.': 'Bairro é obrigatório para entrega',
      'The delivery.city field is required when delivery.is delivery is true.': 'Cidade é obrigatória para entrega',
      'The delivery.state field is required when delivery.is delivery is true.': 'Estado é obrigatório para entrega',
      'The delivery.zip code field is required when delivery.is delivery is true.': 'CEP é obrigatório para entrega',
      'The delivery.city must be a string.': 'Cidade deve ser um texto',
      'The delivery.state must be a string.': 'Estado deve ser um texto',
      'The delivery.zip code must be a string.': 'CEP deve ser um texto',
      'The delivery.address must be a string.': 'Endereço deve ser um texto',
      'The delivery.number must be a string.': 'Número deve ser um texto',
      'The delivery.neighborhood must be a string.': 'Bairro deve ser um texto',
      'The products field is required.': 'Selecione pelo menos um produto',
      'The payment method field is required.': 'Forma de pagamento é obrigatória',
      'The shipping method field is required.': 'Método de entrega é obrigatório'
    }
    return messageTranslations[message] || message
  }

  async function handleCheckout() {
    try {
      setSubmitting(true)

      // Basic frontend validation (backend will do detailed validation)
      if (cart.length === 0) {
        toast.error('Adicione pelo menos um produto ao carrinho')
        setSubmitting(false)
        return
      }

      if (!clientData.name.trim() || !clientData.phone.trim()) {
        toast.error('Preencha nome e telefone.')
        setSubmitting(false)
        return
      }

      if (invoiceDocumentAskEnabled && invoiceDocumentRequired && !clientData.cpf.trim() && !clientData.cnpj.trim()) {
        toast.error('Informe CPF ou CNPJ para emissão da nota.')
        setSubmitting(false)
        return
      }

      // Prepare delivery data based on shipping method
      const deliveryDataToSend = shippingMethod === "delivery" 
        ? {
            // Only send delivery fields when delivery is selected
            address: deliveryData.address.toString(),
            number: deliveryData.number.toString(),
            neighborhood: deliveryData.neighborhood.toString(),
            city: deliveryData.city.toString(),
            state: deliveryData.state.toString(),
            zip_code: deliveryData.zip_code.toString(),
            complement: deliveryData.complement.toString(),
            notes: deliveryData.notes.toString(),
            is_delivery: true,
          }
        : {
            // For pickup, only send is_delivery as false
            is_delivery: false,
          }

      const orderData = {
        client: {
          ...clientData,
          email: clientData.email.trim() || undefined,
        },
        delivery: deliveryDataToSend,
        products: cart.map((item) => ({
          uuid: item.uuid,
          quantity: item.quantity,
          optionals: item.selectedOptionals || [],
        })),
        payment_method: paymentMethod,
        shipping_method: shippingMethod,
      }

      // Debug log to see what's being sent

      const response = await fetch(buildApiUrl(`/api/store/${slug}/orders`), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
        },
        body: JSON.stringify(orderData),
        mode: 'cors',
        credentials: 'include',
      })

      // Check if response is JSON
      const contentType = response.headers.get("content-type")
      if (!contentType || !contentType.includes("application/json")) {
        throw new Error("API retornou resposta inválida. Verifique se o servidor Laravel está rodando.")
      }

      const result = await response.json()
      
      // Debug log for response
      if (result.success && result.data?.order_id) {
        setOrderResult(result.data)
        setCompletedOrderId(result.data?.order_id ?? null)
        setOrderSuccess(true)
        setCart([])
        setShowSuccessModal(true)
        toast.success(result.message)
      } else {
        // Handle validation errors from backend
        if (result.errors) {
          const errors: Record<string, string> = {}
          
          // Fields that should be ignored when pickup is selected
          const pickupIgnoredFields = [
            'delivery.address',
            'delivery.number', 
            'delivery.neighborhood',
            'delivery.city',
            'delivery.state',
            'delivery.zip_code'
          ]
          
          // Show specific field errors and store them for visual feedback
          Object.entries(result.errors).forEach(([field, messages]: [string, any]) => {
            // Skip delivery field validation errors when pickup is selected
            if (shippingMethod === "pickup" && pickupIgnoredFields.includes(field)) {
              return
            }
            
            const translatedField = translateFieldName(field)
            const message = Array.isArray(messages) ? messages[0] : messages
            const translatedMessage = translateErrorMessage(message)
            
            errors[field] = translatedMessage
            toast.error(`${translatedField}: ${translatedMessage}`)
          })
          
          setValidationErrors(errors)
          
          // Clear errors after 5 seconds
          setTimeout(() => setValidationErrors({}), 5000)
        } else {
          toast.error(result.message || "Erro ao criar pedido")
        }
      }
    } catch (error) {

      let errorMessage = "Erro ao finalizar pedido"
      
      if (error instanceof TypeError && error.message.includes('Failed to fetch')) {
        errorMessage = "Não foi possível conectar ao servidor. Verifique sua conexão com a internet ou se o servidor está online."

      } else if (error instanceof Error) {
        errorMessage = error.message
      }
      
      toast.error(errorMessage, {
        duration: 5000,
      })
    } finally {
      setSubmitting(false)
    }
  }

  function openWhatsApp() {

    if (orderResult?.whatsapp_link) {

      window.open(orderResult.whatsapp_link, "_blank")
    } else {

      toast.error('Link do WhatsApp não disponível. Verifique se a loja possui telefone cadastrado.')
    }
  }

  const handleReviewSubmit = async (reviewData: any) => {
    try {
      if (!storeInfo) {
        throw new Error('Dados da loja não encontrados')
      }
      
      // Usar order_id (identify) do orderResult
      const orderIdentify = orderResult?.order_id || completedOrderId
      
      if (!orderIdentify) {
        throw new Error('Identificador do pedido não encontrado')
      }
      
      // Usar tenant_id ou id (fallback)
      const tenantId = storeInfo.tenant_id || storeInfo.id
      
      if (!tenantId) {
        throw new Error('ID do tenant não encontrado. Dados da loja incompletos.')
      }

      const response = await apiClient.post(endpoints.reviews.public.create, {
        tenant_id: tenantId,
        order_identify: orderIdentify,
        rating: reviewData.rating,
        comment: reviewData.comment,
        customer_name: reviewData.customer_name || clientData.name,
        customer_email: reviewData.customer_email || clientData.email,
      })

      if (response.success) {
        setShowReviewModal(false)
        toast.success('Avaliação enviada! Obrigado pelo feedback.')
      }
    } catch (error: any) {
      throw new Error(error.message || 'Erro ao enviar avaliação')
    }
  }

  const cartTotal = getCartTotal()
  const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0)
  const currentOrderIdentify = orderResult?.order_id || completedOrderId || ''
  const tenantIdForReview = storeInfo?.tenant_id || storeInfo?.id || 0

  // Preview da taxa de entrega estimada (apenas informativo — o valor final é sempre recalculado no servidor ao confirmar o pedido)
  useEffect(() => {
    if (shippingMethod !== 'delivery' || !slug) {
      setDeliveryFeeInfo(null)
      return
    }

    if (!deliveryData.city.trim() || !deliveryData.neighborhood.trim()) {
      setDeliveryFeeInfo(null)
      return
    }

    const timeoutId = setTimeout(async () => {
      try {
        const params = new URLSearchParams({
          address: deliveryData.address,
          number: deliveryData.number,
          neighborhood: deliveryData.neighborhood,
          city: deliveryData.city,
          state: deliveryData.state,
          zip_code: deliveryData.zip_code,
          subtotal: String(cartTotal),
        })

        const response = await fetch(
          buildApiUrl(`/api/store/${slug}/delivery-fee/preview?${params.toString()}`),
          { mode: 'cors' }
        )
        const result = await response.json()

        if (result.success && result.data) {
          setDeliveryFeeInfo(result.data)
        }
      } catch {
        // Silencioso — preview é apenas informativo
        setDeliveryFeeInfo(null)
      }
    }, 600)

    return () => clearTimeout(timeoutId)
  }, [
    shippingMethod,
    slug,
    deliveryData.address,
    deliveryData.number,
    deliveryData.neighborhood,
    deliveryData.city,
    deliveryData.state,
    deliveryData.zip_code,
    cartTotal,
  ])

  const wizardSteps = [
    { label: "Cardápio", icon: ShoppingCart },
    { label: "Seus dados", icon: User },
    { label: "Entrega", icon: Truck },
    { label: "Pagamento", icon: CreditCard },
    { label: "Revisão", icon: ClipboardCheck },
  ]

  const storeFormCardClass = "gap-4 py-4 sm:gap-6 sm:py-6"
  const storeFormHeaderClass = "space-y-1 px-4 pb-2 sm:px-6 sm:pb-3"
  const storeFormContentClass = "space-y-4 px-4 sm:space-y-5 sm:px-6"
  const storeFormGridClass = "grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5"
  const storeFormFieldClass = "space-y-2"
  const storeFormInputClass = "h-10 px-3 py-2 sm:h-11"
  const storeFormLabelClass = "mb-0.5 block text-xs font-medium sm:text-sm"
  const storeFormTextareaClass = "min-h-[80px] resize-none px-3 py-2.5 sm:min-h-[96px]"

  const getNextStepLabel = (step: number) => {
    switch (step) {
      case 0:
        return "Continuar pedido"
      case 1:
        return "Escolher entrega"
      case 2:
        return "Forma de pagamento"
      case 3:
        return "Revisar pedido"
      default:
        return "Continuar"
    }
  }

  const getSubmitLabel = (submitting: boolean) =>
    submitting ? "Enviando pedido..." : "Finalizar pedido"

  const requiresDeliveryAddress = () => {
    const currentType = serviceTypes.find((st: { identify?: string; slug?: string; requires_address?: boolean }) =>
      (st.identify || st.slug) === selectedServiceType
    )
    return currentType?.requires_address || shippingMethod === "delivery"
  }

  const validateWizardStep = (step: number): boolean => {
    switch (step) {
      case 0:
        if (!isStoreOpen) {
          toast.error('A loja está fechada no momento. Volte durante o horário de funcionamento.')
          return false
        }
        if (cart.length === 0) {
          toast.error('Adicione ao menos um item ao carrinho para continuar.')
          return false
        }
        return true
      case 1:
        if (!clientData.name.trim() || !clientData.phone.trim()) {
          toast.error('Informe seu nome e celular para continuar.')
          return false
        }
        return true
      case 2: {
        if (!selectedServiceType && !shippingMethod) {
          toast.error('Escolha como prefere receber o pedido.')
          return false
        }
        if (requiresDeliveryAddress()) {
          if (!deliveryData.address.trim() || !deliveryData.number.trim() || !deliveryData.neighborhood.trim() || !deliveryData.city.trim() || !deliveryData.state.trim() || !deliveryData.zip_code.trim()) {
            toast.error('Complete o endereço de entrega para continuar.')
            return false
          }
        }
        return true
      }
      case 3:
        if (!paymentMethod) {
          toast.error('Escolha como deseja pagar.')
          return false
        }
        return true
      default:
        return true
    }
  }

  const goNext = () => {
    if (!validateWizardStep(currentStep)) return
    setCompletedSteps((prev) => new Set(prev).add(currentStep))
    setCurrentStep((s) => Math.min(s + 1, 4))
    setMobileSummaryOpen(false)
  }

  const goBack = () => {
    setCurrentStep((s) => Math.max(s - 1, 0))
    setMobileSummaryOpen(false)
  }

  const goToStep = (step: number) => {
    if (step <= currentStep || completedSteps.has(step)) {
      setCurrentStep(step)
      setMobileSummaryOpen(false)
    }
  }

  const handleStartCheckout = () => {
    if (!validateWizardStep(0)) return
    setCompletedSteps((prev) => new Set(prev).add(0))
    setCurrentStep(1)
    setMobileSummaryOpen(false)
  }

  const whatsappNumber = storeInfo?.whatsapp || storeInfo?.phone || ''
  const sanitizedWhatsapp = whatsappNumber.replace(/\D/g, '')
  const whatsappLink = sanitizedWhatsapp ? `https://wa.me/${sanitizedWhatsapp}` : undefined
  const displayWhatsapp = whatsappNumber ? maskPhone(whatsappNumber) : 'Não informado'

  const locationText = [
    storeInfo?.address,
    storeInfo?.city && storeInfo?.state ? `${storeInfo.city} - ${storeInfo.state}` : storeInfo?.city || storeInfo?.state,
    storeInfo?.zipcode && `CEP: ${storeInfo.zipcode}`,
  ].filter(Boolean).join(' • ')

  const attendanceText = storeInfo?.settings?.delivery_pickup?.pickup_time_minutes
    ? `Retirada pronta em aproximadamente ${storeInfo.settings.delivery_pickup.pickup_time_minutes} minutos`
    : 'Atendimento disponível durante o horário de funcionamento.'

  const estimatedTime = storeInfo?.settings?.delivery_pickup?.pickup_time_minutes
  const estimatedTimeLabel = estimatedTime ? `${estimatedTime} min` : '30-45 min'
  const acceptedPayments = paymentMethods.length > 0 ? paymentMethods : [{ uuid: 'default', name: 'Verificar na loja' }]

  // "Perguntar CPF/CNPJ" é opt-in por tenant; assume true (comportamento atual) até o tenant configurar explicitamente
  const invoiceDocumentAskEnabled = storeInfo?.settings?.invoice_document?.ask_enabled ?? true
  const invoiceDocumentRequired = storeInfo?.settings?.invoice_document?.required ?? false

  const handleContactToggle = (type: 'whatsapp' | 'location') => {
    setContactExpanded((prev) => ({
      ...prev,
      [type]: !prev[type],
    }))
  }

  const handleScrollToSummary = () => {
    if (typeof document === 'undefined') return
    const element = document.getElementById('order-summary')
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }

  const handleApplyCoupon = () => {
    if (!couponCode.trim()) {
      toast.error('Digite um cupom antes de aplicar.')
      return
    }

    toast.info('Aplicação de cupom em desenvolvimento.', {
      description: 'Em breve você poderá validar descontos automaticamente.',
    })
  }

  const showMobileSummaryButton = cart.length > 0 && currentStep === 0 && !orderSuccess && !mobileSummaryOpen && !isStoreOpen

  const renderSummaryContent = (variant: 'cart' | 'checkout' | 'review', hideActions = false) => {
    if (cart.length === 0) {
      return (
        <div className="min-w-0 space-y-3 break-words text-sm text-muted-foreground">
          <p>Nenhum item no carrinho ainda.</p>
          <p>Explore o cardápio e adicione seus produtos favoritos!</p>
        </div>
      )
    }

    const buttonLabel =
      variant === 'cart'
        ? 'Continuar pedido'
        : getSubmitLabel(submitting)

    const buttonAction = variant === 'cart' ? handleStartCheckout : handleCheckout
    const buttonDisabled =
      variant === 'cart'
        ? cart.length === 0 || !isStoreOpen
        : submitting

    return (
      <div className="min-w-0 space-y-4 overflow-hidden">
        <div className="max-h-[320px] space-y-3 overflow-x-hidden overflow-y-auto pr-1">
          {cart.map((item, index) => {
            const basePrice = getNumericPrice(item.promotional_price || item.price)
            const variationPrice = item.selectedVariation ? item.selectedVariation.price : 0
            const optionalsPrice =
              item.selectedOptionals?.reduce((sum, opt) => sum + (opt.price * opt.quantity), 0) || 0
            const unitPrice = basePrice + variationPrice + optionalsPrice
            const totalPrice = unitPrice * item.quantity

            return (
              <div key={`${item.uuid}-${index}`} className="rounded-xl border bg-card/90 p-4 shadow-sm">
                <div className="flex items-start justify-between gap-3">
                  <div className="flex-1 min-w-0 space-y-2">
                    <div className="flex items-start justify-between gap-2">
                      <p className="font-semibold text-sm sm:text-base text-foreground line-clamp-2 flex-1">{item.name}</p>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-6 w-6 text-muted-foreground hover:text-destructive"
                        onClick={() => removeFromCart(`${item.uuid}-${index}`)}
                        title="Remover item"
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    </div>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                      <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 font-semibold text-primary">
                        {item.quantity}
                      </span>
                      <span>R$ {formatPrice(unitPrice)}</span>
                    </div>
                    {item.selectedVariation && (
                      <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <Badge variant="outline" className="text-xs">
                          {item.selectedVariation.name}
                        </Badge>
                        <span>
                          {item.selectedVariation.price > 0 && `+R$ ${item.selectedVariation.price.toFixed(2)}`}
                          {item.selectedVariation.price === 0 && 'Incluso'}
                        </span>
                      </div>
                    )}
                    {item.selectedOptionals && item.selectedOptionals.length > 0 && (
                      <div className="space-y-1 border-l border-border/60 pl-2 text-xs text-muted-foreground">
                        {item.selectedOptionals.map((opt) => (
                          <div key={opt.id} className="flex items-center justify-between">
                            <span>{opt.name} × {opt.quantity}</span>
                            <span>R$ {(opt.price * opt.quantity).toFixed(2)}</span>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                  <span className="font-semibold text-base text-foreground">
                    R$ {formatPrice(totalPrice)}
                  </span>
                </div>
              </div>
            )
          })}
        </div>

        <Separator />

        {variant === 'cart' && (
          <div className="space-y-2.5 sm:space-y-3">
            <div className={storeFormFieldClass}>
              <Label htmlFor="order-notes" className={storeFormLabelClass}>Alguma observação no pedido?</Label>
              <Textarea
                id="order-notes"
                value={deliveryData.notes}
                onChange={(e) => setDeliveryData((prev) => ({ ...prev, notes: e.target.value }))}
                placeholder="Ex.: sem cebola, ponto da carne, retirar ingredientes..."
                className={storeFormTextareaClass}
              />
            </div>
            <div className={storeFormFieldClass}>
              <Label htmlFor="coupon-code" className={storeFormLabelClass}>Tem cupom de desconto?</Label>
              <div className="flex items-center gap-2">
                <Input
                  id="coupon-code"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                  placeholder="Digite o código"
                  className={`uppercase ${storeFormInputClass}`}
                />
                <Button type="button" variant="secondary" onClick={handleApplyCoupon} className="h-10 shrink-0 whitespace-nowrap px-3 text-sm sm:h-11 sm:px-4">
                  Usar cupom
                </Button>
              </div>
            </div>
          </div>
        )}

        <div className="space-y-3">
          <div className="flex items-center justify-between text-sm text-muted-foreground">
            <span>Itens ({cartCount})</span>
            <span>R$ {formatPrice(cartTotal)}</span>
          </div>

          <div className="rounded-xl bg-muted/40 px-4 py-3 text-xs text-muted-foreground flex items-center gap-3">
            <Clock className="h-4 w-4 text-primary" />
            <div>
              <p className="font-medium text-muted-foreground/90">Tempo estimado</p>
              <p>{estimatedTimeLabel}</p>
            </div>
          </div>

          <div className="rounded-xl bg-muted/40 px-4 py-3 text-xs text-muted-foreground">
            <div className="flex items-center gap-2 text-muted-foreground/90">
              <CreditCard className="h-4 w-4 text-primary" />
              <span className="font-medium">Formas de pagamento</span>
            </div>
            <div className="mt-2 flex flex-wrap gap-2">
              {acceptedPayments.map((method, i) => (
                <Badge key={method.uuid ?? i} variant="outline" className="text-xs">
                  {method.name}
                </Badge>
              ))}
            </div>
          </div>

          <div className="flex items-center justify-between text-lg font-semibold">
            <span>Total</span>
            <span className="text-primary">R$ {formatPrice(cartTotal)}</span>
          </div>

        </div>

        {!hideActions && (
          <>
            <Button
              className="w-full rounded-full"
              size="lg"
              onClick={buttonAction}
              disabled={buttonDisabled}
            >
              {buttonLabel}
            </Button>
            {variant === 'cart' && !isStoreOpen && cart.length > 0 && (
              <p className="text-center text-xs text-muted-foreground">
                A loja está fechada no momento. O pedido poderá ser finalizado quando estivermos abertos.
              </p>
            )}

            {variant === 'cart' && (
              <Button variant="ghost" className="w-full text-sm" onClick={() => setCart([])}>
                Esvaziar carrinho
              </Button>
            )}
          </>
        )}
      </div>
    )
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
          <p className="mt-4 text-muted-foreground">Carregando loja...</p>
        </div>
      </div>
    )
  }

  if (!storeInfo) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Card className="w-full max-w-md">
          <CardHeader>
            <CardTitle>Loja não encontrada</CardTitle>
            <CardDescription>A loja que você está procurando não existe ou está inativa.</CardDescription>
          </CardHeader>
        </Card>
      </div>
    )
  }

  const resetSelectionState = () => {
    setSelectedProduct(null)
    setSelectedVariation('')
    setSelectedOptionalsQty({})
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Store Hours Banner */}
      <StoreHoursBanner slug={slug} onStatusChange={setIsStoreOpen} />
      
      {/* Header */}
      <header className="sticky top-0 z-50 bg-background/95 backdrop-blur shadow-md">
        <div className="border-b">
          <div className="container mx-auto flex h-16 items-center justify-between gap-4 px-4">
            <div className="flex items-center gap-3">
              <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                <SheetTrigger asChild>
                  <Button variant="ghost" size="icon" className="lg:hidden">
                    <Menu className="h-5 w-5" />
                    <span className="sr-only">Abrir menu</span>
                  </Button>
                </SheetTrigger>
                <SheetContent side="left" className="w-full p-0 sm:max-w-sm">
                  <div className="space-y-6 p-6">
                    <div className="flex items-center gap-3">
                      {storeInfo.logo ? (
                        <Image
                          src={resolveImageUrl(storeInfo.logo) || ""}
                          alt={storeInfo.name}
                          width={48}
                          height={48}
                          className="h-12 w-12 rounded-full object-cover"
                          unoptimized
                        />
                      ) : (
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                          <Store className="h-6 w-6 text-muted-foreground" />
                        </div>
              )}
              <div>
                        <h2 className="text-lg font-semibold">{storeInfo.name}</h2>
                        {locationText && <p className="text-sm text-muted-foreground">{locationText}</p>}
                      </div>
                    </div>

                    <div className="space-y-2">
                      <Button
                        className="w-full gap-2"
                        onClick={() => {
                          setMobileSummaryOpen(true)
                          setMobileMenuOpen(false)
                        }}
                      >
                        <ShoppingCart className="h-4 w-4" />
                        Ver carrinho {cartCount > 0 && `(${cartCount})`}
                      </Button>
                      <Button variant="outline" className="w-full gap-2" asChild>
                        <Link href={`/store/${slug}/track`} onClick={() => setMobileMenuOpen(false)}>
                          <Package className="h-4 w-4" />
                          Acompanhar pedido
                        </Link>
                      </Button>
                    </div>

                    <Separator />

                    <div className="space-y-2">
                      <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Contato e Endereço</p>
                      {displayWhatsapp !== 'Não informado' && (
                        <a
                          href={whatsappLink}
                          target="_blank"
                          rel="noreferrer"
                          className="flex items-center gap-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 px-4 py-3 text-sm font-medium text-emerald-700 dark:text-emerald-400 transition hover:bg-emerald-100 dark:hover:bg-emerald-950/40"
                        >
                          <MessageCircle className="h-4 w-4 flex-shrink-0" />
                          <span>{displayWhatsapp}</span>
                        </a>
                      )}
                      {locationText && (
                        <div className="flex items-start gap-3 rounded-xl bg-muted/50 px-4 py-3 text-sm text-muted-foreground">
                          <MapPin className="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" />
                          <span>{locationText}</span>
                        </div>
                      )}
                      <div className="flex items-center gap-3 rounded-xl bg-muted/50 px-4 py-3 text-sm text-muted-foreground">
                        <Clock className="h-4 w-4 flex-shrink-0 text-primary" />
                        <span>{attendanceText}</span>
                      </div>
                    </div>
            </div>
                </SheetContent>
              </Sheet>

              {storeInfo.logo ? (
                <Image
                  src={resolveImageUrl(storeInfo.logo) || ""}
                  alt={storeInfo.name}
                  width={48}
                  height={48}
                  className="hidden h-12 w-12 rounded-full object-cover sm:block"
                  unoptimized
                />
              ) : (
                <div className="hidden h-12 w-12 items-center justify-center rounded-full bg-muted sm:flex">
                  <Store className="h-6 w-6 text-muted-foreground" />
                </div>
              )}

              <div>
                <h1 className="text-lg font-semibold sm:text-xl">{storeInfo.name}</h1>
                {locationText && (
                  <p className="hidden text-xs text-muted-foreground sm:block">{locationText}</p>
                )}
              </div>
            </div>

            <div className="hidden items-center gap-3 lg:flex">
              <Button variant="ghost" className="relative gap-2" onClick={handleScrollToSummary}>
                <ShoppingCart className="h-4 w-4" />
                <span>Carrinho</span>
                {cartCount > 0 && (
                  <Badge className="absolute -top-2 -right-2 h-5 w-5 items-center justify-center p-0">
                    {cartCount}
                  </Badge>
                )}
              </Button>

              <Button variant="ghost" className="gap-2" asChild>
              <Link href={`/store/${slug}/track`}>
                  <Package className="h-4 w-4" />
                  Meu pedido
                </Link>
                </Button>
            </div>

            <div className="flex items-center gap-2 lg:hidden">
                <Button
                variant="ghost"
                size="icon"
                className="relative"
                onClick={() => setMobileSummaryOpen(true)}
                >
                <ShoppingCart className="h-5 w-5" />
                {cartCount > 0 && (
                  <Badge className="absolute -top-2 -right-2 h-5 w-5 items-center justify-center p-0">
                    {cartCount}
                  </Badge>
                )}
                <span className="sr-only">Abrir resumo do carrinho</span>
                </Button>
            </div>
          </div>
        </div>

        {!orderSuccess && (
          <div className="border-b bg-background/90">
            <div className="container mx-auto px-4 py-2 sm:py-3">
              <OrderStepper
                currentStep={currentStep}
                steps={wizardSteps}
                onStepClick={goToStep}
                completedSteps={completedSteps}
              />
            </div>
          </div>
        )}
      </header>

      <main className={`flex-1 ${
        orderSuccess
          ? 'pb-4 sm:pb-8 lg:pb-0'
          : currentStep === 4
            ? 'pb-24 lg:pb-4'
            : 'pb-28 lg:pb-4'
      }`}>
        <div className="w-full">
          {!orderSuccess && currentStep === 0 && (
            <section className="container mx-auto space-y-10 overflow-x-hidden px-4 py-6 sm:py-10">
              <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
                <div className="min-w-0 flex-1 space-y-6">
                  {/* Category filter chips */}
                  {categories.length > 0 && (
                    <div className="flex gap-2 overflow-x-auto pb-2 no-scrollbar">
                      <button
                        onClick={() => setSelectedCategory("all")}
                        className={`flex-shrink-0 rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${
                          selectedCategory === "all"
                            ? "bg-primary text-primary-foreground shadow-sm"
                            : "bg-muted text-muted-foreground hover:bg-muted/80"
                        }`}
                      >
                        Todos
                      </button>
                      {categories.map((cat) => (
                        <button
                          key={cat}
                          onClick={() => setSelectedCategory(cat)}
                          className={`flex-shrink-0 rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${
                            selectedCategory === cat
                              ? "bg-primary text-primary-foreground shadow-sm"
                              : "bg-muted text-muted-foreground hover:bg-muted/80"
                          }`}
                        >
                          {cat}
                        </button>
                      ))}
                    </div>
                  )}

                  <div className="mt-0">
                    {filteredProducts.length === 0 ? (
                      <div className="rounded-2xl border border-dashed border-muted p-12 text-center">
                        <p className="text-lg text-muted-foreground">Nenhum produto encontrado nesta categoria.</p>
                      </div>
                    ) : (
                      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:gap-4 xl:grid-cols-2 2xl:grid-cols-3">
                        {filteredProducts.map((product) => {
                          const price = product.promotional_price || product.price;
                          const hasDiscount = product.promotional_price && product.promotional_price < product.price;
                          const hasCustomization = (product.variations && product.variations.length > 0) || (product.optionals && product.optionals.length > 0);

                          return (
                            <article
                              key={product.uuid}
                              className="group flex gap-3 rounded-2xl border border-border/70 bg-card p-3 text-left transition hover:border-primary/40 hover:shadow-md w-full"
                            >
                              <button
                                type="button"
                                onClick={() => openProductDetail(product)}
                                className="flex min-w-0 flex-1 gap-3 text-left outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-xl"
                                aria-label={`Ver detalhes de ${product.name}`}
                              >
                              {/* Image */}
                              <div className="relative h-24 w-24 flex-shrink-0 overflow-hidden rounded-xl bg-muted md:h-28 md:w-28">
                                {product.image ? (
                                  <Image
                                    src={resolveImageUrl(product.image) || ""}
                                    alt={product.name}
                                    fill
                                    className="object-cover transition-transform duration-300 ease-out group-hover:scale-110"
                                    sizes="112px"
                                    unoptimized
                                  />
                                ) : (
                                  <div className="flex h-full w-full items-center justify-center">
                                    <ImageIcon className="h-8 w-8 text-muted-foreground/40" />
                                  </div>
                                )}
                                {hasDiscount && (
                                  <Badge className="absolute left-1 top-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                    -{Math.round((1 - (getNumericPrice(product.promotional_price!) / getNumericPrice(product.price))) * 100)}%
                                  </Badge>
                                )}
                              </div>

                              {/* Content */}
                              <div className="flex flex-1 flex-col justify-between min-w-0 py-0.5">
                                <div className="space-y-0.5">
                                  <p className="font-semibold text-sm leading-snug line-clamp-2 text-foreground group-hover:text-primary transition-colors">
                                    {product.name}
                                  </p>
                                  {product.description && (
                                    <p className="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                      {product.description}
                                    </p>
                                  )}
                                  {hasCustomization && (
                                    <p className="text-[10px] text-primary/70 font-medium">Personalizável · toque para ver</p>
                                  )}
                                  {!hasCustomization && product.description && (
                                    <p className="text-[10px] text-muted-foreground/80 font-medium">Toque para ver descrição</p>
                                  )}
                                </div>

                                <div className="mt-2">
                                  {hasDiscount && (
                                    <p className="text-[10px] text-muted-foreground line-through leading-none">
                                      R$ {formatPrice(product.price)}
                                    </p>
                                  )}
                                  <p className="text-base font-bold text-primary leading-tight">
                                    R$ {formatPrice(price)}
                                  </p>
                                </div>
                              </div>
                              </button>

                              <div className="flex shrink-0 items-end pb-0.5">
                                <button
                                  type="button"
                                  disabled={product.qtd_stock === 0}
                                  onClick={(e) => handleAddProduct(product, e)}
                                  aria-label={product.qtd_stock === 0 ? 'Produto esgotado' : `Adicionar ${product.name} ao carrinho`}
                                  className={`flex h-12 w-12 items-center justify-center rounded-full shadow-sm transition-colors disabled:opacity-50 disabled:pointer-events-none ${
                                    product.qtd_stock === 0
                                      ? 'bg-muted text-muted-foreground'
                                      : 'bg-primary text-primary-foreground hover:bg-primary/90 active:scale-95'
                                  }`}
                                >
                                  {product.qtd_stock === 0 ? (
                                    <X className="h-5 w-5" />
                                  ) : (
                                    <Plus className="h-6 w-6" strokeWidth={2.5} />
                                  )}
                                </button>
                              </div>
                            </article>
                          )
                        })}
                      </div>
                    )}
                  </div>

                  {/* Recomendações de Produtos */}
                  {cart.length > 0 && (
                    <div className="mt-8">
                      <ProductRecommendations
                        cart={cart.map(item => ({
                          uuid: item.uuid,
                          name: item.name,
                          price: item.price,
                          promotional_price: item.promotional_price,
                          categories: item.categories,
                        }))}
                        allProducts={products.map(p => ({
                          uuid: p.uuid,
                          name: p.name,
                          price: p.price,
                          promotional_price: p.promotional_price,
                          image: p.image,
                          image_url: p.image,
                          description: p.description,
                          categories: p.categories,
                        }))}
                        onAddProduct={(product) => {
                          // Encontrar o produto completo na lista de produtos
                          const fullProduct = products.find(p => p.uuid === product.uuid)
                          if (fullProduct) {
                            handleAddProduct(fullProduct)
                          }
                        }}
                      />
                    </div>
                  )}
                </div>

                <aside
                  className="hidden w-full shrink-0 lg:block lg:w-[22rem] xl:w-96"
                  id="order-summary"
                >
                  <Card className="sticky top-32 space-y-0 overflow-hidden rounded-3xl border border-border/60 shadow-2xl">
                    <CardHeader className="min-w-0 space-y-1 pb-0">
                      <CardTitle className="flex items-center justify-between gap-3 text-xl">
                        <span className="min-w-0 truncate">Seu pedido</span>
                        <Badge variant="outline" className="shrink-0 rounded-full text-xs">
                          {cartCount} {cartCount === 1 ? 'item' : 'itens'}
                        </Badge>
                      </CardTitle>
                      <CardDescription className="text-sm break-words">
                        Revise os itens selecionados antes de prosseguir.
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="min-w-0 space-y-4 overflow-hidden pt-4">
                      {renderSummaryContent('cart')}
                    </CardContent>
                  </Card>
                </aside>
              </div>
            </section>
          )}
        </div>
                
          {!orderSuccess && currentStep >= 1 && currentStep <= 4 && (
          <section className="container mx-auto overflow-x-hidden px-4 py-4 sm:px-4 sm:py-6 lg:py-10">
            <div className="flex flex-col gap-3 sm:gap-4 lg:flex-row lg:items-start lg:gap-8">
              <div className="flex-1 min-w-0 space-y-3 sm:space-y-4">

                {/* ETAPA 1: DADOS */}
                {currentStep === 1 && (
                <Card className={storeFormCardClass}>
                  <CardHeader className={storeFormHeaderClass}>
                    <CardTitle className="text-base sm:text-lg">Seus dados</CardTitle>
                    <CardDescription className="text-xs sm:text-sm">
                      Já pediu aqui antes? Informe celular ou CPF e preenchemos tudo automaticamente.
                    </CardDescription>
                  </CardHeader>
                  <CardContent className={storeFormContentClass}>
                    <div className={storeFormGridClass}>
                      <div className={storeFormFieldClass}>
                        <Label htmlFor="name" className={storeFormLabelClass}>Como podemos te chamar? *</Label>
                        <Input
                          id="name"
                          value={clientData.name}
                          onChange={(e) => setClientData({ ...clientData, name: e.target.value })}
                          placeholder="Seu nome completo"
                          required
                          className={storeFormInputClass}
                        />
                      </div>
                      <div className={storeFormFieldClass}>
                        <Label htmlFor="email" className={storeFormLabelClass}>E-mail (opcional)</Label>
                        <Input
                          id="email"
                          type="email"
                          value={clientData.email}
                          onChange={(e) => setClientData({ ...clientData, email: e.target.value })}
                          placeholder="seu@email.com"
                          className={storeFormInputClass}
                        />
                      </div>
                      <div className={storeFormFieldClass}>
                        <Label htmlFor="phone" className={storeFormLabelClass}>Celular com WhatsApp *</Label>
                        <Input
                          id="phone"
                          value={clientData.phone}
                          onChange={(e) => {
                            const masked = maskPhone(e.target.value)
                            setClientData({ ...clientData, phone: masked })
                            const digits = masked.replace(/\D/g, "")
                            if (digits.length >= 10) {
                              scheduleClientLookup(clientData.cpf, digits)
                            }
                          }}
                          onBlur={() => scheduleClientLookup(clientData.cpf, clientData.phone)}
                          placeholder="(11) 99999-9999"
                          required
                          className={storeFormInputClass}
                        />
                      </div>
                      {invoiceDocumentAskEnabled && (
                        <>
                          <div className={storeFormFieldClass}>
                            <Label htmlFor="cpf" className={storeFormLabelClass}>
                              CPF {invoiceDocumentRequired ? '*' : '(opcional)'}
                            </Label>
                            <Input
                              id="cpf"
                              value={clientData.cpf}
                              onChange={(e) => {
                                const masked = maskCPF(e.target.value)
                                setClientData({ ...clientData, cpf: masked })
                                const digits = masked.replace(/\D/g, "")
                                if (digits.length === 11) {
                                  scheduleClientLookup(digits, clientData.phone)
                                }
                              }}
                              onBlur={() => scheduleClientLookup(clientData.cpf, clientData.phone)}
                              placeholder="000.000.000-00"
                              className={storeFormInputClass}
                            />
                          </div>
                          <div className={storeFormFieldClass}>
                            <Label htmlFor="cnpj" className={storeFormLabelClass}>
                              CNPJ {invoiceDocumentRequired ? '*' : '(opcional)'}
                            </Label>
                            <Input
                              id="cnpj"
                              value={clientData.cnpj}
                              onChange={(e) => setClientData({ ...clientData, cnpj: maskCNPJ(e.target.value) })}
                              placeholder="00.000.000/0000-00"
                              className={storeFormInputClass}
                            />
                          </div>
                          {invoiceDocumentRequired && (
                            <p className="text-xs text-muted-foreground col-span-full">
                              Informe CPF ou CNPJ para emissão da nota
                            </p>
                          )}
                        </>
                      )}
                    </div>
                  </CardContent>
                </Card>
                )}

                {/* ETAPA 2: ENTREGA */}
                {currentStep === 2 && (
                <>
                <Card className={storeFormCardClass}>
                  <CardHeader className={storeFormHeaderClass}>
                    <CardTitle className="text-base sm:text-lg">Como prefere receber?</CardTitle>
                    <CardDescription className="text-xs sm:text-sm">
                      Escolha entre entrega em casa ou retirada no local.
                    </CardDescription>
                  </CardHeader>
                  <CardContent className={`${storeFormContentClass} pt-0`}>
                    <RadioGroup 
                      value={selectedServiceType || shippingMethod} 
                      onValueChange={(value) => {
                        const serviceType = serviceTypes.find((st: any) => 
                          (st.identify || st.slug) === value
                        )
                        if (serviceType) {
                          setSelectedServiceType(value)
                          const typeSlug = (serviceType.slug || serviceType.identify || '').toLowerCase()
                          setShippingMethod(typeSlug === 'delivery' ? 'delivery' : 'pickup')
                        } else {
                          setShippingMethod(value)
                        }
                      }}
                      className="space-y-2.5 sm:space-y-3"
                    >
                      {serviceTypes.length > 0 ? (
                        serviceTypes.map((st: any) => {
                          const typeValue = st.identify || st.slug
                          return (
                            <div key={typeValue} className="flex items-center gap-3 rounded-lg border border-border/60 px-4 py-3 sm:px-4 sm:py-3.5">
                              <RadioGroupItem value={typeValue} id={typeValue} />
                              <Label htmlFor={typeValue} className={`${storeFormLabelClass} cursor-pointer font-normal`}>{st.name}</Label>
                            </div>
                          )
                        })
                      ) : (
                        <>
                          <div className="flex items-center gap-3 rounded-lg border border-border/60 px-4 py-3 sm:px-4 sm:py-3.5">
                            <RadioGroupItem value="delivery" id="delivery" />
                            <Label htmlFor="delivery" className={`${storeFormLabelClass} cursor-pointer font-normal`}>Receber em casa</Label>
                          </div>
                          <div className="flex items-center gap-3 rounded-lg border border-border/60 px-4 py-3 sm:px-4 sm:py-3.5">
                            <RadioGroupItem value="pickup" id="pickup" />
                            <Label htmlFor="pickup" className={`${storeFormLabelClass} cursor-pointer font-normal`}>Buscar no restaurante</Label>
                          </div>
                        </>
                      )}
                    </RadioGroup>
                  </CardContent>
                </Card>

                {/* Delivery Address - Exibir apenas se o tipo selecionado requer endereço */}
                {(() => {
                  const currentType = serviceTypes.find((st: any) => 
                    (st.identify || st.slug) === selectedServiceType
                  )
                  const requiresAddress = currentType?.requires_address || shippingMethod === "delivery"
                  return requiresAddress && (
                  <Card className={storeFormCardClass}>
                    <CardHeader className={storeFormHeaderClass}>
                      <CardTitle className="text-base sm:text-lg">Onde entregar?</CardTitle>
                      <CardDescription className="text-xs sm:text-sm">
                        Informe o endereço completo para receber seu pedido.
                      </CardDescription>
                    </CardHeader>
                    <CardContent className={storeFormContentClass}>
                      <div className="grid grid-cols-1 gap-3 md:grid-cols-3 sm:gap-4">
                        <div className={`md:col-span-2 ${storeFormFieldClass}`}>
                          <Label htmlFor="address" className={storeFormLabelClass}>Rua ou avenida *</Label>
                          <Input
                            id="address"
                            value={deliveryData.address}
                            onChange={(e) => setDeliveryData({ ...deliveryData, address: e.target.value })}
                            required={shippingMethod === "delivery"}
                            placeholder="Ex.: Rua das Flores"
                            className={`${storeFormInputClass} ${validationErrors['delivery.address'] ? 'border-red-500' : ''}`}
                          />
                          {validationErrors['delivery.address'] && (
                            <p className="text-xs text-red-500 mt-1 sm:text-sm">{validationErrors['delivery.address']}</p>
                          )}
                        </div>
                        <div className={storeFormFieldClass}>
                          <Label htmlFor="number" className={storeFormLabelClass}>Número *</Label>
                          <Input
                            id="number"
                            value={deliveryData.number}
                            onChange={(e) => setDeliveryData({ ...deliveryData, number: e.target.value })}
                            required={shippingMethod === "delivery"}
                            placeholder="Ex.: 123"
                            className={`${storeFormInputClass} ${validationErrors['delivery.number'] ? 'border-red-500' : ''}`}
                          />
                          {validationErrors['delivery.number'] && (
                            <p className="text-xs text-red-500 mt-1 sm:text-sm">{validationErrors['delivery.number']}</p>
                          )}
                        </div>
                      </div>
                      <div className={storeFormGridClass}>
                        <div className={storeFormFieldClass}>
                          <Label htmlFor="neighborhood" className={storeFormLabelClass}>Bairro *</Label>
                          <Input
                            id="neighborhood"
                            value={deliveryData.neighborhood}
                            onChange={(e) => setDeliveryData({ ...deliveryData, neighborhood: e.target.value })}
                            required={shippingMethod === "delivery"}
                            placeholder="Ex.: Centro"
                            className={`${storeFormInputClass} ${validationErrors['delivery.neighborhood'] ? 'border-red-500' : ''}`}
                          />
                          {validationErrors['delivery.neighborhood'] && (
                            <p className="text-xs text-red-500 mt-1 sm:text-sm">{validationErrors['delivery.neighborhood']}</p>
                          )}
                        </div>
                      </div>
                      
                      {/* Estado e Cidade */}
                      <StateCitySelect
                        stateValue={deliveryData.state}
                        cityValue={deliveryData.city}
                        onStateChange={(value) => setDeliveryData({ ...deliveryData, state: value })}
                        onCityChange={(value) => setDeliveryData({ ...deliveryData, city: value })}
                        stateError={validationErrors['delivery.state']}
                        cityError={validationErrors['delivery.city']}
                        required={shippingMethod === "delivery"}
                        className={storeFormGridClass}
                        fieldClassName={storeFormFieldClass}
                        labelClassName={storeFormLabelClass}
                        triggerClassName={storeFormInputClass}
                      />

                      <div className={storeFormGridClass}>
                        <div className={storeFormFieldClass}>
                          <Label htmlFor="zip_code" className={storeFormLabelClass}>CEP *</Label>
                          <div className="flex gap-2">
                            <Input
                              id="zip_code"
                              value={deliveryData.zip_code}
                              onChange={handleCEPChange}
                              onBlur={handleSearchCEP}
                              maxLength={9}
                              required={shippingMethod === "delivery"}
                              placeholder="00000-000"
                              className={`${storeFormInputClass} ${validationErrors['delivery.zip_code'] ? 'border-red-500' : ''}`}
                            />
                            <Button
                              type="button"
                              variant="outline"
                              size="icon"
                              className="h-10 w-10 shrink-0 sm:h-11 sm:w-11"
                              onClick={handleSearchCEP}
                              disabled={cepLoading || deliveryData.zip_code.replace(/\D/g, '').length !== 8}
                              title="Buscar endereço pelo CEP"
                            >
                              {cepLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}
                            </Button>
                          </div>
                          {validationErrors['delivery.zip_code'] && (
                            <p className="text-xs text-red-500 mt-1 sm:text-sm">{validationErrors['delivery.zip_code']}</p>
                          )}
                          {cepLoading && (
                            <p className="text-xs text-muted-foreground mt-1 sm:text-sm">Buscando endereço...</p>
                          )}
                        </div>
                      </div>
                      <div className={storeFormFieldClass}>
                        <Label htmlFor="complement" className={storeFormLabelClass}>Complemento (opcional)</Label>
                        <Input
                          id="complement"
                          value={deliveryData.complement}
                          onChange={(e) => setDeliveryData({ ...deliveryData, complement: e.target.value })}
                          placeholder="Apto, bloco, casa..."
                          className={storeFormInputClass}
                        />
                      </div>
                      <div className={storeFormFieldClass}>
                        <Label htmlFor="notes" className={storeFormLabelClass}>Instruções para entrega (opcional)</Label>
                        <Textarea
                          id="notes"
                          value={deliveryData.notes}
                          onChange={(e) => setDeliveryData({ ...deliveryData, notes: e.target.value })}
                          placeholder="Ex.: portão azul, interfone 12, ponto de referência..."
                          className={storeFormTextareaClass}
                        />
                      </div>
                    </CardContent>
                  </Card>
                  )
                })()}
                </>
                )}

                {/* ETAPA 3: PAGAMENTO */}
                {currentStep === 3 && (
                <Card className={storeFormCardClass}>
                  <CardHeader className={storeFormHeaderClass}>
                    <CardTitle className="text-base sm:text-lg">Como vai pagar?</CardTitle>
                    <CardDescription className="text-xs sm:text-sm">
                      Selecione a forma de pagamento que preferir.
                    </CardDescription>
                  </CardHeader>
                  <CardContent className={`${storeFormContentClass} pt-0`}>
                    {paymentMethods.length > 0 ? (
                      <RadioGroup 
                        value={paymentMethod} 
                        onValueChange={(uuid) => {
                          setPaymentMethod(uuid)
                          const selected = paymentMethods.find(m => m.uuid === uuid)
                          setPaymentMethodName(selected?.name || uuid)
                        }}
                        className="space-y-2.5 sm:space-y-3"
                      >
                        {paymentMethods.map((method) => (
                          <div key={method.uuid} className="flex items-center gap-3 rounded-lg border border-border/60 px-4 py-3 sm:px-4 sm:py-3.5">
                            <RadioGroupItem value={method.uuid} id={method.uuid} />
                            <Label htmlFor={method.uuid} className={`${storeFormLabelClass} flex cursor-pointer items-center gap-2 font-normal`}>
                              {method.name}
                              {method.description && (
                                <Badge variant="outline" className="text-[10px] sm:text-xs">
                                  {method.description}
                                </Badge>
                              )}
                            </Label>
                          </div>
                        ))}
                      </RadioGroup>
                    ) : (
                      <div className="py-3 text-center text-muted-foreground sm:py-4">
                        <Loader2 className="mx-auto mb-2 h-6 w-6 animate-spin" />
                        <p className="text-xs sm:text-sm">Carregando opções de pagamento...</p>
                      </div>
                    )}
                  </CardContent>
                </Card>
                )}

                {/* ETAPA 4: REVISÃO */}
                {currentStep === 4 && (
                <div className="space-y-3 sm:space-y-4">
                  <Card className="gap-3 py-3 sm:gap-6 sm:py-6">
                    <CardContent className="px-4 pt-4 sm:px-6 sm:pt-6">
                      <div className="flex items-center gap-2.5 sm:gap-3">
                        <div className="flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-primary/10 text-primary shrink-0"><User className="h-4 w-4 sm:h-5 sm:w-5" /></div>
                        <div>
                          <p className="font-semibold">{clientData.name || "—"}</p>
                          {clientData.phone && <p className="text-sm text-muted-foreground">{clientData.phone}</p>}
                          {clientData.email && <p className="text-sm text-muted-foreground">{clientData.email}</p>}
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                  <Card className="gap-3 py-3 sm:gap-6 sm:py-6">
                    <CardHeader className="px-4 pb-2 sm:px-6 sm:pb-3"><CardTitle className="text-base">Itens ({cartCount})</CardTitle></CardHeader>
                    <CardContent className="space-y-2 px-4 sm:space-y-2 sm:px-6">
                      {cart.map((item, index) => {
                        const basePrice = getNumericPrice(item.promotional_price || item.price)
                        const variationPrice = item.selectedVariation ? item.selectedVariation.price : 0
                        const optionalsPrice = item.selectedOptionals?.reduce((sum, opt) => sum + (opt.price * opt.quantity), 0) || 0
                        const unitPrice = basePrice + variationPrice + optionalsPrice
                        const lineTotal = unitPrice * item.quantity
                        return (
                          <div key={`${item.uuid}-${index}`} className="flex justify-between items-center text-sm py-1">
                            <div className="flex-1 min-w-0"><span className="font-medium">{item.quantity}x</span> {item.name}</div>
                            <span className="font-medium shrink-0 ml-2">R$ {formatPrice(lineTotal)}</span>
                          </div>
                        )
                      })}
                    </CardContent>
                  </Card>
                  <Card className="gap-3 py-3 sm:gap-6 sm:py-6">
                    <CardContent className="space-y-2 px-4 pt-4 text-sm sm:px-6 sm:pt-6">
                      <p><strong>Entrega:</strong> {translateShippingMethod(shippingMethod)}</p>
                      {requiresDeliveryAddress() && deliveryData.address && (
                        <p><strong>Endereço:</strong> {deliveryData.address}, {deliveryData.number} — {deliveryData.neighborhood}, {deliveryData.city}/{deliveryData.state}</p>
                      )}
                      <p><strong>Pagamento:</strong> {paymentMethodName || "—"}</p>
                      {shippingMethod === 'delivery' && deliveryFeeInfo && (
                        <p>
                          <strong>Taxa de entrega:</strong>{' '}
                          {deliveryFeeInfo.fee_type === 'fixed' && `R$ ${formatPrice(deliveryFeeInfo.fee_value)}`}
                          {deliveryFeeInfo.fee_type === 'free' && 'Grátis'}
                          {deliveryFeeInfo.fee_type === 'negotiable' && (deliveryFeeInfo.note || 'A combinar')}
                          {deliveryFeeInfo.estimated_delivery_minutes > 0 && ` · ~${deliveryFeeInfo.estimated_delivery_minutes} min`}
                        </p>
                      )}
                      {shippingMethod === 'pickup' && estimatedTime && (
                        <p><strong>Tempo estimado:</strong> ~{estimatedTime} min</p>
                      )}
                      <div className="flex justify-between font-bold text-lg border-t pt-2 mt-2">
                        <span>Total</span>
                        <span className="text-primary">
                          R$ {formatPrice(cartTotal + (shippingMethod === 'delivery' && deliveryFeeInfo?.fee_type === 'fixed' ? deliveryFeeInfo.fee_value : 0))}
                        </span>
                      </div>
                    </CardContent>
                  </Card>
                </div>
                )}
              </div>

              {/* Order Summary Sidebar (desktop) */}
              <div className="hidden w-full shrink-0 lg:block lg:w-[22rem] xl:w-96" id="order-summary-checkout">
                <Card className="sticky top-24 overflow-hidden rounded-3xl border border-border/60 shadow-lg">
                  <CardHeader className="min-w-0 space-y-1 pb-0">
                    <CardTitle className="flex items-center justify-between gap-3 text-base">
                      <span className="min-w-0 truncate">Seu pedido</span>
                      <Badge variant="outline" className="shrink-0 rounded-full text-xs">
                        {cartCount} {cartCount === 1 ? 'item' : 'itens'}
                      </Badge>
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="min-w-0 space-y-4 overflow-hidden pt-4">
                    {renderSummaryContent(currentStep === 4 ? 'review' : 'checkout', true)}
                    {currentStep === 4 && (
                      <Button type="button" onClick={handleCheckout} disabled={submitting} className="w-full h-11 sm:h-12">
                        {getSubmitLabel(submitting)}
                      </Button>
                    )}
                  </CardContent>
                </Card>
              </div>
            </div>
          </section>
        )}

        {orderSuccess && orderResult && (
          <section className="container mx-auto flex justify-center px-2 py-3 sm:px-4 sm:py-12">
            <Card className="w-full max-w-2xl gap-3 py-3 sm:gap-6 sm:py-6">
              <CardHeader className="px-3 text-center sm:px-6">
                <div className="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 sm:mb-4 sm:h-16 sm:w-16">
                  <svg className="h-6 w-6 text-green-600 sm:h-8 sm:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <CardTitle className="text-xl sm:text-2xl">Pedido Realizado com Sucesso!</CardTitle>
                <CardDescription className="text-sm">
                  Seu pedido <strong>#{orderResult.order_id}</strong> foi criado com sucesso.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-3 px-3 sm:space-y-6 sm:px-6">
                {/* Customer Info */}
                <div className="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-indigo-50 p-3 dark:border-blue-900 dark:from-blue-950/20 dark:to-indigo-950/20 sm:p-5">
                  <div className="mb-2 flex items-center gap-2 sm:mb-4">
                    <div className="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                      <svg className="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </div>
                    <h3 className="font-semibold text-blue-900 dark:text-blue-100">Dados do Cliente</h3>
                  </div>
                  <div className="space-y-2 sm:space-y-3">
                    <div className="flex items-center gap-3">
                      <svg className="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      <p className="font-semibold text-gray-900 dark:text-gray-100">{clientData.name}</p>
                    </div>
                    {clientData.email && (
                    <div className="flex items-center gap-3">
                      <svg className="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                      <p className="text-sm text-gray-700 dark:text-gray-300">{clientData.email}</p>
                    </div>
                    )}
                    <div className="flex items-center gap-3">
                      <svg className="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                      </svg>
                      <p className="text-sm text-gray-700 dark:text-gray-300">{clientData.phone}</p>
                    </div>
                    <div className="flex items-center gap-3 pt-2 border-t border-blue-200 dark:border-blue-800">
                      <svg className="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {new Date().toLocaleDateString('pt-BR', { 
                          day: '2-digit', 
                          month: 'long', 
                          year: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit'
                        })}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Order Items */}
                <div className="space-y-2 sm:space-y-4">
                  <div className="flex items-center gap-2">
                    <div className="p-2 bg-orange-100 dark:bg-orange-900/50 rounded-lg">
                      <svg className="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                    </div>
                    <h3 className="font-semibold text-base text-gray-900 dark:text-gray-100 sm:text-lg">Itens do Pedido</h3>
                  </div>
                  <div className="space-y-1.5 sm:space-y-2">
                    {cart.map((item) => {
                      const price = getNumericPrice(item.promotional_price || item.price)
                      return (
                        <div key={item.uuid} className="flex items-start justify-between gap-2 rounded-lg border border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100/50 p-2.5 transition-shadow hover:shadow-md dark:border-gray-700 dark:from-gray-900/30 dark:to-gray-800/20 sm:gap-4 sm:p-4">
                          <div className="flex-1 min-w-0">
                            <p className="font-semibold text-gray-900 dark:text-gray-100">{item.name}</p>
                            <div className="flex items-center gap-2 mt-1">
                              <span className="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 dark:bg-orange-900/50 text-xs font-bold text-orange-700 dark:text-orange-300">
                                {item.quantity}
                              </span>
                              <span className="text-sm text-gray-600 dark:text-gray-400">
                                × R$ {formatPrice(item.promotional_price || item.price)}
                              </span>
                            </div>
                          </div>
                          <p className="font-bold text-base text-gray-900 dark:text-gray-100 sm:text-lg">
                            R$ {formatPrice(price * item.quantity)}
                          </p>
                        </div>
                      )
                    })}
                  </div>
                </div>

                {/* Payment and Shipping Info */}
                <div className="grid grid-cols-1 gap-2 sm:gap-4 md:grid-cols-2">
                  <div className="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-teal-50 p-3 dark:border-emerald-900 dark:from-emerald-950/20 dark:to-teal-950/20 sm:p-4">
                    <div className="flex items-center gap-2 mb-2">
                      <svg className="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                      </svg>
                      <p className="text-sm font-medium text-emerald-700 dark:text-emerald-300">Pagamento</p>
                    </div>
                    <p className="font-semibold text-gray-900 dark:text-gray-100">{paymentMethodName || 'Não selecionado'}</p>
                  </div>
                  <div className="rounded-xl border border-purple-100 bg-gradient-to-br from-purple-50 to-pink-50 p-3 dark:border-purple-900 dark:from-purple-950/20 dark:to-pink-950/20 sm:p-4">
                    <div className="flex items-center gap-2 mb-2">
                      <svg className="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                      </svg>
                      <p className="text-sm font-medium text-purple-700 dark:text-purple-300">Método de Entrega</p>
                    </div>
                    <p className="font-semibold text-gray-900 dark:text-gray-100">{translateShippingMethod(shippingMethod)}</p>
                  </div>
                </div>

                {/* Delivery Address (if delivery is selected) */}
                {shippingMethod === "delivery" && (
                  <div className="rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50 to-yellow-50 p-3 dark:border-amber-900 dark:from-amber-950/20 dark:to-yellow-950/20 sm:p-5">
                    <div className="mb-2 flex items-center gap-2 sm:mb-4">
                      <div className="p-2 bg-amber-100 dark:bg-amber-900/50 rounded-lg">
                        <svg className="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                      </div>
                      <h3 className="font-semibold text-amber-900 dark:text-amber-100">Endereço de Entrega</h3>
                    </div>
                    <div className="space-y-2">
                      <p className="font-medium text-gray-900 dark:text-gray-100">
                        {deliveryData.address}, {deliveryData.number}
                      </p>
                      {deliveryData.complement && (
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                          <span className="font-medium">Complemento:</span> {deliveryData.complement}
                        </p>
                      )}
                      <p className="text-sm text-gray-700 dark:text-gray-300">
                        {deliveryData.neighborhood} - {deliveryData.city}/{deliveryData.state}
                      </p>
                      <p className="text-sm text-gray-700 dark:text-gray-300">
                        <span className="font-medium">CEP:</span> {deliveryData.zip_code}
                      </p>
                      {deliveryData.notes && (
                        <div className="pt-2 border-t border-amber-200 dark:border-amber-800 mt-3">
                          <p className="text-sm text-gray-700 dark:text-gray-300">
                            <span className="font-semibold text-amber-700 dark:text-amber-300">📝 Observações:</span> {deliveryData.notes}
                          </p>
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {/* Total */}
                <div className="rounded-xl border-2 border-green-300 bg-gradient-to-br from-green-100 via-emerald-100 to-teal-100 p-4 shadow-lg dark:border-green-700 dark:from-green-900/30 dark:via-emerald-900/30 dark:to-teal-900/30 sm:p-6">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2 sm:gap-3">
                      <div className="rounded-xl bg-green-500 p-2 shadow-md dark:bg-green-600 sm:p-3">
                        <svg className="h-5 w-5 text-white sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div>
                        <p className="text-sm font-medium text-green-700 dark:text-green-300">Total do Pedido</p>
                        <p className="text-2xl font-black tracking-tight text-green-700 dark:text-green-300 sm:text-4xl">
                          R$ {orderResult.total}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Next Steps */}
                <div className="rounded-xl border border-slate-200 bg-gradient-to-r from-slate-50 to-gray-50 p-3 dark:border-slate-700 dark:from-slate-900/30 dark:to-gray-900/30 sm:p-5">
                  <div className="mb-2 flex items-center gap-2 sm:mb-3">
                    <div className="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg">
                      <svg className="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                      </svg>
                    </div>
                    <h3 className="font-semibold text-slate-900 dark:text-slate-100">Próximos Passos</h3>
                  </div>
                  <ol className="space-y-2 sm:space-y-3">
                    <li className="flex items-start gap-3">
                      <span className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">1</span>
                      <p className="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Confirme seu pedido via WhatsApp</p>
                    </li>
                    <li className="flex items-start gap-3">
                      <span className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">2</span>
                      <p className="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Aguarde a confirmação do restaurante</p>
                    </li>
                    <li className="flex items-start gap-3">
                      <span className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">3</span>
                      <p className="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Você receberá atualizações sobre o status do pedido</p>
                    </li>
                  </ol>
                </div>

                {/* Pickup Time Estimate (if pickup is selected) */}
                {shippingMethod === "pickup" && storeInfo?.settings?.delivery_pickup?.pickup_enabled && (
                  <div className="rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4">
                    <div className="flex items-start gap-3">
                      <div className="p-2 bg-green-100 rounded-lg">
                        <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div className="flex-1">
                        <p className="font-semibold text-green-900">⏰ Tempo Estimado para Retirada</p>
                        <p className="text-sm text-green-700 mt-1">
                          Seu pedido estará pronto em aproximadamente{" "}
                          <strong>{storeInfo.settings.delivery_pickup.pickup_time_minutes || 35} minutos</strong>
                        </p>
                        {storeInfo.settings.delivery_pickup.pickup_discount_enabled && 
                         (storeInfo.settings.delivery_pickup.pickup_discount_percent || 0) > 0 && (
                          <p className="text-sm text-green-700 mt-2 font-medium">
                            🎉 Desconto de {storeInfo.settings.delivery_pickup.pickup_discount_percent}% aplicado por retirada no local!
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                )}

                {orderResult?.whatsapp_link ? (
                  <Button 
                    className="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white shadow-lg hover:shadow-xl transition-all duration-200 border-0" 
                    size="lg" 
                    onClick={openWhatsApp}
                  >
                    <svg className="mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Enviar Pedido via WhatsApp
                  </Button>
                ) : (
                  <div className="w-full rounded-xl border border-gray-200 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-900/30 sm:p-6">
                    <Phone className="h-10 w-10 mx-auto mb-3 text-gray-400 dark:text-gray-600" />
                    <p className="text-sm font-medium text-gray-700 dark:text-gray-300">WhatsApp não disponível</p>
                    <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">Restaurante não possui telefone cadastrado</p>
                  </div>
                )}

                <Button 
                  variant="outline" 
                  className="w-full border-2 hover:bg-gray-50 dark:hover:bg-gray-900/50" 
                  size="lg"
                  onClick={() => {
                    setOrderSuccess(false)
                    setCurrentStep(0)
                    setCompletedSteps(new Set())
                    setOrderResult(null)
                    setCompletedOrderId(null)
                  }}
                >
                  <svg className="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                  </svg>
                  Fazer outro pedido
                </Button>

                {/* Botão de Avaliação */}
                <Button 
                  variant="secondary" 
                  className="w-full" 
                  onClick={() => {
                    const orderIdentify = orderResult?.order_id || completedOrderId
                    if (!orderIdentify) {
                      toast.error('Identificador do pedido não encontrado.')
                      return
                    }
                    if (!storeInfo || !(storeInfo.tenant_id || storeInfo.id)) {
                      toast.error('Não foi possível carregar os dados da loja para avaliação.')
                      return
                    }
                    setShowReviewModal(true)
                  }}
                >
                  ⭐ Avaliar Pedido
                </Button>
              </CardContent>
            </Card>
          </section>
        )}
      </main>

      {!orderSuccess && currentStep >= 1 && (
        <div className={`fixed bottom-0 left-0 right-0 z-[55] border-t bg-background lg:hidden ${currentStep === 4 ? 'px-3 pt-2 pb-3' : 'px-4 pt-3 pb-4'}`}>
          <div className={`flex items-center justify-between text-sm font-bold ${currentStep === 4 ? 'mb-1.5' : 'mb-2'}`}>
            <span>Total: R$ {formatPrice(cartTotal)}</span>
            <span className="text-muted-foreground">{cartCount} item(ns)</span>
          </div>
          {currentStep < 4 ? (
            <div className="flex gap-2 sm:gap-3">
              <Button type="button" variant="outline" onClick={goBack} className="h-11 flex-1 sm:h-12 sm:flex-none sm:w-32">
                <ChevronLeft className="h-4 w-4 mr-1" /> Voltar
              </Button>
              <Button type="button" onClick={goNext} className="h-11 flex-1 sm:h-12">
                {getNextStepLabel(currentStep)} <ChevronRight className="h-4 w-4 ml-1" />
              </Button>
            </div>
          ) : (
            <div className="flex gap-2 sm:gap-3">
              <Button type="button" variant="outline" onClick={goBack} className="h-11 sm:h-12 sm:w-32">
                <ChevronLeft className="h-4 w-4 mr-1" /> Voltar
              </Button>
              <Button type="button" onClick={handleCheckout} disabled={submitting} className="h-11 flex-1 text-sm font-semibold sm:h-14 sm:text-base">
                {getSubmitLabel(submitting)}
              </Button>
            </div>
          )}
        </div>
      )}

      {!orderSuccess && currentStep >= 1 && (
        <div className="hidden lg:block sticky bottom-0 bg-background border-t pt-3 pb-4 mt-4">
          <div className="container mx-auto px-4">
            {currentStep < 4 ? (
              <div className="flex gap-3 justify-end">
                <Button type="button" variant="outline" onClick={goBack} className="h-12 w-32">
                  <ChevronLeft className="h-4 w-4 mr-1" /> Voltar
                </Button>
                <Button type="button" onClick={goNext} className="h-12 w-48">
                  {getNextStepLabel(currentStep)} <ChevronRight className="h-4 w-4 ml-1" />
                </Button>
              </div>
            ) : (
              <div className="flex gap-3 justify-end">
                <Button type="button" variant="outline" onClick={goBack} className="h-12 w-32">
                  <ChevronLeft className="h-4 w-4 mr-1" /> Voltar
                </Button>
                <Button type="button" onClick={handleCheckout} disabled={submitting} className="h-14 w-52 text-base font-semibold">
                  {getSubmitLabel(submitting)}
                </Button>
              </div>
            )}
          </div>
        </div>
      )}

      {showMobileSummaryButton && (
        <button
          onClick={() => setMobileSummaryOpen(true)}
          className="fixed bottom-5 left-4 right-4 z-[60] flex items-center justify-between gap-3 rounded-2xl bg-primary px-5 py-3.5 text-primary-foreground shadow-2xl transition active:scale-[0.98] lg:hidden"
        >
          <div className="flex items-center gap-2">
            <div className="relative">
              <ShoppingCart className="h-5 w-5" />
              <span className="absolute -top-2 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-white text-[10px] font-bold text-primary">
                {cartCount}
              </span>
            </div>
            <span className="text-sm font-semibold">Ver carrinho</span>
          </div>
          <span className="text-sm font-bold">R$ {formatPrice(cartTotal)}</span>
        </button>
      )}

      {!orderSuccess && currentStep === 0 && cart.length > 0 && (
        <div className="fixed bottom-0 left-0 right-0 z-[55] bg-background border-t pt-3 pb-4 px-4 hidden lg:block">
          <div className="container mx-auto flex items-center justify-between gap-4">
            <div className="text-sm font-bold">
              <span>Total: R$ {formatPrice(cartTotal)}</span>
              <span className="text-muted-foreground font-normal ml-2">{cartCount} item(ns)</span>
            </div>
            <Button type="button" onClick={handleStartCheckout} disabled={!isStoreOpen} className="h-11 px-6 sm:h-12 sm:px-8">
              Continuar pedido <ChevronRight className="h-4 w-4 ml-1" />
            </Button>
          </div>
        </div>
      )}

      {!orderSuccess && currentStep === 0 && cart.length > 0 && isStoreOpen && (
        <div className="fixed bottom-0 left-0 right-0 z-[55] border-t bg-background px-3 pt-2.5 pb-3 lg:hidden">
          <div className="mb-1.5 flex items-center justify-between text-sm font-bold">
            <span>Total: R$ {formatPrice(cartTotal)}</span>
            <span className="text-muted-foreground">{cartCount} item(ns)</span>
          </div>
          <Button type="button" onClick={handleStartCheckout} className="h-11 w-full sm:h-12">
            Continuar pedido <ChevronRight className="h-4 w-4 ml-1" />
          </Button>
        </div>
      )}

      <Sheet open={mobileSummaryOpen} onOpenChange={setMobileSummaryOpen}>
        <SheetContent side="bottom" className="z-[70] w-full max-h-[85vh] overflow-x-hidden overflow-y-auto px-4 py-4 pb-6 sm:mx-auto sm:max-w-lg sm:px-6 sm:py-6 sm:pb-8">
          <SheetHeader>
            <SheetTitle>Seu pedido</SheetTitle>
          </SheetHeader>
          <div className="mt-4 min-w-0 space-y-4 overflow-hidden pb-2 break-words">
            {renderSummaryContent(currentStep === 0 ? 'cart' : currentStep === 4 ? 'review' : 'checkout', currentStep >= 1)}
          </div>
        </SheetContent>
      </Sheet>

      <Dialog
        open={showSelectionDialog}
        onOpenChange={(open) => {
          setShowSelectionDialog(open)
          if (!open) {
            resetSelectionState()
          }
        }}
      >
        <DialogContent className="flex flex-col max-w-lg max-h-[90dvh] rounded-2xl border border-border/50 bg-background p-0 shadow-2xl gap-0 overflow-hidden">
          {selectedProduct && (
            <>
              {/* Header com imagem em destaque */}
              <div className="shrink-0 border-b">
                <div className="relative h-52 w-full overflow-hidden bg-muted sm:h-64">
                  {selectedProduct.image ? (
                    <Image
                      src={resolveImageUrl(selectedProduct.image) || ""}
                      alt={selectedProduct.name}
                      fill
                      className="object-cover"
                      sizes="(max-width: 640px) 100vw, 512px"
                      unoptimized
                      priority
                    />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center">
                      <ImageIcon className="h-12 w-12 text-muted-foreground/40" />
                    </div>
                  )}
                </div>
                <div className="space-y-2 px-5 pt-4 pb-4">
                  <DialogHeader className="space-y-1 text-left">
                    <DialogTitle className="text-xl font-semibold leading-tight">{selectedProduct.name}</DialogTitle>
                    <DialogDescription className="text-sm text-muted-foreground">
                      {productNeedsCustomization(selectedProduct)
                        ? 'Escolha as opções e adicione ao seu pedido.'
                        : 'Confira os detalhes e adicione ao seu pedido.'}
                    </DialogDescription>
                  </DialogHeader>
                  {selectedProduct.description ? (
                    <p className="text-sm text-muted-foreground leading-relaxed whitespace-pre-wrap">
                      {selectedProduct.description}
                    </p>
                  ) : (
                    <p className="text-sm text-muted-foreground italic">Sem descrição adicional.</p>
                  )}
                  <div className="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                    Preço: <span className="text-primary font-semibold ml-1">R$ {formatPrice(selectedProduct.promotional_price || selectedProduct.price)}</span>
                  </div>
                </div>
              </div>

              {/* Corpo scrollável */}
              <div className="flex-1 overflow-y-auto px-5 py-4 space-y-5">
                {selectedProduct.variations && selectedProduct.variations.length > 0 && (
                  <div className="space-y-2">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Variações</p>
                      <p className="text-xs text-muted-foreground">Escolha uma opção</p>
                    </div>
                    <RadioGroup value={selectedVariation} onValueChange={setSelectedVariation} className="grid gap-2">
                      {selectedProduct.variations.map((variation) => {
                        const isSelected = selectedVariation === variation.id
                        return (
                          <div
                            key={variation.id}
                            className={`flex items-center justify-between rounded-xl border px-3 py-2.5 transition cursor-pointer ${
                              isSelected ? 'border-primary bg-primary/5 shadow-sm' : 'border-border hover:bg-muted/40'
                            }`}
                          >
                            <RadioGroupItem value={variation.id} id={`variation-${variation.id}`} className="sr-only" />
                            <label htmlFor={`variation-${variation.id}`} className="flex flex-1 cursor-pointer items-center justify-between gap-3">
                              <div>
                                <p className="text-sm font-medium text-foreground">{variation.name}</p>
                                <p className="text-xs text-muted-foreground">
                                  {variation.price > 0 ? `+ R$ ${formatPrice(variation.price)}` : 'Sem custo adicional'}
                                </p>
                              </div>
                              <div className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border ${isSelected ? 'border-primary bg-primary/10 text-primary' : 'border-border'}`}>
                                {isSelected && <Check className="h-3 w-3" />}
                              </div>
                            </label>
                          </div>
                        )
                      })}
                    </RadioGroup>
                  </div>
                )}

                {selectedProduct.optionals && selectedProduct.optionals.length > 0 && (
                  <div className="space-y-2">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Opcionais</p>
                      <p className="text-xs text-muted-foreground">Adicione itens extras</p>
                    </div>
                    <div className="space-y-2">
                      {selectedProduct.optionals.map((optional) => {
                        const qty = selectedOptionalsQty[optional.id] || 0
                        return (
                          <div
                            key={optional.id}
                            className="flex items-center justify-between rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5"
                          >
                            <div className="min-w-0 flex-1 mr-3">
                              <p className="text-sm font-medium text-foreground truncate">{optional.name}</p>
                              <p className="text-xs text-muted-foreground">+ R$ {formatPrice(optional.price)}</p>
                            </div>
                            <div className="flex items-center gap-1.5 shrink-0">
                              <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="h-8 w-8"
                                onClick={() => handleOptionalQuantityChange(optional.id, -1)}
                                disabled={qty === 0}
                              >
                                <Minus className="h-3.5 w-3.5" />
                              </Button>
                              <span className="w-5 text-center text-sm font-semibold tabular-nums">{qty}</span>
                              <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="h-8 w-8"
                                onClick={() => handleOptionalQuantityChange(optional.id, 1)}
                              >
                                <Plus className="h-3.5 w-3.5" />
                              </Button>
                            </div>
                          </div>
                        )
                      })}
                    </div>
                  </div>
                )}
              </div>

              {/* Footer fixo */}
              <div className="shrink-0 border-t bg-background px-5 py-4 space-y-3">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Total estimado</p>
                    <p className="text-xl font-bold text-primary">R$ {formatPrice(calculateSelectionTotal())}</p>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="text-muted-foreground hover:text-foreground"
                    onClick={() => { setShowSelectionDialog(false); resetSelectionState() }}
                  >
                    Fechar
                  </Button>
                </div>
                <Button
                  type="button"
                  className="w-full rounded-full"
                  size="lg"
                  onClick={confirmAddToCart}
                  disabled={!!(selectedProduct.variations && selectedProduct.variations.length > 0 && !selectedVariation)}
                >
                  Adicionar · R$ {formatPrice(calculateSelectionTotal())}
                </Button>
              </div>
            </>
          )}
        </DialogContent>
      </Dialog>

      <Dialog
        open={showSuccessModal && !!orderResult}
        onOpenChange={(open) => setShowSuccessModal(open)}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader className="text-center space-y-2">
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
              <Check className="h-7 w-7" />
            </div>
            <DialogTitle className="text-2xl">Pedido realizado com sucesso!</DialogTitle>
            <DialogDescription className="text-base">
              Número do pedido <span className="font-semibold text-foreground">#{orderResult?.order_id}</span>
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-3 text-sm text-muted-foreground text-center">
            {orderResult?.estimated_delivery_minutes ? (
              <p>Tempo estimado: ~{orderResult.estimated_delivery_minutes} minutos</p>
            ) : null}
            {orderResult?.delivery_fee_type === 'negotiable' && (
              <p>A taxa de entrega será confirmada pelo restaurante.</p>
            )}
            <p>Você receberá atualizações assim que o restaurante começar a preparar seu pedido.</p>
            <p>Guarde o número acima. Você pode enviar o pedido ao restaurante pelo WhatsApp.</p>
          </div>

          <div className="flex flex-col gap-3 w-full pt-2">
            {orderResult?.whatsapp_link && (
              <Button asChild className="w-full">
                <a href={orderResult.whatsapp_link} target="_blank" rel="noopener noreferrer">
                  Enviar para o WhatsApp do restaurante
                </a>
              </Button>
            )}
            <Button
              variant={orderResult?.whatsapp_link ? "outline" : "default"}
              className="w-full"
              onClick={() => setShowSuccessModal(false)}
            >
              Fechar
            </Button>
          </div>
        </DialogContent>
      </Dialog>

      <ReviewModal
        isOpen={showReviewModal}
        onClose={() => setShowReviewModal(false)}
        onSubmit={handleReviewSubmit}
        orderData={{
          id: 0,
          identify: currentOrderIdentify || '—',
        }}
        tenantId={tenantIdForReview}
        customerData={{
          name: clientData.name,
          email: clientData.email,
        }}
      />

      <ReviewsSection tenantSlug={slug} />

      {/* Footer */}
      <SiteFooter />
    </div>
  )
}
