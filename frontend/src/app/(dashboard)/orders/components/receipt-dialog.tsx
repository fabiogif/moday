"use client"

import { useState, useEffect } from "react"
import { format } from "date-fns"
import { ptBR } from "date-fns/locale"
import {
  Printer,
  Mail,
  MessageSquare,
  Download,
  X,
  FileText,
  User,
  Phone,
  MapPin,
  Package,
  Calendar,
  Clock,
  CreditCard,
} from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Separator } from "@/components/ui/separator"
import { ScrollArea } from "@/components/ui/scroll-area"
import { toast } from "sonner"
import { OrderReceipt, Order } from "../types"

interface ReceiptDialogProps {
  order: OrderReceipt | Order | null
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function ReceiptDialog({ order, open, onOpenChange }: ReceiptDialogProps) {
  const [isPrinting, setIsPrinting] = useState(false)
  const [isEmailing, setIsEmailing] = useState(false)
  const [isSharing, setIsSharing] = useState(false)

  if (!order) return null

  // Type guard para diferenciar entre Order e OrderReceipt
  const isOrderReceipt = (order: Order | OrderReceipt): order is OrderReceipt => {
    return 'items' in order && order.items !== undefined
  }

  const orderItems = isOrderReceipt(order) ? order.items : order.products || []

  // Debug: Log completo do pedido recebido
  console.log('ReceiptDialog - Pedido completo:', order)
  console.log('ReceiptDialog - Cliente:', order.client)
  console.log('ReceiptDialog - Cliente JSON:', JSON.stringify(order.client, null, 2))
  console.log('ReceiptDialog - Todas as keys do pedido:', Object.keys(order))
  console.log('ReceiptDialog - Tentando acessar:', {
    'order.client?.name': order.client?.name,
    'order.client?.email': order.client?.email,
    'order.client?.phone': order.client?.phone,
    'order.customerName': (order as any).customerName,
    'order.customer?.name': (order as any).customer?.name
  })
  console.log('ReceiptDialog - Produtos:', orderItems)

  // Get order number from either type
  const orderNumber = (order as OrderReceipt).orderNumber || (order as Order).identify || 'N/A'
  const orderDate = (order as OrderReceipt).orderDate || (order as Order).date
  const isDelivery = (order as OrderReceipt).isDelivery || (order as Order).is_delivery || false

  const formatCurrency = (value: number | string | undefined) => {
    // Debug: Log do valor recebido
    console.log('formatCurrency recebeu:', value, 'tipo:', typeof value)
    
    // Converter para número se for string
    const numValue = typeof value === 'string' ? parseFloat(value) : value
    
    // Verificar se é um número válido
    if (numValue === undefined || numValue === null || isNaN(numValue)) {
      console.warn('formatCurrency: valor inválido:', value)
      return 'R$ 0,00'
    }
    
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(numValue)
  }

  const formatDate = (dateString: string | null | undefined) => {
    if (!dateString) {
      return 'Data não disponível'
    }
    
    try {
      const date = new Date(dateString)
      if (isNaN(date.getTime())) {
        return 'Data inválida'
      }
      return format(date, "dd/MM/yyyy 'às' HH:mm", { locale: ptBR })
    } catch (error) {
      console.error('Erro ao formatar data:', error)
      return 'Data inválida'
    }
  }

  const getFullDeliveryAddress = () => {
    const orderReceipt = order as OrderReceipt
    const orderBase = order as Order
    
    if (orderReceipt.useClientAddress && order.client?.address) {
      const parts = [
        order.client?.address,
        order.client?.number,
        order.client?.complement,
        order.client?.neighborhood,
        order.client?.city,
        order.client?.state,
        (order.client as any)?.zip_code || (order.client as any)?.zipCode
      ].filter(Boolean)
      return parts.join(", ")
    }

    if ((orderReceipt.isDelivery || orderBase.is_delivery) && orderReceipt.deliveryAddress) {
      const parts = [
        orderReceipt.deliveryAddress,
        orderReceipt.deliveryNumber,
        orderReceipt.deliveryComplement,
        orderReceipt.deliveryNeighborhood,
        orderReceipt.deliveryCity,
        orderReceipt.deliveryState,
        orderReceipt.deliveryZipCode
      ].filter(Boolean)
      return parts.join(", ")
    }
    
    // Tentar usar campos de Order
    if (orderBase.is_delivery && (orderBase.delivery_address || orderBase.full_delivery_address)) {
      return orderBase.full_delivery_address || orderBase.delivery_address || "Endereço não informado"
    }

    return "Endereço não informado"
  }

  const handlePrint = () => {
    setIsPrinting(true)
    
    // Criar uma nova janela para impressão
    const printWindow = window.open('', '_blank')
    if (!printWindow) {
      toast.error("Não foi possível abrir a janela de impressão. Verifique se o pop-up está bloqueado.")
      setIsPrinting(false)
      return
    }

    const printContent = generatePrintContent()
    printWindow.document.write(printContent)
    printWindow.document.close()
    
    // Aguardar o carregamento e imprimir
    printWindow.onload = () => {
      printWindow.print()
      printWindow.close()
      setIsPrinting(false)
      toast.success("Recibo enviado para impressão")
    }
  }

  const generatePrintContent = () => {
    return `
      <!DOCTYPE html>
      <html>
      <head>
        <title>Recibo - Pedido #${orderNumber}</title>
        <style>
          body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            font-size: 12px;
            line-height: 1.4;
          }
          .header { 
            text-align: center; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
            margin-bottom: 20px;
          }
          .company-name { 
            font-size: 18px; 
            font-weight: bold; 
            margin-bottom: 5px;
          }
          .order-info { 
            margin-bottom: 15px; 
          }
          .client-info { 
            margin-bottom: 15px; 
          }
          .items-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px;
          }
          .items-table th, .items-table td { 
            border: 1px solid #000; 
            padding: 5px; 
            text-align: left;
          }
          .items-table th { 
            background-color: #f0f0f0; 
            font-weight: bold;
          }
          .total { 
            text-align: right; 
            font-weight: bold; 
            font-size: 14px;
            margin-top: 10px;
          }
          .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 10px;
          }
          @media print {
            body { margin: 0; }
          }
        </style>
      </head>
      <body>
        <div class="header">
          <div class="company-name">MODAY RESTAURANTE</div>
          <div>Rua Exemplo, 123 - Centro</div>
          <div>Tel: (11) 99999-9999</div>
        </div>
        
        <div class="order-info">
          <strong>PEDIDO #${orderNumber}</strong><br>
          Data: ${formatDate(orderDate)}<br>
          Status: ${order.status}<br>
          ${isDelivery ? 'Tipo: Delivery' : 'Tipo: Balcão'}
        </div>
        
        <div class="client-info">
          <strong>CLIENTE:</strong><br>
          ${order.client?.name || (order as any).customerName || (order as any).customer?.name || 'N/A'}<br>
          ${order.client?.email || (order as any).customerEmail || (order as any).customer?.email || 'N/A'}<br>
          ${order.client?.phone || (order as any).customerPhone || (order as any).customer?.phone || 'N/A'}
          ${isDelivery ? `<br><br><strong>ENDEREÇO DE ENTREGA:</strong><br>${getFullDeliveryAddress()}` : ''}
        </div>
        
        <table class="items-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Qtd</th>
              <th>Preço</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            ${orderItems.map(item => `
              <tr>
                <td>${item.name}</td>
                <td>${item.quantity || 1}</td>
                <td>${formatCurrency(item.price)}</td>
                <td>${formatCurrency(item.total)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        
        <div class="total">
          TOTAL: ${formatCurrency(order.total)}
        </div>
        
        ${order.comment ? `<div style="margin-top: 15px;"><strong>Observações:</strong><br>${order.comment}</div>` : ''}
        
        <div class="footer">
          <p>Obrigado pela preferência!</p>
          <p>Este é um recibo de pedido gerado automaticamente.</p>
        </div>
      </body>
      </html>
    `
  }

  const handleEmail = async () => {
    setIsEmailing(true)
    
    try {
      // Aqui você implementaria a lógica de envio de email
      // Por enquanto, vamos simular
      await new Promise(resolve => setTimeout(resolve, 2000))
      
      toast.success(`Recibo enviado por email para ${order.client?.email || (order as any).customerEmail || (order as any).customer?.email || 'N/A'}`)
    } catch (error) {
      toast.error("Não foi possível enviar o email. Tente novamente.")
    } finally {
      setIsEmailing(false)
    }
  }

  const handleWhatsApp = () => {
    setIsSharing(true)
    
    const message = `Olá! Aqui está o recibo do seu pedido #${orderNumber}:

📋 *PEDIDO #${orderNumber}*
📅 Data: ${formatDate(orderDate)}
💰 Total: ${formatCurrency(order.total)}

${orderItems.map(item => `• ${item.name} - ${item.quantity || 1}x ${formatCurrency(item.price)} = ${formatCurrency(item.total)}`).join('\n')}

${order.comment ? `\n📝 Observações: ${order.comment}` : ''}

Obrigado pela preferência! 🍽️`

    const whatsappUrl = order.client?.phone ? `https://wa.me/55${order.client.phone.replace(/\D/g, '')}?text=${encodeURIComponent(message)}` : null
    if (!whatsappUrl) {
      toast.error('Cliente não possui telefone cadastrado')
      return
    }
    window.open(whatsappUrl, '_blank')
    
    setTimeout(() => {
      setIsSharing(false)
      toast.success("WhatsApp aberto com o recibo")
    }, 1000)
  }

  const handleDownload = () => {
    const printContent = generatePrintContent()
    const blob = new Blob([printContent], { type: 'text/html' })
    const url = URL.createObjectURL(blob)
    
    const link = document.createElement('a')
    link.href = url
    link.download = `recibo-pedido-${orderNumber}.html`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    URL.revokeObjectURL(url)
    
    toast.success("Recibo baixado com sucesso")
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden">
        <DialogHeader>
          <div className="flex items-center justify-between">
            <DialogTitle className="text-xl font-semibold">
              Recibo - Pedido #{orderNumber}
            </DialogTitle>
            <Badge variant="outline">
              {order.status}
            </Badge>
          </div>
        </DialogHeader>

        <ScrollArea className="max-h-[calc(90vh-200px)] pr-4">
          <div className="space-y-6">
            {/* Cabeçalho do Recibo */}
            <div className="text-center border-b pb-4">
              <h2 className="text-2xl font-bold">MODAY RESTAURANTE</h2>
              <p className="text-sm text-muted-foreground">Rua Exemplo, 123 - Centro</p>
              <p className="text-sm text-muted-foreground">Tel: (11) 99999-9999</p>
            </div>

            {/* Informações do Pedido */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <h3 className="font-semibold mb-2">Informações do Pedido</h3>
                <div className="space-y-1 text-sm">
                  <p><strong>Número:</strong> #{orderNumber}</p>
                  <p><strong>Data:</strong> {formatDate(orderDate)}</p>
                  <p><strong>Tipo:</strong> {isDelivery ? 'Delivery' : 'Balcão'}</p>
                  {order.table && (
                    <p><strong>Mesa:</strong> {order.table.name}</p>
                  )}
                </div>
              </div>

              <div>
                <h3 className="font-semibold mb-2">Cliente</h3>
                <div className="space-y-1 text-sm">
                  <p><strong>Nome:</strong> {
                      order.client?.name || 
                      (order as any).customerName || 
                      (order as any).customer?.name || 
                      (order as any).client_name ||
                      'N/A'
                    }</p>
                  <p><strong>Email:</strong> {
                      order.client?.email || 
                      (order as any).customerEmail || 
                      (order as any).customer?.email ||
                      (order as any).client_email ||
                      'N/A'
                    }</p>
                  <p><strong>Telefone:</strong> {
                      order.client?.phone || 
                      (order as any).customerPhone || 
                      (order as any).customer?.phone ||
                      (order as any).client_phone ||
                      'N/A'
                    }</p>
                </div>
              </div>
            </div>

            {/* Endereço de Entrega */}
            {isDelivery && (
              <div>
                <h3 className="font-semibold mb-2">Endereço de Entrega</h3>
                <p className="text-sm">{getFullDeliveryAddress()}</p>
                {((order as OrderReceipt).deliveryNotes || (order as Order).delivery_notes) && (
                  <p className="text-sm mt-2"><strong>Observações:</strong> {(order as OrderReceipt).deliveryNotes || (order as Order).delivery_notes}</p>
                )}
              </div>
            )}

            {/* Itens do Pedido */}
            <div>
              <h3 className="font-semibold mb-3">Itens do Pedido</h3>
              <div className="border rounded-lg overflow-hidden">
                <table className="w-full">
                  <thead className="bg-muted">
                    <tr>
                      <th className="text-left p-3 font-medium">Item</th>
                      <th className="text-center p-3 font-medium">Qtd</th>
                      <th className="text-right p-3 font-medium">Preço</th>
                      <th className="text-right p-3 font-medium">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {orderItems.map((item, index) => (<tr key={item.id || `item-${index}`} className="border-t">
                        <td className="p-3">{item.name}</td>
                        <td className="p-3 text-center">{item.quantity || 1}</td>
                        <td className="p-3 text-right">{formatCurrency(item.price)}</td>
                        <td className="p-3 text-right font-medium">{formatCurrency(item.total)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              
              <div className="flex justify-end mt-4">
                <div className="text-right">
                  <p className="text-lg font-bold">
                    TOTAL: {formatCurrency(order.total)}
                  </p>
                </div>
              </div>
            </div>

            {/* Observações */}
            {order.comment && (
              <div>
                <h3 className="font-semibold mb-2">Observações</h3>
                <p className="text-sm bg-muted p-3 rounded-lg">{order.comment}</p>
              </div>
            )}
          </div>
        </ScrollArea>

        {/* Ações */}
        <div className="flex flex-wrap gap-2 pt-4 border-t">
          <Button 
            onClick={handlePrint} 
            disabled={isPrinting}
            className="flex-1 min-w-[120px]"
          >
            <Printer className="mr-2 h-4 w-4" />
            {isPrinting ? "Imprimindo..." : "Imprimir"}
          </Button>
          
          <Button 
            onClick={handleEmail} 
            disabled={isEmailing}
            variant="outline"
            className="flex-1 min-w-[120px]"
          >
            <Mail className="mr-2 h-4 w-4" />
            {isEmailing ? "Enviando..." : "Email"}
          </Button>
          
          <Button 
            onClick={handleWhatsApp} 
            disabled={isSharing}
            variant="outline"
            className="flex-1 min-w-[120px]"
          >
            <MessageSquare className="mr-2 h-4 w-4" />
            {isSharing ? "Abrindo..." : "WhatsApp"}
          </Button>
          
          <Button 
            onClick={handleDownload} 
            variant="outline"
            className="flex-1 min-w-[120px]"
          >
            <Download className="mr-2 h-4 w-4" />
            Baixar
          </Button>
        </div>

        <div className="flex justify-end gap-2 pt-2">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Fechar
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}













