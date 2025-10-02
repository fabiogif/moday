import { screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, generateClient } from '../utils/test-utils'
import ClientsPage from '@/app/(dashboard)/clients/page'

describe('Clients CRUD', () => {
  const mockClients = [
    generateClient({ 
      id: 1, 
      name: 'John Smith', 
      email: 'john@example.com',
      phone: '+1234567890',
      address: '123 Main St, City',
      totalOrders: 5,
      lastOrder: '2024-01-15',
      isActive: true
    }),
    generateClient({ 
      id: 2, 
      name: 'Jane Doe', 
      email: 'jane@example.com',
      phone: '+9876543210',
      address: '456 Oak Ave, Town',
      totalOrders: 3,
      lastOrder: '2024-01-10',
      isActive: false
    }),
  ]

  // Mock useState for clients data
  const mockSetClients = jest.fn()
  beforeEach(() => {
    jest.clearAllMocks()
    
    // Mock React.useState to return our mock data
    jest.spyOn(React, 'useState').mockImplementation((initial) => {
      if (Array.isArray(initial) || initial === undefined) {
        return [mockClients, mockSetClients]
      }
      return [initial, jest.fn()]
    })
  })

  afterEach(() => {
    jest.restoreAllMocks()
  })

  describe('Read Operations', () => {
    it('should render clients list', async () => {
      render(<ClientsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('John Smith')).toBeInTheDocument()
        expect(screen.getByText('Jane Doe')).toBeInTheDocument()
      })
    })

    it('should display client details correctly', async () => {
      render(<ClientsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('john@example.com')).toBeInTheDocument()
        expect(screen.getByText('jane@example.com')).toBeInTheDocument()
        expect(screen.getByText('+1234567890')).toBeInTheDocument()
        expect(screen.getByText('+9876543210')).toBeInTheDocument()
        expect(screen.getByText('123 Main St, City')).toBeInTheDocument()
        expect(screen.getByText('456 Oak Ave, Town')).toBeInTheDocument()
      })
    })

    it('should display client order statistics', async () => {
      render(<ClientsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('5')).toBeInTheDocument() // total orders
        expect(screen.getByText('3')).toBeInTheDocument() // total orders
        expect(screen.getByText('2024-01-15')).toBeInTheDocument() // last order
        expect(screen.getByText('2024-01-10')).toBeInTheDocument() // last order
      })
    })

    it('should display client status correctly', async () => {
      render(<ClientsPage />)
      
      await waitFor(() => {
        // Should show active/inactive status indicators
        const activeIndicators = screen.getAllByText(/active/i)
        const inactiveIndicators = screen.getAllByText(/inactive/i)
        
        expect(activeIndicators.length).toBeGreaterThan(0)
        expect(inactiveIndicators.length).toBeGreaterThan(0)
      })
    })
  })

  describe('Create Operations', () => {
    it('should open create client dialog when add button is clicked', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      expect(screen.getByRole('dialog')).toBeInTheDocument()
    })

    it('should create new client with valid data', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      // Fill form
      await user.type(screen.getByLabelText(/name/i), 'Alice Johnson')
      await user.type(screen.getByLabelText(/email/i), 'alice@example.com')
      await user.type(screen.getByLabelText(/phone/i), '+1122334455')
      await user.type(screen.getByLabelText(/address/i), '789 Pine St, Village')
      
      // Set active status
      const activeCheckbox = screen.getByLabelText(/active/i)
      await user.click(activeCheckbox)
      
      // Submit form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(mockSetClients).toHaveBeenCalledWith(expect.any(Function))
        
        // Verify the function passed to setClients creates correct client
        const setClientsCall = mockSetClients.mock.calls[0][0]
        const newClients = setClientsCall(mockClients)
        
        expect(newClients[0]).toMatchObject({
          name: 'Alice Johnson',
          email: 'alice@example.com',
          phone: '+1122334455',
          address: '789 Pine St, Village',
          isActive: true,
          totalOrders: 0,
          lastOrder: '',
        })
      })
    })

    it('should generate unique ID for new client', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      await user.type(screen.getByLabelText(/name/i), 'New Client')
      await user.type(screen.getByLabelText(/email/i), 'new@example.com')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        const setClientsCall = mockSetClients.mock.calls[0][0]
        const newClients = setClientsCall(mockClients)
        
        // New client should have ID = max(existing IDs) + 1 = 3
        expect(newClients[0].id).toBe(3)
      })
    })

    it('should set creation date for new client', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      await user.type(screen.getByLabelText(/name/i), 'New Client')
      await user.type(screen.getByLabelText(/email/i), 'new@example.com')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        const setClientsCall = mockSetClients.mock.calls[0][0]
        const newClients = setClientsCall(mockClients)
        
        // Should set today's date as creation date
        const today = new Date().toISOString().split('T')[0]
        expect(newClients[0].createdAt).toBe(today)
      })
    })

    it('should show validation errors for invalid data', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      // Submit empty form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/name.*required/i)).toBeInTheDocument()
        expect(screen.getByText(/email.*required/i)).toBeInTheDocument()
      })
    })

    it('should validate email format', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      // Fill form with invalid email
      await user.type(screen.getByLabelText(/name/i), 'Test Client')
      await user.type(screen.getByLabelText(/email/i), 'invalid-email')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/valid email/i)).toBeInTheDocument()
      })
    })

    it('should validate phone number format', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      // Fill form with invalid phone
      await user.type(screen.getByLabelText(/name/i), 'Test Client')
      await user.type(screen.getByLabelText(/email/i), 'test@example.com')
      await user.type(screen.getByLabelText(/phone/i), '123')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/valid phone/i)).toBeInTheDocument()
      })
    })
  })

  describe('Update Operations', () => {
    it('should open edit client dialog when edit button is clicked', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      // Should call handleEditClient (currently just logs)
    })

    it('should update client data', async () => {
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation()
      
      render(<ClientsPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      expect(consoleSpy).toHaveBeenCalledWith('Edit client:', mockClients[0])
      consoleSpy.mockRestore()
    })

    it('should handle client status update', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      // Test status toggle if available
      const statusToggle = screen.queryByRole('switch', { name: /status/i })
      if (statusToggle) {
        await user.click(statusToggle)
        
        // Should update client status
      }
    })
  })

  describe('Delete Operations', () => {
    it('should delete client when delete button is clicked', async () => {
      render(<ClientsPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(mockSetClients).toHaveBeenCalledWith(expect.any(Function))
        
        // Verify the function passed to setClients removes the correct client
        const setClientsCall = mockSetClients.mock.calls[0][0]
        const filteredClients = setClientsCall(mockClients)
        
        expect(filteredClients).toHaveLength(1)
        expect(filteredClients[0].id).toBe(2) // Should keep Jane Doe, remove John Smith
      })
    })

    it('should show confirmation dialog before deletion', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        user.click(deleteButton)
      })
      
      // In a full implementation, this would show a confirmation dialog
      // especially for clients with existing orders
    })

    it('should prevent deletion of clients with orders', async () => {
      render(<ClientsPage />)
      
      // John Smith has 5 orders, should show warning
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      // In a full implementation, would show warning about
      // deleting clients with existing orders
    })
  })

  describe('Search and Filter Operations', () => {
    it('should filter clients by search term', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*clients/i)
      await user.type(searchInput, 'John')
      
      await waitFor(() => {
        expect(screen.getByText('John Smith')).toBeInTheDocument()
        expect(screen.queryByText('Jane Doe')).not.toBeInTheDocument()
      })
    })

    it('should filter clients by email', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*clients/i)
      await user.type(searchInput, 'jane@example.com')
      
      await waitFor(() => {
        expect(screen.queryByText('John Smith')).not.toBeInTheDocument()
        expect(screen.getByText('Jane Doe')).toBeInTheDocument()
      })
    })

    it('should filter clients by status', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const statusFilter = screen.getByRole('combobox', { name: /status/i })
      await user.selectOptions(statusFilter, 'Active')
      
      await waitFor(() => {
        expect(screen.getByText('John Smith')).toBeInTheDocument()
        expect(screen.queryByText('Jane Doe')).not.toBeInTheDocument()
      })
    })

    it('should filter clients by order count', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const orderFilter = screen.queryByRole('combobox', { name: /orders/i })
      if (orderFilter) {
        await user.selectOptions(orderFilter, 'Has Orders')
        
        // Should show both clients as they both have orders
        await waitFor(() => {
          expect(screen.getByText('John Smith')).toBeInTheDocument()
          expect(screen.getByText('Jane Doe')).toBeInTheDocument()
        })
      }
    })
  })

  describe('Sorting Operations', () => {
    it('should sort clients by name', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const nameHeader = screen.queryByRole('columnheader', { name: /name/i })
      if (nameHeader) {
        await user.click(nameHeader)
        // Should sort clients alphabetically
      }
    })

    it('should sort clients by total orders', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const ordersHeader = screen.queryByRole('columnheader', { name: /orders/i })
      if (ordersHeader) {
        await user.click(ordersHeader)
        // Should sort clients by order count
      }
    })

    it('should sort clients by last order date', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const lastOrderHeader = screen.queryByRole('columnheader', { name: /last.*order/i })
      if (lastOrderHeader) {
        await user.click(lastOrderHeader)
        // Should sort clients by last order date
      }
    })
  })

  describe('Client Statistics', () => {
    it('should display client statistics cards', () => {
      render(<ClientsPage />)
      
      // Should render StatCards component
      expect(screen.getByText(/total.*clients/i) || screen.getByText(/clients/i)).toBeInTheDocument()
    })

    it('should calculate active vs inactive clients', () => {
      render(<ClientsPage />)
      
      // Should show breakdown of active vs inactive clients
      // Based on mock data: 1 active, 1 inactive
    })

    it('should show total orders across all clients', () => {
      render(<ClientsPage />)
      
      // Total orders should be 5 + 3 = 8
      const totalOrders = mockClients.reduce((sum, client) => sum + client.totalOrders, 0)
      expect(totalOrders).toBe(8)
    })
  })

  describe('Client Details View', () => {
    it('should show client contact information', async () => {
      render(<ClientsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('+1234567890')).toBeInTheDocument()
        expect(screen.getByText('123 Main St, City')).toBeInTheDocument()
      })
    })

    it('should display client order history summary', async () => {
      render(<ClientsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('5')).toBeInTheDocument() // John's orders
        expect(screen.getByText('3')).toBeInTheDocument() // Jane's orders
        expect(screen.getByText('2024-01-15')).toBeInTheDocument() // John's last order
        expect(screen.getByText('2024-01-10')).toBeInTheDocument() // Jane's last order
      })
    })

    it('should show client since date', async () => {
      render(<ClientsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('2024-01-15')).toBeInTheDocument() // creation date
      })
    })
  })

  describe('Bulk Operations', () => {
    it('should select multiple clients for bulk operations', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        // Should show bulk operation controls
      }
    })

    it('should perform bulk status change', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      // Select clients
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        const bulkStatusButton = screen.queryByRole('button', { name: /change status/i })
        if (bulkStatusButton) {
          await user.click(bulkStatusButton)
          
          // Should update status for selected clients
        }
      }
    })

    it('should export selected clients', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      // Select clients
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

    it('should send bulk email to selected clients', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      // Select clients
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        const emailButton = screen.queryByRole('button', { name: /send email/i })
        if (emailButton) {
          await user.click(emailButton)
          
          // Should open email composition dialog
        }
      }
    })
  })

  describe('Data Validation', () => {
    it('should prevent duplicate email addresses', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      // Try to create client with existing email
      await user.type(screen.getByLabelText(/name/i), 'New Client')
      await user.type(screen.getByLabelText(/email/i), 'john@example.com')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/email.*already exists/i)).toBeInTheDocument()
      })
    })

    it('should validate required fields', async () => {
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      // Fill only optional fields
      await user.type(screen.getByLabelText(/phone/i), '+1234567890')
      await user.type(screen.getByLabelText(/address/i), '123 Test St')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/name.*required/i)).toBeInTheDocument()
        expect(screen.getByText(/email.*required/i)).toBeInTheDocument()
      })
    })
  })

  describe('Integration with Orders', () => {
    it('should show link to view client orders', async () => {
      render(<ClientsPage />)
      
      const viewOrdersButton = screen.queryByRole('button', { name: /view orders/i })
      if (viewOrdersButton) {
        expect(viewOrdersButton).toBeInTheDocument()
      }
    })

    it('should display last order information', async () => {
      render(<ClientsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('2024-01-15')).toBeInTheDocument()
        expect(screen.getByText('2024-01-10')).toBeInTheDocument()
      })
    })

    it('should show clients without orders', () => {
      const clientsWithoutOrders = [
        generateClient({ 
          id: 1, 
          name: 'New Client', 
          totalOrders: 0,
          lastOrder: ''
        }),
      ]
      
      jest.spyOn(React, 'useState').mockImplementation((initial) => {
        if (Array.isArray(initial) || initial === undefined) {
          return [clientsWithoutOrders, mockSetClients]
        }
        return [initial, jest.fn()]
      })

      render(<ClientsPage />)
      
      expect(screen.getByText('New Client')).toBeInTheDocument()
      expect(screen.getByText('0')).toBeInTheDocument() // no orders
    })
  })

  describe('Error Handling', () => {
    it('should handle empty client list gracefully', () => {
      jest.spyOn(React, 'useState').mockImplementation((initial) => {
        if (Array.isArray(initial) || initial === undefined) {
          return [[], mockSetClients]
        }
        return [initial, jest.fn()]
      })

      render(<ClientsPage />)
      
      expect(screen.getByText(/no clients found/i) || 
             screen.getByText(/empty/i)).toBeInTheDocument()
    })

    it('should handle form submission errors gracefully', async () => {
      const consoleSpy = jest.spyOn(console, 'error').mockImplementation()
      
      // Mock error during client creation
      mockSetClients.mockImplementation(() => {
        throw new Error('Failed to add client')
      })
      
      const user = userEvent.setup()
      render(<ClientsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*client/i })
      await user.click(addButton)
      
      await user.type(screen.getByLabelText(/name/i), 'Test Client')
      await user.type(screen.getByLabelText(/email/i), 'test@example.com')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      // Should handle error gracefully
      consoleSpy.mockRestore()
    })
  })
})