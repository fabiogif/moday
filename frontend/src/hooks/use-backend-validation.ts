import { useCallback } from 'react'
import { FieldPath, FieldValues, UseFormSetError } from 'react-hook-form'

interface BackendValidationError {
  field: string
  message: string
}

interface BackendErrorResponse {
  message?: string
  data?: Record<string, string[]>
  errors?: Record<string, string[]>
}

/**
 * Hook para tratar erros de validação do backend e mapeá-los para os campos do formulário
 */
export function useBackendValidation<T extends FieldValues>(
  setError: UseFormSetError<T>
) {
  const handleBackendErrors = useCallback((
    error: any,
    fieldMapping?: Record<string, FieldPath<T>>
  ) => {

    // Se o erro tem dados de validação estruturados
    if (error?.data?.data) {
      const validationErrors = error.data.data
      
      Object.entries(validationErrors).forEach(([field, messages]) => {
        const fieldName = fieldMapping?.[field] || field as FieldPath<T>
        const errorMessage = Array.isArray(messages) ? messages[0] : messages
        
        setError(fieldName, {
          type: 'server',
          message: errorMessage
        })
      })
      
      return true
    }
    
    // Se o erro tem estrutura de errors
    if (error?.errors) {
      Object.entries(error.errors).forEach(([field, messages]) => {
        const fieldName = fieldMapping?.[field] || field as FieldPath<T>
        const errorMessage = Array.isArray(messages) ? messages[0] : messages
        
        setError(fieldName, {
          type: 'server',
          message: errorMessage
        })
      })
      
      return true
    }
    
    // Se o erro tem uma mensagem geral
    if (error?.message) {
      // Mapear mensagens gerais para campos específicos baseado no conteúdo
      const message = error.message.toLowerCase()
      
      if (message.includes('nome') || message.includes('name')) {
        setError('name' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('email')) {
        setError('email' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('slug')) {
        setError('slug' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('descrição') || message.includes('description')) {
        setError('description' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('preço') || message.includes('price')) {
        setError('price' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('categoria') || message.includes('category')) {
        setError('category_id' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('estoque') || message.includes('stock')) {
        setError('qtd_stock' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('telefone') || message.includes('phone')) {
        setError('phone' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('endereço') || message.includes('address')) {
        setError('address' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('cidade') || message.includes('city')) {
        setError('city' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('estado') || message.includes('state')) {
        setError('state' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('cep') || message.includes('zip')) {
        setError('zip_code' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('bairro') || message.includes('neighborhood')) {
        setError('neighborhood' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('número') || message.includes('number')) {
        setError('number' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('complemento') || message.includes('complement')) {
        setError('complement' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('módulo') || message.includes('module')) {
        setError('module' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('ação') || message.includes('action')) {
        setError('action' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else if (message.includes('recurso') || message.includes('resource')) {
        setError('resource' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      } else {
        // Se não conseguir mapear para um campo específico, mostrar como erro geral
        setError('root' as FieldPath<T>, {
          type: 'server',
          message: error.message
        })
      }
      
      return true
    }
    
    return false
  }, [setError])

  return { handleBackendErrors }
}

/**
 * Mapeamento de campos comuns do backend para o frontend
 */
export const commonFieldMappings = {
  // Campos de produto
  'name': 'name',
  'description': 'description',
  'price': 'price',
  'price_cost': "price_cost",
  'qtd_stock': 'qtd_stock',
  'categories': 'categories',
  'image': 'image',
  
  // Campos de cliente
  'email': 'email',
  'phone': 'phone',
  'address': 'address',
  'city': 'city',
  'state': 'state',
  'zip_code': 'zip_code',
  'neighborhood': 'neighborhood',
  'number': 'number',
  'complement': 'complement',
  
  // Campos de permissão
  'slug': 'slug',
  'module': 'module',
  'action': 'action',
  'resource': 'resource',
  
  // Campos de mesa
  'identify': 'identify',
  'capacity': 'capacity',
  
  // Campos de categoria
  'url': 'url',
  'status': 'status',
}
