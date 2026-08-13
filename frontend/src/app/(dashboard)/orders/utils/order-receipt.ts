import { format } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import type { Order } from '../types'

export interface NormalizedOrderItem {
  name: string
  quantity: number
  unitPrice: number
  lineTotal: number
}

export function toNumber(value: unknown): number {
  if (typeof value === 'number') return value
  if (typeof value === 'string') {
    const parsed = Number(value)
    return Number.isNaN(parsed) ? 0 : parsed
  }
  return 0
}

export function formatReceiptCurrency(value: unknown): string {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(toNumber(value))
}

export function formatReceiptDate(dateString?: string | null): string {
  if (!dateString) return 'Data não disponível'

  try {
    const date = new Date(dateString)
    if (Number.isNaN(date.getTime())) {
      return dateString
    }
    return format(date, "dd/MM/yyyy 'às' HH:mm", { locale: ptBR })
  } catch {
    return dateString
  }
}

export function normalizeOrderItems(order: Order): NormalizedOrderItem[] {
  const products = order.products || []

  return products.map((product) => {
    const quantityRaw =
      product.quantity ??
      (product as any).qty ??
      (product as any)?.pivot?.quantity ??
      (product as any)?.pivot?.qty ??
      1

    const quantity = Number(quantityRaw) > 0 ? Number(quantityRaw) : 1
    const unitPrice = toNumber(
      (product as any).pivot?.price ??
        (product as any).unit_price ??
        product.price
    )
    const lineTotal = toNumber(product.total) || unitPrice * quantity

    return {
      name: product.name,
      quantity,
      unitPrice,
      lineTotal,
    }
  })
}

export function getClientPhone(order: Order): string | null {
  return (
    order.client?.phone ||
    (order as any).client_phone ||
    (order as any).customerPhone ||
    null
  )
}

export function getFullDeliveryAddress(order: Order): string | null {
  if (order.full_delivery_address) {
    return order.full_delivery_address
  }

  if (order.is_delivery && order.delivery_address) {
    const parts = [
      order.delivery_address,
      order.delivery_number,
      order.delivery_complement,
      order.delivery_neighborhood,
      order.delivery_city,
      order.delivery_state,
      order.delivery_zip_code ? `CEP: ${order.delivery_zip_code}` : null,
    ].filter(Boolean)

    return parts.join(', ')
  }

  return null
}

export function generateOrderReceiptHtml(order: Order, companyName = 'Alba Tec'): string {
  const orderNumber = order.identify || order.orderNumber || 'N/A'
  const orderDate = order.created_at || order.date || order.orderDate
  const items = normalizeOrderItems(order)
  const isDelivery = Boolean(order.is_delivery)
  const clientName =
    order.client?.name || order.customerName || (order as any).client_full_name || 'N/A'
  const clientEmail = order.client?.email || order.customerEmail || (order as any).client_email || ''
  const clientPhone = getClientPhone(order) || ''
  const paymentLabel =
    (order as any).payment_method_name ||
    (order as any).payment_method ||
    'Não informado'
  const deliveryAddress = getFullDeliveryAddress(order)

  return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <title>Comprovante - Pedido #${orderNumber}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; line-height: 1.5; color: #000; }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    .company-name { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
    .section { margin-bottom: 16px; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .items-table th, .items-table td { border: 1px solid #000; padding: 6px; text-align: left; }
    .items-table th { background: #f0f0f0; }
    .total { text-align: right; font-weight: bold; font-size: 16px; margin-top: 8px; }
    .footer { margin-top: 24px; text-align: center; font-size: 10px; color: #444; }
  </style>
</head>
<body>
  <div class="header">
    <div class="company-name">${companyName}</div>
    <div>Comprovante de Pedido</div>
  </div>

  <div class="section">
    <strong>PEDIDO #${orderNumber}</strong><br>
    Data: ${formatReceiptDate(orderDate)}<br>
    Status: ${order.status}<br>
    Tipo: ${isDelivery ? 'Delivery' : 'Balcão'}<br>
    Pagamento: ${paymentLabel}
  </div>

  <div class="section">
    <strong>CLIENTE</strong><br>
    ${clientName}<br>
    ${clientEmail ? `${clientEmail}<br>` : ''}
    ${clientPhone ? `${clientPhone}<br>` : ''}
    ${isDelivery && deliveryAddress ? `<br><strong>ENDEREÇO DE ENTREGA</strong><br>${deliveryAddress}` : ''}
    ${order.delivery_notes ? `<br><br><strong>Observações:</strong> ${order.delivery_notes}` : ''}
  </div>

  <table class="items-table">
    <thead>
      <tr>
        <th>Item</th>
        <th>Qtd</th>
        <th>Preço unit.</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      ${items
        .map(
          (item) => `
        <tr>
          <td>${item.name}</td>
          <td>${item.quantity}</td>
          <td>${formatReceiptCurrency(item.unitPrice)}</td>
          <td>${formatReceiptCurrency(item.lineTotal)}</td>
        </tr>`
        )
        .join('')}
    </tbody>
  </table>

  <div class="total">TOTAL: ${formatReceiptCurrency(order.total)}</div>

  ${order.comment ? `<div class="section"><strong>Observações do pedido:</strong><br>${order.comment}</div>` : ''}

  <div class="footer">
    <p>Obrigado pela preferência!</p>
    <p>Documento gerado automaticamente pelo sistema.</p>
  </div>
</body>
</html>`
}

export function printOrderReceipt(order: Order, companyName?: string): boolean {
  const html = generateOrderReceiptHtml(order, companyName)

  // Não usar "noopener" aqui: com esse flag, window.open retorna null
  // em navegadores modernos e a impressão falha mesmo com pop-up liberado.
  const printWindow = window.open('', '_blank')

  if (printWindow) {
    try {
      printWindow.opener = null
      printWindow.document.open()
      printWindow.document.write(html)
      printWindow.document.close()

      const triggerPrint = () => {
        try {
          printWindow.focus()
          printWindow.print()
        } finally {
          // Alguns navegadores fecham cedo demais se close() for imediato
          setTimeout(() => {
            try {
              printWindow.close()
            } catch {
              // ignore
            }
          }, 300)
        }
      }

      // document.write nem sempre dispara onload de forma confiável
      if (printWindow.document.readyState === 'complete') {
        setTimeout(triggerPrint, 50)
      } else {
        printWindow.onload = () => setTimeout(triggerPrint, 50)
        setTimeout(triggerPrint, 250)
      }

      return true
    } catch {
      try {
        printWindow.close()
      } catch {
        // ignore
      }
    }
  }

  // Fallback sem pop-up: iframe oculto
  return printReceiptViaIframe(html)
}

function printReceiptViaIframe(html: string): boolean {
  try {
    const iframe = document.createElement('iframe')
    iframe.setAttribute('aria-hidden', 'true')
    iframe.style.position = 'fixed'
    iframe.style.right = '0'
    iframe.style.bottom = '0'
    iframe.style.width = '0'
    iframe.style.height = '0'
    iframe.style.border = '0'
    iframe.style.visibility = 'hidden'
    document.body.appendChild(iframe)

    const frameWindow = iframe.contentWindow
    const frameDocument = frameWindow?.document
    if (!frameWindow || !frameDocument) {
      iframe.remove()
      return false
    }

    frameDocument.open()
    frameDocument.write(html)
    frameDocument.close()

    const cleanup = () => {
      setTimeout(() => {
        iframe.remove()
      }, 1000)
    }

    const triggerPrint = () => {
      try {
        frameWindow.focus()
        frameWindow.print()
      } finally {
        cleanup()
      }
    }

    if (frameDocument.readyState === 'complete') {
      setTimeout(triggerPrint, 50)
    } else {
      iframe.onload = () => setTimeout(triggerPrint, 50)
      setTimeout(triggerPrint, 250)
    }

    return true
  } catch {
    return false
  }
}

function formatWhatsAppPhone(phone: string): string {
  const clean = phone.replace(/\D/g, '')
  if (clean.length === 10 || clean.length === 11) {
    return `55${clean}`
  }
  return clean
}

export function buildOrderWhatsAppMessage(order: Order): string {
  const orderNumber = order.identify || order.orderNumber || 'N/A'
  const orderDate = formatReceiptDate(order.created_at || order.date || order.orderDate)
  const items = normalizeOrderItems(order)
  const paymentLabel =
    (order as any).payment_method_name ||
    (order as any).payment_method ||
    'Não informado'

  const lines = items.map(
    (item) =>
      `• ${item.quantity}x ${item.name} — ${formatReceiptCurrency(item.lineTotal)}`
  )

  const deliveryAddress = getFullDeliveryAddress(order)

  return `Olá! Segue o comprovante do seu pedido *#${orderNumber}*:

📋 *Pedido #${orderNumber}*
📅 Data: ${orderDate}
💳 Pagamento: ${paymentLabel}
💰 *Total: ${formatReceiptCurrency(order.total)}*

*Itens:*
${lines.join('\n')}
${deliveryAddress ? `\n📍 *Entrega:* ${deliveryAddress}` : ''}
${order.delivery_notes ? `\n📝 *Observações:* ${order.delivery_notes}` : ''}
${order.comment ? `\n📝 *Nota:* ${order.comment}` : ''}

Obrigado pela preferência!`
}

export function openOrderWhatsApp(order: Order): boolean {
  const phone = getClientPhone(order)
  if (!phone) {
    return false
  }

  const url = `https://wa.me/${formatWhatsAppPhone(phone)}?text=${encodeURIComponent(
    buildOrderWhatsAppMessage(order)
  )}`

  window.open(url, '_blank', 'noopener,noreferrer')
  return true
}
