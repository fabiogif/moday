"use client"

import { useState } from "react"
import { DataTable } from "./components/data-table"
import { ProductStatCards } from "./components/product-stat-cards"
import { SuccessAlert } from "./components/success-alert"
import { useAuthenticatedProducts, useMutation, useMutationWithValidation } from "@/hooks/use-authenticated-api"
import { commonFieldMappings } from "@/hooks/use-backend-validation"
import { endpoints } from "@/lib/api-client"
import { PageLoading } from "@/components/ui/loading-progress"

interface Product {
  id: number
  name: string
  description: string
  price: number
  categories: Array<{
    identify: string
    name: string
  }>
  price_cost: number
  is_active: boolean
  created_at: string
  createdAt: string
  url?: string
  qtd_stock?: number
}

interface ProductFormValues {
  name: string
  description: string
  price: number
  price_cost: number
  categories: string[]
  qtd_stock: number
  image?: File
}

export default function ProductsPage() {
  const { data: products, loading, error, refetch, isAuthenticated } = useAuthenticatedProducts()
  const { mutate: createProduct, loading: creating, error: createError } = useMutation()
  const { mutate: deleteProduct, loading: deleting } = useMutation()

  // Estados para o alert de sucesso
  const [successAlert, setSuccessAlert] = useState({
    open: false,
    title: "",
    message: "",
  })

  const handleShowSuccessAlert = (title: string, message: string) => {
    setSuccessAlert({
      open: true,
      title,
      message,
    })
  }

  const handleAddProduct = async (productData: ProductFormValues) => {
    try {
      console.log('Dados do produto antes do envio:', productData)
      
      // Validar se categories está definido
      if (!productData.categories || productData.categories.length === 0) {
        console.error('categories está undefined ou vazio:', productData.categories)
        handleShowSuccessAlert('Atenção!', 'Por favor, selecione uma categoria antes de criar o produto.')
        return
      }
      
      // Criar FormData para enviar arquivo de imagem
      const formData = new FormData()
      formData.append('name', productData.name)
      formData.append('description', productData.description)
      formData.append('price', productData.price.toString())
      formData.append('price_cost', productData.price_cost?.toString() || '0')
      formData.append('qtd_stock', productData.qtd_stock.toString())
      
      // Enviar cada categoria individualmente para o Laravel processar como array
      productData.categories.forEach((categoryId, index) => {
        formData.append(`categories[${index}]`, categoryId)
      })
      
      // Só adicionar imagem se ela existir
      if (productData.image && productData.image instanceof File) {
        formData.append('image', productData.image)
      }
      
      const result = await createProduct(
        endpoints.products.create,
        'POST',
        formData
      )
      
      if (result) {
        console.log('Produto criado com sucesso:', result)
        // ✅ Atualizar grid automaticamente sem refresh
        await refetch()
        handleShowSuccessAlert('Sucesso!', 'Produto criado com sucesso!')
      }
    } catch (error: any) {
      console.error('Erro ao criar produto:', error)
      
      // Se o erro tem dados de validação, mostrar para o usuário
      if (error?.data?.data) {
        const validationErrors = Object.entries(error.data.data)
          .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
          .join('; ')
        handleShowSuccessAlert('Erro de Validação!', `Erro de validação: ${validationErrors}`)
      } else {
        handleShowSuccessAlert('Erro!', error.message || 'Erro ao criar produto')
      }
    }
  }

  const handleDeleteProduct = async (id: number) => {
    try {
      const result = await deleteProduct(
        endpoints.products.delete(id.toString()),
        'DELETE'
      )
      
      if (result) {
        // ✅ Atualizar grid automaticamente sem refresh
        await refetch()
        handleShowSuccessAlert('Sucesso!', 'Produto excluído com sucesso!')
      }
    } catch (error) {
      console.error('Erro ao excluir produto:', error)
      handleShowSuccessAlert('Erro!', 'Erro ao excluir produto')
    }
  }

  const handleEditProduct = async (product: Product) => {
    try {
      console.log("Edit product:", product)
      
      const formData = new FormData()
      formData.append('name', product.name)
      formData.append('description', product.description)
      formData.append('price', product.price.toString())
      formData.append('price_cost', product.price_cost?.toString() || '0')
      formData.append('qtd_stock', product.qtd_stock?.toString() || '0')
      
      // Categorias
      product.categories?.forEach((category, index) => {
        formData.append(`categories[${index}]`, category.identify)
      })
      
      const result = await createProduct(
        endpoints.products.update(product.id),
        'PUT',
        formData
      )
      
      if (result) {
        // ✅ Atualizar grid automaticamente sem refresh
        await refetch()
        handleShowSuccessAlert('Sucesso!', 'Produto alterado com sucesso!')
      }
    } catch (error) {
      console.error('Erro ao editar produto:', error)
      handleShowSuccessAlert('Erro!', 'Erro ao editar produto')
    }
  }

  if (!isAuthenticated) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Usuário não autenticado. Faça login para continuar.</div>
      </div>
    )
  }

  if (loading) {
    return (
      <PageLoading 
        isLoading={loading}
        message="Carregando produtos..."
      />
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar produtos: {error}</div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="@container/main px-4 lg:px-6">
        <ProductStatCards />
      </div>
      
      {createError && (
        <div className="@container/main px-4 lg:px-6">
          <div className="p-4 border border-destructive/20 bg-destructive/5 rounded-md">
            <div className="text-destructive text-sm">
              Erro ao criar produto: {createError}
            </div>
          </div>
        </div>
      )}
      
      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <DataTable 
          products={Array.isArray(products) ? products : []}
          onDeleteProduct={handleDeleteProduct}
          onEditProduct={handleEditProduct}
          onAddProduct={handleAddProduct}
        />
      </div>

      {/* Success Alert */}
      <SuccessAlert
        open={successAlert.open}
        onOpenChange={(open: boolean) => setSuccessAlert(prev => ({ ...prev, open }))}
        title={successAlert.title}
        message={successAlert.message}
      />
    </div>
  )
}
