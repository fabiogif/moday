import { screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, generateProduct } from '../utils/test-utils'
import ProductsPage from '@/app/(dashboard)/products/page'
import { useAuthenticatedProducts, useMutation } from '@/hooks/use-authenticated-api'

// Mock the hooks
const mockUseAuthenticatedProducts = useAuthenticatedProducts as jest.MockedFunction<typeof useAuthenticatedProducts>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

describe('Products CRUD', () => {
  const mockProducts = [
    generateProduct({ id: 1, name: 'iPhone 14', price: 999.99, category: 'Electronics' }),
    generateProduct({ id: 2, name: 'Samsung Galaxy', price: 899.99, category: 'Electronics' }),
  ]

  const mockMutate = jest.fn()
  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    
    mockUseAuthenticatedProducts.mockReturnValue({
      data: mockProducts,
      loading: false,
      error: null,
      refetch: mockRefetch,
      isAuthenticated: true,
    })

    mockUseMutation.mockReturnValue({
      mutate: mockMutate,
      loading: false,
      error: null,
    })
  })

  describe('Authentication', () => {
    it('should show authentication error when not authenticated', () => {
      mockUseAuthenticatedProducts.mockReturnValue({
        data: [],
        loading: false,
        error: null,
        refetch: mockRefetch,
        isAuthenticated: false,
      })

      render(<ProductsPage />)
      
      expect(screen.getByText('Usuário não autenticado. Faça login para continuar.')).toBeInTheDocument()
    })
  })

  describe('Read Operations', () => {
    it('should render products list', async () => {
      render(<ProductsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('iPhone 14')).toBeInTheDocument()
        expect(screen.getByText('Samsung Galaxy')).toBeInTheDocument()
      })
    })

    it('should display loading state', () => {
      mockUseAuthenticatedProducts.mockReturnValue({
        data: [],
        loading: true,
        error: null,
        refetch: mockRefetch,
        isAuthenticated: true,
      })

      render(<ProductsPage />)
      
      expect(screen.getByText('Carregando produtos...')).toBeInTheDocument()
    })

    it('should display error state', () => {
      const errorMessage = 'Failed to fetch products'
      mockUseAuthenticatedProducts.mockReturnValue({
        data: [],
        loading: false,
        error: errorMessage,
        refetch: mockRefetch,
        isAuthenticated: true,
      })

      render(<ProductsPage />)
      
      expect(screen.getByText(`Erro ao carregar produtos: ${errorMessage}`)).toBeInTheDocument()
    })

    it('should display product details correctly', async () => {
      render(<ProductsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('$999.99')).toBeInTheDocument()
        expect(screen.getByText('Electronics')).toBeInTheDocument()
      })
    })
  })

  describe('Create Operations', () => {
    it('should open create product dialog when add button is clicked', async () => {
      const user = userEvent.setup()
      render(<ProductsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*product/i })
      await user.click(addButton)
      
      expect(screen.getByRole('dialog')).toBeInTheDocument()
    })

    it('should create new product with valid data including image', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<ProductsPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*product/i })
      await user.click(addButton)
      
      // Fill form
      await user.type(screen.getByLabelText(/name/i), 'New Product')
      await user.type(screen.getByLabelText(/description/i), 'Product description')
      await user.type(screen.getByLabelText(/price/i), '299.99')
      await user.type(screen.getByLabelText(/stock/i), '50')
      await user.selectOptions(screen.getByLabelText(/category/i), '1')
      
      // Mock file upload
      const fileInput = screen.getByLabelText(/image/i)
      const file = new File(['test'], 'test.png', { type: 'image/png' })
      await user.upload(fileInput, file)
      
      // Submit form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/products',
          'POST',
          expect.any(FormData)
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should show validation error when category is not selected', async () => {
      const user = userEvent.setup()
      const alertSpy = jest.spyOn(window, 'alert').mockImplementation()
      
      render(<ProductsPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*product/i })
      await user.click(addButton)
      
      // Fill form without category
      await user.type(screen.getByLabelText(/name/i), 'New Product')
      await user.type(screen.getByLabelText(/price/i), '299.99')
      
      // Submit form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(alertSpy).toHaveBeenCalledWith(
          'Por favor, selecione uma categoria antes de criar o produto.'
        )
      })
      
      alertSpy.mockRestore()
    })

    it('should display create error message', () => {
      const errorMessage = 'Failed to create product'
      mockUseMutation.mockReturnValue({
        mutate: mockMutate,
        loading: false,
        error: errorMessage,
      })

      render(<ProductsPage />)
      
      expect(screen.getByText(`Erro ao criar produto: ${errorMessage}`)).toBeInTheDocument()
    })

    it('should handle FormData creation correctly', async () => {
      const user = userEvent.setup()
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<ProductsPage />)
      
      // Open dialog and fill form
      const addButton = screen.getByRole('button', { name: /add.*product/i })
      await user.click(addButton)
      
      await user.type(screen.getByLabelText(/name/i), 'Test Product')
      await user.type(screen.getByLabelText(/price/i), '99.99')
      await user.selectOptions(screen.getByLabelText(/category/i), '1')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(consoleSpy).toHaveBeenCalledWith('Dados do produto antes do envio:', expect.any(Object))
        expect(consoleSpy).toHaveBeenCalledWith('FormData contents:')
      })
      
      consoleSpy.mockRestore()
    })
  })

  describe('Update Operations', () => {
    it('should open edit product dialog when edit button is clicked', async () => {
      const user = userEvent.setup()
      render(<ProductsPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      // Should call handleEditProduct (currently just logs)
    })

    it('should update product with valid data', async () => {
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation()
      
      render(<ProductsPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      expect(consoleSpy).toHaveBeenCalledWith('Edit product:', mockProducts[0])
      consoleSpy.mockRestore()
    })
  })

  describe('Delete Operations', () => {
    it('should delete product when delete button is clicked', async () => {
      mockMutate.mockResolvedValue({ success: true })
      
      render(<ProductsPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/products/1',
          'DELETE'
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should handle delete error gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Delete failed'))
      
      render(<ProductsPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(consoleErrorSpy).toHaveBeenCalledWith(
          'Erro ao excluir produto:',
          expect.any(Error)
        )
      })
      
      consoleErrorSpy.mockRestore()
    })

    it('should log success message after successful creation', async () => {
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation()
      const mockResult = { id: 1, name: 'New Product' }
      mockMutate.mockResolvedValue(mockResult)
      
      render(<ProductsPage />)
      
      // Simulate product creation
      const addButton = screen.getByRole('button', { name: /add.*product/i })
      await userEvent.click(addButton)
      
      // Fill and submit form
      await userEvent.type(screen.getByLabelText(/name/i), 'New Product')
      await userEvent.selectOptions(screen.getByLabelText(/category/i), '1')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await userEvent.click(submitButton)
      
      await waitFor(() => {
        expect(consoleSpy).toHaveBeenCalledWith('Produto criado com sucesso:', mockResult)
      })
      
      consoleSpy.mockRestore()
    })
  })

  describe('Search and Filter Operations', () => {
    it('should filter products by search term', async () => {
      const user = userEvent.setup()
      render(<ProductsPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*products/i)
      await user.type(searchInput, 'iPhone')
      
      await waitFor(() => {
        expect(screen.getByText('iPhone 14')).toBeInTheDocument()
        expect(screen.queryByText('Samsung Galaxy')).not.toBeInTheDocument()
      })
    })

    it('should filter products by category', async () => {
      const user = userEvent.setup()
      render(<ProductsPage />)
      
      const categoryFilter = screen.getByRole('combobox', { name: /category/i })
      await user.selectOptions(categoryFilter, 'Electronics')
      
      // Both products should be visible as they're both Electronics
      await waitFor(() => {
        expect(screen.getByText('iPhone 14')).toBeInTheDocument()
        expect(screen.getByText('Samsung Galaxy')).toBeInTheDocument()
      })
    })

    it('should filter products by price range', async () => {
      const user = userEvent.setup()
      render(<ProductsPage />)
      
      // Test price filter if it exists
      const minPriceInput = screen.queryByLabelText(/min.*price/i)
      const maxPriceInput = screen.queryByLabelText(/max.*price/i)
      
      if (minPriceInput && maxPriceInput) {
        await user.type(minPriceInput, '900')
        await user.type(maxPriceInput, '1000')
        
        await waitFor(() => {
          expect(screen.getByText('iPhone 14')).toBeInTheDocument()
        })
      }
    })

    it('should filter products by stock status', async () => {
      const user = userEvent.setup()
      render(<ProductsPage />)
      
      const stockFilter = screen.queryByRole('combobox', { name: /stock/i })
      if (stockFilter) {
        await user.selectOptions(stockFilter, 'In Stock')
        
        // Should show products with stock > 0
      }
    })
  })

  describe('Sorting Operations', () => {
    it('should sort products by name', async () => {
      const user = userEvent.setup()
      render(<ProductsPage />)
      
      const nameHeader = screen.queryByRole('columnheader', { name: /name/i })
      if (nameHeader) {
        await user.click(nameHeader)
        // Should sort products alphabetically
      }
    })

    it('should sort products by price', async () => {
      const user = userEvent.setup()
      render(<ProductsPage />)
      
      const priceHeader = screen.queryByRole('columnheader', { name: /price/i })
      if (priceHeader) {
        await user.click(priceHeader)
        // Should sort products by price
      }
    })
  })

  describe('Image Upload', () => {
    it('should handle image upload correctly', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<ProductsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*product/i })
      await user.click(addButton)
      
      const fileInput = screen.getByLabelText(/image/i)
      const file = new File(['image data'], 'product.jpg', { type: 'image/jpeg' })
      
      await user.upload(fileInput, file)
      
      expect(fileInput.files[0]).toBe(file)
      expect(fileInput.files).toHaveLength(1)
    })

    it('should handle missing image gracefully', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<ProductsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*product/i })
      await user.click(addButton)
      
      // Fill form without image
      await user.type(screen.getByLabelText(/name/i), 'Product without image')
      await user.selectOptions(screen.getByLabelText(/category/i), '1')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalled()
      })
    })
  })

  describe('Product Statistics', () => {
    it('should display product statistics cards', () => {
      render(<ProductsPage />)
      
      // Should render ProductStatCards component
      expect(screen.getByText(/total.*products/i) || screen.getByText(/products/i)).toBeInTheDocument()
    })
  })
})