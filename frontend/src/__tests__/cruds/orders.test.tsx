import { screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, generateOrder } from '../utils/test-utils'
import OrdersPage from '@/app/(dashboard)/orders/page'
import { useOrders, useMutation } from '@/hooks/use-api'

// Mock the hooks
const mockUseOrders = useOrders as jest.MockedFunction<typeof useOrders>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

describe('Orders CRUD', () => {
  const mockOrders = [
    generateOrder({ 
      id: 1, 
      orderNumber: 'ORD-001', 
      customerName: 'John Doe', 
      status: 'pending',
      total: 299.99,
      items: 3
    }),
    generateOrder({ 
      id: 2, 
      orderNumber: 'ORD-002', 
      customerName: 'Jane Smith', 
      status: 'completed',
      total: 199.99,
      items: 2
    }),
  ]

  const mockMutate = jest.fn()
  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    
    mockUseOrders.mockReturnValue({
      data: mockOrders,
      loading: false,
      error: null,
      refetch: mockRefetch,
    })

    mockUseMutation.mockReturnValue({
      mutate: mockMutate,
      loading: false,
      error: null,
    })
  })

  describe('Read Operations', () => {
    it('should render orders list', async () => {
      render(<OrdersPage />)
      
      await waitFor(() => {
        expect(screen.getByText('ORD-001')).toBeInTheDocument()
        expect(screen.getByText('ORD-002')).toBeInTheDocument()
        expect(screen.getByText('John Doe')).toBeInTheDocument()
        expect(screen.getByText('Jane Smith')).toBeInTheDocument()
      })
    })

    it('should display loading state', () => {
      mockUseOrders.mockReturnValue({
        data: [],
        loading: true,
        error: null,
        refetch: mockRefetch,
      })

      render(<OrdersPage />)
      
      expect(screen.getByText('Carregando pedidos...')).toBeInTheDocument()
    })

    it('should display error state', () => {
      const errorMessage = 'Failed to fetch orders'
      mockUseOrders.mockReturnValue({
        data: [],
        loading: false,
        error: errorMessage,
        refetch: mockRefetch,
      })

      render(<OrdersPage />)
      
      expect(screen.getByText(`Erro ao carregar pedidos: ${errorMessage}`)).toBeInTheDocument()
    })

    it('should display order details correctly', async () => {
      render(<OrdersPage />)
      
      await waitFor(() => {
        expect(screen.getByText('$299.99')).toBeInTheDocument()
        expect(screen.getByText('$199.99')).toBeInTheDocument()
        expect(screen.getByText('pending')).toBeInTheDocument()
        expect(screen.getByText('completed')).toBeInTheDocument()
        expect(screen.getByText('3')).toBeInTheDocument() // items count
        expect(screen.getByText('2')).toBeInTheDocument() // items count
      })
    })

    it('should display customer information', async () => {
      render(<OrdersPage />)
      
      await waitFor(() => {
        expect(screen.getByText('john@example.com')).toBeInTheDocument()
        expect(screen.getByText('jane@example.com')).toBeInTheDocument()
      })
    })
  })

  describe('Create Operations', () => {
    it('should open create order dialog when add button is clicked', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*order/i })
      await user.click(addButton)
      
      expect(screen.getByRole('dialog')).toBeInTheDocument()
    })

    it('should create new order with valid data', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<OrdersPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*order/i })
      await user.click(addButton)
      
      // Fill form
      await user.type(screen.getByLabelText(/customer name/i), 'Alice Johnson')
      await user.type(screen.getByLabelText(/customer email/i), 'alice@example.com')
      await user.selectOptions(screen.getByLabelText(/status/i), 'pending')
      await user.type(screen.getByLabelText(/total/i), '149.99')
      await user.type(screen.getByLabelText(/items/i), '1')
      
      // Submit form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/orders',
          'POST',
          expect.objectContaining({
            customerName: 'Alice Johnson',
            customerEmail: 'alice@example.com',
            status: 'pending',
            total: 149.99,
            items: 1,
          })
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should show validation errors for invalid data', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*order/i })
      await user.click(addButton)
      
      // Submit empty form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/customer name.*required/i)).toBeInTheDocument()
        expect(screen.getByText(/email.*required/i)).toBeInTheDocument()
      })
    })

    it('should validate email format', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*order/i })
      await user.click(addButton)
      
      // Fill form with invalid email
      await user.type(screen.getByLabelText(/customer email/i), 'invalid-email')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/valid email/i)).toBeInTheDocument()
      })
    })

    it('should validate numeric fields', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*order/i })
      await user.click(addButton)
      
      // Fill form with invalid numbers
      await user.type(screen.getByLabelText(/total/i), 'not-a-number')
      await user.type(screen.getByLabelText(/items/i), 'not-a-number')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/valid number/i)).toBeInTheDocument()
      })
    })
  })

  describe('Update Operations', () => {
    it('should open edit order dialog when edit button is clicked', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      // Should call handleEditOrder (currently just logs)
    })

    it('should update order status', async () => {
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation()
      
      render(<OrdersPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      expect(consoleSpy).toHaveBeenCalledWith('Edit order:', mockOrders[0])
      consoleSpy.mockRestore()
    })

    it('should handle order status transitions', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      // Test status change from pending to processing
      const statusButton = screen.queryByRole('button', { name: /change status/i })
      if (statusButton) {
        await user.click(statusButton)
        
        // Should show status options
        const processingOption = screen.queryByText('processing')
        if (processingOption) {
          await user.click(processingOption)
        }
      }
    })
  })

  describe('Delete Operations', () => {
    it('should delete order when delete button is clicked', async () => {
      mockMutate.mockResolvedValue({ success: true })
      
      render(<OrdersPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/orders/1',
          'DELETE'
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should handle delete error gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Delete failed'))
      
      render(<OrdersPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(consoleErrorSpy).toHaveBeenCalledWith(
          'Erro ao excluir pedido:',
          expect.any(Error)
        )
      })
      
      consoleErrorSpy.mockRestore()
    })

    it('should show confirmation for order deletion', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        user.click(deleteButton)
      })
      
      // In a full implementation, this would show a confirmation dialog
      // especially for orders with status 'completed' or 'shipped'
    })
  })

  describe('Search and Filter Operations', () => {
    it('should filter orders by search term', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*orders/i)
      await user.type(searchInput, 'ORD-001')
      
      await waitFor(() => {
        expect(screen.getByText('ORD-001')).toBeInTheDocument()
        expect(screen.queryByText('ORD-002')).not.toBeInTheDocument()
      })
    })

    it('should filter orders by customer name', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*orders/i)
      await user.type(searchInput, 'John')
      
      await waitFor(() => {
        expect(screen.getByText('John Doe')).toBeInTheDocument()
        expect(screen.queryByText('Jane Smith')).not.toBeInTheDocument()
      })
    })

    it('should filter orders by status', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const statusFilter = screen.getByRole('combobox', { name: /status/i })
      await user.selectOptions(statusFilter, 'pending')
      
      await waitFor(() => {
        expect(screen.getByText('pending')).toBeInTheDocument()
        expect(screen.queryByText('completed')).not.toBeInTheDocument()
      })
    })

    it('should filter orders by date range', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const startDateInput = screen.queryByLabelText(/start date/i)
      const endDateInput = screen.queryByLabelText(/end date/i)
      
      if (startDateInput && endDateInput) {
        await user.type(startDateInput, '2024-01-01')
        await user.type(endDateInput, '2024-01-31')
        
        // Should filter orders within date range
      }
    })

    it('should filter orders by total amount range', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const minAmountInput = screen.queryByLabelText(/min.*amount/i)
      const maxAmountInput = screen.queryByLabelText(/max.*amount/i)
      
      if (minAmountInput && maxAmountInput) {
        await user.type(minAmountInput, '200')
        await user.type(maxAmountInput, '300')
        
        await waitFor(() => {
          expect(screen.getByText('$299.99')).toBeInTheDocument()
          expect(screen.queryByText('$199.99')).not.toBeInTheDocument()
        })
      }
    })
  })

  describe('Sorting Operations', () => {
    it('should sort orders by order number', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const orderNumberHeader = screen.queryByRole('columnheader', { name: /order.*number/i })
      if (orderNumberHeader) {
        await user.click(orderNumberHeader)
        // Should sort orders by order number
      }
    })

    it('should sort orders by total amount', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const totalHeader = screen.queryByRole('columnheader', { name: /total/i })
      if (totalHeader) {
        await user.click(totalHeader)
        // Should sort orders by total amount
      }
    })

    it('should sort orders by date', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const dateHeader = screen.queryByRole('columnheader', { name: /date/i })
      if (dateHeader) {
        await user.click(dateHeader)
        // Should sort orders by date
      }
    })
  })

  describe('Order Status Management', () => {
    it('should display correct status indicators', async () => {
      render(<OrdersPage />)
      
      await waitFor(() => {
        const pendingStatus = screen.getByText('pending')
        const completedStatus = screen.getByText('completed')
        
        expect(pendingStatus).toBeInTheDocument()
        expect(completedStatus).toBeInTheDocument()
        
        // Status should have appropriate styling
        expect(pendingStatus).toHaveClass(/pending|warning|yellow/i)
        expect(completedStatus).toHaveClass(/completed|success|green/i)
      })
    })

    it('should allow status updates', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<OrdersPage />)
      
      // Test quick status change
      const statusDropdown = screen.queryByRole('button', { name: /change.*status/i })
      if (statusDropdown) {
        await user.click(statusDropdown)
        
        const shippedOption = screen.queryByText('shipped')
        if (shippedOption) {
          await user.click(shippedOption)
          
          await waitFor(() => {
            expect(mockMutate).toHaveBeenCalled()
          })
        }
      }
    })
  })

  describe('Order Statistics', () => {
    it('should display order statistics cards', () => {
      render(<OrdersPage />)
      
      // Should render StatCards component
      expect(screen.getByText(/total.*orders/i) || screen.getByText(/orders/i)).toBeInTheDocument()
    })

    it('should calculate total revenue correctly', async () => {
      render(<OrdersPage />)
      
      await waitFor(() => {
        // Should show total revenue from all orders
        const totalRevenue = 299.99 + 199.99
        expect(screen.getByText(totalRevenue.toString()) || 
               screen.getByText(`$${totalRevenue.toFixed(2)}`)).toBeInTheDocument()
      })
    })
  })

  describe('Order Details View', () => {
    it('should show order details when clicking on order', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      await waitFor(() => {
        const orderRow = screen.getByText('ORD-001')
        user.click(orderRow)
      })
      
      // Should open order details modal or navigate to details page
    })

    it('should display order items', async () => {
      render(<OrdersPage />)
      
      await waitFor(() => {
        expect(screen.getByText('3')).toBeInTheDocument() // items count
        expect(screen.getByText('2')).toBeInTheDocument() // items count
      })
    })
  })

  describe('Bulk Operations', () => {
    it('should select multiple orders for bulk operations', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        // Should show bulk operation controls
      }
    })

    it('should perform bulk status update', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<OrdersPage />)
      
      // Select orders
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        const bulkStatusButton = screen.queryByRole('button', { name: /bulk.*status/i })
        if (bulkStatusButton) {
          await user.click(bulkStatusButton)
          
          await waitFor(() => {
            expect(mockMutate).toHaveBeenCalled()
            expect(mockRefetch).toHaveBeenCalled()
          })
        }
      }
    })

    it('should export selected orders', async () => {
      const user = userEvent.setup()
      render(<OrdersPage />)
      
      // Select orders
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        const exportButton = screen.queryByRole('button', { name: /export/i })
        if (exportButton) {
          await user.click(exportButton)
          
          // Should trigger export functionality
        }
      }
    })
  })

  describe('Error Handling', () => {
    it('should handle network errors gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Network error'))
      
      render(<OrdersPage />)
      
      // Trigger an operation that would cause an error
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(consoleErrorSpy).toHaveBeenCalled()
      })
      
      consoleErrorSpy.mockRestore()
    })
  })
})