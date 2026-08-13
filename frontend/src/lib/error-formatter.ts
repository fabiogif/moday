/**
 * Formata mensagens de erro do backend para exibição amigável
 */

interface ApiError {
  data?: {
    message?: string
    errors?: Record<string, string[]>
  }
  message?: string
}

/**
 * Formata uma mensagem de erro do backend
 */
export function formatErrorMessage(error: any): string {
  // Se for string, retornar direto
  if (typeof error === 'string') {
    return error
  }

  // Tentar pegar mensagem do backend
  const backendMessage = error?.data?.message || error?.message

  if (backendMessage) {
    // Remover prefixos técnicos
    return backendMessage
      .replace('ApiClient: Erro HTTP ', '')
      .replace('Erro HTTP ', '')
      .replace(/^\d+\s+":\s+"/, '')  // Remove "422 ":"
      .replace(/^"/, '')              // Remove aspas inicial
      .replace(/"$/, '')              // Remove aspas final
      .trim()
  }

  return 'Ocorreu um erro. Por favor, tente novamente.'
}

/**
 * Extrai todos os erros de validação do backend
 */
export function extractValidationErrors(error: any): Record<string, string> {
  const errors: Record<string, string> = {}

  // Formato 1: error.data.errors (Laravel padrão)
  if (error?.data?.errors) {
    Object.entries(error.data.errors).forEach(([field, messages]) => {
      if (Array.isArray(messages) && messages.length > 0) {
        errors[field] = messages[0]
      } else if (typeof messages === 'string') {
        errors[field] = messages
      }
    })
  }

  // Formato 2: error.errors (alguns endpoints retornam assim)
  if (error?.errors && !error?.data?.errors) {
    Object.entries(error.errors).forEach(([field, messages]) => {
      if (Array.isArray(messages) && messages.length > 0) {
        errors[field] = messages[0]
      } else if (typeof messages === 'string') {
        errors[field] = messages
      }
    })
  }

  // Formato 3: error.response?.data?.errors (Axios)
  if (error?.response?.data?.errors) {
    Object.entries(error.response.data.errors).forEach(([field, messages]) => {
      if (Array.isArray(messages) && messages.length > 0) {
        errors[field] = messages[0]
      } else if (typeof messages === 'string') {
        errors[field] = messages
      }
    })
  }

  // Mensagem principal como fallback
  const mainMessage = formatErrorMessage(error)
  if (mainMessage && Object.keys(errors).length === 0) {
    errors._general = mainMessage
  }

  return errors
}

/**
 * Cria uma mensagem de erro formatada para toast
 */
export function formatErrorForToast(error: any): string {
  const errors = extractValidationErrors(error)
  
  // Se tem erro geral, usar ele
  if (errors._general) {
    return errors._general
  }

  // Se tem erros de campos, listar (máximo 3)
  const fieldErrors = Object.entries(errors)
    .filter(([key]) => key !== '_general')
    .slice(0, 3)

  if (fieldErrors.length === 1) {
    return fieldErrors[0][1]
  }

  if (fieldErrors.length > 1) {
    const errorList = fieldErrors.map(([_, msg]) => `• ${msg}`).join('\n')
    return `Erros de validação:\n${errorList}`
  }

  return 'Erro ao processar requisição'
}

/**
 * Traduz nomes de campos do backend para português
 */
export function translateFieldName(field: string): string {
  const translations: Record<string, string> = {
    // Cliente
    'name': 'Nome',
    'cpf': 'CPF',
    'email': 'Email',
    'phone': 'Telefone',
    'address': 'Endereço',
    'city': 'Cidade',
    'state': 'Estado',
    'zip_code': 'CEP',
    'neighborhood': 'Bairro',
    'number': 'Número',
    'complement': 'Complemento',
    
    // Cliente com prefixo
    'client.name': 'Nome do cliente',
    'client.cpf': 'CPF do cliente',
    'client.email': 'Email do cliente',
    'client.phone': 'Telefone do cliente',
    
    // Delivery
    'delivery.address': 'Endereço de entrega',
    'delivery.city': 'Cidade de entrega',
    'delivery.state': 'Estado de entrega',
    'delivery.zip_code': 'CEP de entrega',
    'delivery.neighborhood': 'Bairro de entrega',
    'delivery.number': 'Número de entrega',
    
    // Produtos
    'products': 'Produtos',
    'products.*.identify': 'Produto',
    'products.*.qty': 'Quantidade',
    'products.*.price': 'Preço',
    
    // Pedido
    'payment_method': 'Forma de pagamento',
    'payment_method_id': 'Forma de pagamento',
    'split_payments': 'Pagamentos divididos',
    'split_payments.*.payment_method_id': 'Método de pagamento',
    'split_payments.*.amount': 'Valor do pagamento',
    'shipping_method': 'Método de entrega',
    'table': 'Mesa',
    'status': 'Status',
    
    // Empresa
    'cnpj': 'CNPJ',
    'zipcode': 'CEP',
    
    // Eventos
    'title': 'Título',
    'type': 'Tipo',
    'color': 'Cor',
    'start_date': 'Data e Hora',
    'duration_minutes': 'Duração',
    'location': 'Local',
    'description': 'Descrição',
    'client_ids': 'Clientes',
    'notification_channels': 'Canais de Notificação',
    'is_active': 'Status',
    
    // Módulo Financeiro - Contas a Pagar/Receber
    'issue_date': 'Data de Emissão',
    'due_date': 'Data de Vencimento',
    'payment_date': 'Data de Pagamento',
    'receipt_date': 'Data de Recebimento',
    'amount': 'Valor',
    'amount_paid': 'Valor Pago',
    'amount_received': 'Valor Recebido',
    'discount': 'Desconto',
    'interest': 'Juros',
    'fine': 'Multa',
    'document_number': 'Número do Documento',
    'installment_number': 'Número da Parcela',
    'total_installments': 'Total de Parcelas',
    'financial_category_id': 'Categoria Financeira',
    'supplier_id': 'Fornecedor',
    'client_id': 'Cliente',
    'order_id': 'Pedido',
    'notes': 'Observações',
  }

  return translations[field] || field
}

/**
 * Formata mensagem de erro com nome do campo traduzido
 */
export function formatFieldError(field: string, message: string): string {
  const fieldName = translateFieldName(field)
  
  // Se a mensagem já contém o nome do campo traduzido, retornar como está
  if (message.includes(fieldName)) {
    return message
  }
  
  // Adicionar nome do campo se necessário
  return `${fieldName}: ${message}`
}

