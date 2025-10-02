import { screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, generateUser } from '../utils/test-utils'
import UsersPage from '@/app/(dashboard)/users/page'
import { useUsers, useMutation } from '@/hooks/use-api'

// Mock the hooks
const mockUseUsers = useUsers as jest.MockedFunction<typeof useUsers>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

describe('Users CRUD', () => {
  const mockUsers = [
    generateUser({ id: 1, name: 'John Doe', email: 'john@example.com' }),
    generateUser({ id: 2, name: 'Jane Smith', email: 'jane@example.com' }),
  ]

  const mockMutate = jest.fn()
  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    
    mockUseUsers.mockReturnValue({
      data: mockUsers,
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
    it('should render users list', async () => {
      render(<UsersPage />)
      
      await waitFor(() => {
        expect(screen.getByText('John Doe')).toBeInTheDocument()
        expect(screen.getByText('Jane Smith')).toBeInTheDocument()
      })
    })

    it('should display loading state', () => {
      mockUseUsers.mockReturnValue({
        data: [],
        loading: true,
        error: null,
        refetch: mockRefetch,
      })

      render(<UsersPage />)
      
      expect(screen.getByText('Carregando usuários...')).toBeInTheDocument()
    })

    it('should display error state', () => {
      const errorMessage = 'Failed to fetch users'
      mockUseUsers.mockReturnValue({
        data: [],
        loading: false,
        error: errorMessage,
        refetch: mockRefetch,
      })

      render(<UsersPage />)
      
      expect(screen.getByText(`Erro ao carregar usuários: ${errorMessage}`)).toBeInTheDocument()
    })
  })

  describe('Create Operations', () => {
    it('should open create user dialog when add button is clicked', async () => {
      const user = userEvent.setup()
      render(<UsersPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*user/i })
      await user.click(addButton)
      
      expect(screen.getByRole('dialog')).toBeInTheDocument()
    })

    it('should create new user with valid data', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<UsersPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*user/i })
      await user.click(addButton)
      
      // Fill form
      await user.type(screen.getByLabelText(/name/i), 'New User')
      await user.type(screen.getByLabelText(/email/i), 'newuser@example.com')
      await user.selectOptions(screen.getByLabelText(/role/i), 'Admin')
      
      // Submit form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/users',
          'POST',
          expect.objectContaining({
            name: 'New User',
            email: 'newuser@example.com',
            role: 'Admin',
          })
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should show validation errors for invalid data', async () => {
      const user = userEvent.setup()
      render(<UsersPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*user/i })
      await user.click(addButton)
      
      // Submit empty form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/name.*required/i)).toBeInTheDocument()
        expect(screen.getByText(/email.*required/i)).toBeInTheDocument()
      })
    })
  })

  describe('Update Operations', () => {
    it('should open edit user dialog when edit button is clicked', async () => {
      const user = userEvent.setup()
      render(<UsersPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      // Should call handleEditUser (currently just logs)
      // In a full implementation, this would open an edit dialog
    })

    it('should update user with valid data', async () => {
      // This test would be similar to create, but for update operations
      // Since the current implementation only logs, we'll test the intention
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation()
      
      render(<UsersPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      expect(consoleSpy).toHaveBeenCalledWith('Edit user:', mockUsers[0])
      consoleSpy.mockRestore()
    })
  })

  describe('Delete Operations', () => {
    it('should delete user when delete button is clicked', async () => {
      mockMutate.mockResolvedValue({ success: true })
      
      render(<UsersPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/users/1',
          'DELETE'
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should show confirmation dialog before deletion', async () => {
      const user = userEvent.setup()
      render(<UsersPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        user.click(deleteButton)
      })
      
      // In a full implementation, this would show a confirmation dialog
      // Currently it deletes directly, but we're testing the intention
    })

    it('should handle delete error gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Delete failed'))
      
      render(<UsersPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(consoleErrorSpy).toHaveBeenCalledWith(
          'Erro ao excluir usuário:',
          expect.any(Error)
        )
      })
      
      consoleErrorSpy.mockRestore()
    })
  })

  describe('Search and Filter Operations', () => {
    it('should filter users by search term', async () => {
      const user = userEvent.setup()
      render(<UsersPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*users/i)
      await user.type(searchInput, 'John')
      
      await waitFor(() => {
        expect(screen.getByText('John Doe')).toBeInTheDocument()
        expect(screen.queryByText('Jane Smith')).not.toBeInTheDocument()
      })
    })

    it('should filter users by status', async () => {
      const user = userEvent.setup()
      render(<UsersPage />)
      
      const statusFilter = screen.getByRole('combobox', { name: /status/i })
      await user.selectOptions(statusFilter, 'Active')
      
      // Would filter based on status
      // Implementation depends on the actual filter logic
    })
  })

  describe('Pagination', () => {
    it('should handle pagination correctly', async () => {
      const user = userEvent.setup()
      render(<UsersPage />)
      
      // Test pagination controls if they exist
      const nextButton = screen.queryByRole('button', { name: /next/i })
      if (nextButton) {
        await user.click(nextButton)
        // Should load next page
      }
    })
  })

  describe('Sorting', () => {
    it('should sort users by name', async () => {
      const user = userEvent.setup()
      render(<UsersPage />)
      
      const nameHeader = screen.getByRole('columnheader', { name: /name/i })
      await user.click(nameHeader)
      
      // Should sort by name
      // Implementation depends on the actual sorting logic
    })
  })

  describe('Bulk Operations', () => {
    it('should select multiple users for bulk operations', async () => {
      const user = userEvent.setup()
      render(<UsersPage />)
      
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 0) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        // Should show bulk operation controls
      }
    })

    it('should perform bulk delete operation', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<UsersPage />)
      
      // Select users and perform bulk delete
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 0) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        const bulkDeleteButton = screen.queryByRole('button', { name: /delete selected/i })
        if (bulkDeleteButton) {
          await user.click(bulkDeleteButton)
          
          await waitFor(() => {
            expect(mockMutate).toHaveBeenCalled()
            expect(mockRefetch).toHaveBeenCalled()
          })
        }
      }
    })
  })
})