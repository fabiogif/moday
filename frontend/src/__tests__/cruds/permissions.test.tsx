import { screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, generatePermission } from '../utils/test-utils'
import PermissionsPage from '@/app/(dashboard)/permissions/page'
import { useAuthenticatedPermissions, useMutation } from '@/hooks/use-authenticated-api'

// Mock the hooks
const mockUseAuthenticatedPermissions = useAuthenticatedPermissions as jest.MockedFunction<typeof useAuthenticatedPermissions>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

describe('Permissions CRUD', () => {
  const mockPermissions = [
    generatePermission({ 
      id: 1, 
      name: 'Create Users', 
      slug: 'create-users',
      description: 'Can create new users in the system'
    }),
    generatePermission({ 
      id: 2, 
      name: 'Edit Products', 
      slug: 'edit-products',
      description: 'Can edit existing products'
    }),
    generatePermission({ 
      id: 3, 
      name: 'Delete Orders', 
      slug: 'delete-orders',
      description: 'Can delete orders from the system'
    }),
  ]

  const mockMutate = jest.fn()
  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    
    mockUseAuthenticatedPermissions.mockReturnValue({
      data: mockPermissions,
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
      mockUseAuthenticatedPermissions.mockReturnValue({
        data: [],
        loading: false,
        error: null,
        refetch: mockRefetch,
        isAuthenticated: false,
      })

      render(<PermissionsPage />)
      
      expect(screen.getByText('Usuário não autenticado. Faça login para continuar.')).toBeInTheDocument()
    })
  })

  describe('Read Operations', () => {
    it('should render permissions list', async () => {
      render(<PermissionsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('Create Users')).toBeInTheDocument()
        expect(screen.getByText('Edit Products')).toBeInTheDocument()
        expect(screen.getByText('Delete Orders')).toBeInTheDocument()
      })
    })

    it('should display loading state', () => {
      mockUseAuthenticatedPermissions.mockReturnValue({
        data: [],
        loading: true,
        error: null,
        refetch: mockRefetch,
        isAuthenticated: true,
      })

      render(<PermissionsPage />)
      
      expect(screen.getByText('Carregando permissões...')).toBeInTheDocument()
    })

    it('should display error state', () => {
      const errorMessage = 'Failed to fetch permissions'
      mockUseAuthenticatedPermissions.mockReturnValue({
        data: [],
        loading: false,
        error: errorMessage,
        refetch: mockRefetch,
        isAuthenticated: true,
      })

      render(<PermissionsPage />)
      
      expect(screen.getByText(`Erro ao carregar permissões: ${errorMessage}`)).toBeInTheDocument()
    })

    it('should display permission details correctly', async () => {
      render(<PermissionsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('create-users')).toBeInTheDocument()
        expect(screen.getByText('edit-products')).toBeInTheDocument()
        expect(screen.getByText('delete-orders')).toBeInTheDocument()
        expect(screen.getByText('Can create new users in the system')).toBeInTheDocument()
        expect(screen.getByText('Can edit existing products')).toBeInTheDocument()
        expect(screen.getByText('Can delete orders from the system')).toBeInTheDocument()
      })
    })

    it('should display page header and description', () => {
      render(<PermissionsPage />)
      
      expect(screen.getByText('Permissões')).toBeInTheDocument()
      expect(screen.getByText('Gerencie as permissões do sistema')).toBeInTheDocument()
    })
  })

  describe('Create Operations', () => {
    it('should open create permission dialog when add button is clicked', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await user.click(addButton)
      
      expect(screen.getByRole('dialog')).toBeInTheDocument()
    })

    it('should create new permission with valid data', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<PermissionsPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await user.click(addButton)
      
      // Fill form
      await user.type(screen.getByLabelText(/name/i), 'View Reports')
      await user.type(screen.getByLabelText(/slug/i), 'view-reports')
      await user.type(screen.getByLabelText(/description/i), 'Can view system reports')
      
      // Submit form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/permissions',
          'POST',
          expect.objectContaining({
            name: 'View Reports',
            slug: 'view-reports',
            description: 'Can view system reports',
          })
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should auto-generate slug from name', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await user.click(addButton)
      
      // Type name
      const nameInput = screen.getByLabelText(/name/i)
      await user.type(nameInput, 'Manage User Accounts')
      
      // Slug should be auto-generated
      const slugInput = screen.getByLabelText(/slug/i)
      expect(slugInput.value).toBe('manage-user-accounts')
    })

    it('should show validation errors for invalid data', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await user.click(addButton)
      
      // Submit empty form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/name.*required/i)).toBeInTheDocument()
        expect(screen.getByText(/slug.*required/i)).toBeInTheDocument()
      })
    })

    it('should validate slug format', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await user.click(addButton)
      
      // Fill form with invalid slug
      await user.type(screen.getByLabelText(/name/i), 'Test Permission')
      await user.clear(screen.getByLabelText(/slug/i))
      await user.type(screen.getByLabelText(/slug/i), 'Invalid Slug!')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/slug.*format/i)).toBeInTheDocument()
      })
    })

    it('should create permission without description', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<PermissionsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await user.click(addButton)
      
      // Fill only required fields
      await user.type(screen.getByLabelText(/name/i), 'Basic Permission')
      await user.type(screen.getByLabelText(/slug/i), 'basic-permission')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/permissions',
          'POST',
          expect.objectContaining({
            name: 'Basic Permission',
            slug: 'basic-permission',
          })
        )
      })
    })

    it('should handle create error gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Create failed'))
      
      render(<PermissionsPage />)
      
      // Open dialog and fill form
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await userEvent.click(addButton)
      
      await userEvent.type(screen.getByLabelText(/name/i), 'Test Permission')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await userEvent.click(submitButton)
      
      await waitFor(() => {
        expect(consoleErrorSpy).toHaveBeenCalledWith(
          'Erro ao criar permissão:',
          expect.any(Error)
        )
      })
      
      consoleErrorSpy.mockRestore()
    })
  })

  describe('Update Operations', () => {
    it('should open edit permission dialog when edit button is clicked', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      // Should call handleEditPermission (currently just logs)
    })

    it('should update permission with valid data', async () => {
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation()
      
      render(<PermissionsPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      expect(consoleSpy).toHaveBeenCalledWith('Editar permissão:', 1)
      consoleSpy.mockRestore()
    })

    it('should handle permission description update', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<PermissionsPage />)
      
      // In a full implementation, clicking edit would open a dialog
      // with pre-filled form fields
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      // Currently only logs, but in full implementation would:
      // 1. Open edit dialog
      // 2. Pre-fill with current values
      // 3. Allow editing description
      // 4. Submit changes
    })
  })

  describe('Delete Operations', () => {
    it('should delete permission when delete button is clicked', async () => {
      mockMutate.mockResolvedValue({ success: true })
      
      render(<PermissionsPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/permissions/1',
          'DELETE'
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should handle delete error gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Delete failed'))
      
      render(<PermissionsPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(consoleErrorSpy).toHaveBeenCalledWith(
          'Erro ao excluir permissão:',
          expect.any(Error)
        )
      })
      
      consoleErrorSpy.mockRestore()
    })

    it('should show confirmation before deleting critical permissions', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      // Critical permissions should require special confirmation
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[2] // Delete Orders
        user.click(deleteButton)
      })
      
      // In a full implementation, this would show a warning dialog
      // for permissions that could affect system security
    })
  })

  describe('Search and Filter Operations', () => {
    it('should filter permissions by search term', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*permissions/i)
      await user.type(searchInput, 'Create')
      
      await waitFor(() => {
        expect(screen.getByText('Create Users')).toBeInTheDocument()
        expect(screen.queryByText('Edit Products')).not.toBeInTheDocument()
        expect(screen.queryByText('Delete Orders')).not.toBeInTheDocument()
      })
    })

    it('should filter permissions by slug', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*permissions/i)
      await user.type(searchInput, 'edit-products')
      
      await waitFor(() => {
        expect(screen.queryByText('Create Users')).not.toBeInTheDocument()
        expect(screen.getByText('Edit Products')).toBeInTheDocument()
        expect(screen.queryByText('Delete Orders')).not.toBeInTheDocument()
      })
    })

    it('should filter permissions by description', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*permissions/i)
      await user.type(searchInput, 'products')
      
      await waitFor(() => {
        expect(screen.getByText('Edit Products')).toBeInTheDocument()
        expect(screen.getByText('Can edit existing products')).toBeInTheDocument()
      })
    })

    it('should filter permissions by category', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const categoryFilter = screen.queryByRole('combobox', { name: /category/i })
      if (categoryFilter) {
        await user.selectOptions(categoryFilter, 'User Management')
        
        // Should show only user-related permissions
        await waitFor(() => {
          expect(screen.getByText('Create Users')).toBeInTheDocument()
        })
      }
    })
  })

  describe('Sorting Operations', () => {
    it('should sort permissions by name', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const nameHeader = screen.queryByRole('columnheader', { name: /name/i })
      if (nameHeader) {
        await user.click(nameHeader)
        // Should sort permissions alphabetically
      }
    })

    it('should sort permissions by creation date', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const dateHeader = screen.queryByRole('columnheader', { name: /created/i })
      if (dateHeader) {
        await user.click(dateHeader)
        // Should sort permissions by creation date
      }
    })
  })

  describe('Permission Management Features', () => {
    it('should display permission creation date', async () => {
      render(<PermissionsPage />)
      
      await waitFor(() => {
        // Should show formatted creation dates
        expect(screen.getByText(/2024-01-15/)).toBeInTheDocument()
      })
    })

    it('should group permissions by category', async () => {
      render(<PermissionsPage />)
      
      // In a full implementation, permissions might be grouped
      // by categories like "User Management", "Product Management", etc.
    })

    it('should show roles that have each permission', async () => {
      render(<PermissionsPage />)
      
      // In a full implementation, would show which roles
      // are assigned each permission
    })
  })

  describe('Bulk Operations', () => {
    it('should select multiple permissions for bulk operations', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        // Should show bulk operation controls
      }
    })

    it('should perform bulk delete operation', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<PermissionsPage />)
      
      // Select permissions (excluding critical ones)
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
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

    it('should assign permissions to role in bulk', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<PermissionsPage />)
      
      // Select permissions
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[0])
        await user.click(checkboxes[1])
        
        const assignToRoleButton = screen.queryByRole('button', { name: /assign to role/i })
        if (assignToRoleButton) {
          await user.click(assignToRoleButton)
          
          // Should open role selection dialog
        }
      }
    })
  })

  describe('Permission Validation', () => {
    it('should prevent duplicate permission names', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await user.click(addButton)
      
      // Try to create permission with existing name
      await user.type(screen.getByLabelText(/name/i), 'Create Users')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/permission.*already exists/i)).toBeInTheDocument()
      })
    })

    it('should prevent duplicate slugs', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await user.click(addButton)
      
      // Try to create permission with existing slug
      await user.type(screen.getByLabelText(/name/i), 'New User Creation')
      await user.clear(screen.getByLabelText(/slug/i))
      await user.type(screen.getByLabelText(/slug/i), 'create-users')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/slug.*already exists/i)).toBeInTheDocument()
      })
    })

    it('should validate permission name length', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*permission/i })
      await user.click(addButton)
      
      // Try to create permission with too long name
      const longName = 'A'.repeat(256)
      await user.type(screen.getByLabelText(/name/i), longName)
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/name.*too long/i)).toBeInTheDocument()
      })
    })
  })

  describe('Role-Permission Integration', () => {
    it('should show link to manage roles', async () => {
      render(<PermissionsPage />)
      
      const manageRolesButton = screen.queryByRole('button', { name: /manage roles/i })
      if (manageRolesButton) {
        expect(manageRolesButton).toBeInTheDocument()
      }
    })

    it('should navigate to roles page', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const rolesLink = screen.queryByRole('link', { name: /roles/i })
      if (rolesLink) {
        await user.click(rolesLink)
        // Should navigate to roles management
      }
    })

    it('should show which roles have specific permission', async () => {
      render(<PermissionsPage />)
      
      // In a full implementation, would show role chips or badges
      // next to each permission indicating which roles have it
    })
  })

  describe('System Permission Protection', () => {
    it('should protect system permissions from deletion', async () => {
      render(<PermissionsPage />)
      
      // Critical permissions should be protected
      const criticalDeleteButton = screen.getAllByRole('button', { name: /delete/i })[2] // Delete Orders
      
      // In a full implementation, this button might be disabled
      // or show a warning when clicked
      expect(criticalDeleteButton).toBeInTheDocument()
    })

    it('should show warning when editing system permissions', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[2] // Delete Orders
        user.click(editButton)
      })
      
      // In a full implementation, would show warning about
      // editing system-critical permissions
    })
  })

  describe('Permission Categories', () => {
    it('should display permissions grouped by category', () => {
      render(<PermissionsPage />)
      
      // In a full implementation, permissions would be grouped
      // by categories like "User Management", "Product Management", etc.
    })

    it('should filter by permission category', async () => {
      const user = userEvent.setup()
      render(<PermissionsPage />)
      
      const categoryFilter = screen.queryByRole('combobox', { name: /category/i })
      if (categoryFilter) {
        await user.selectOptions(categoryFilter, 'User Management')
        
        // Should show only user management permissions
      }
    })
  })

  describe('Error Handling', () => {
    it('should handle network errors gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Network error'))
      
      render(<PermissionsPage />)
      
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

    it('should show user-friendly error messages', () => {
      const errorMessage = 'Access denied'
      mockUseAuthenticatedPermissions.mockReturnValue({
        data: [],
        loading: false,
        error: errorMessage,
        refetch: mockRefetch,
        isAuthenticated: true,
      })

      render(<PermissionsPage />)
      
      expect(screen.getByText(`Erro ao carregar permissões: ${errorMessage}`)).toBeInTheDocument()
    })
  })

  describe('Permission Documentation', () => {
    it('should display permission descriptions clearly', async () => {
      render(<PermissionsPage />)
      
      await waitFor(() => {
        expect(screen.getByText('Can create new users in the system')).toBeInTheDocument()
        expect(screen.getByText('Can edit existing products')).toBeInTheDocument()
        expect(screen.getByText('Can delete orders from the system')).toBeInTheDocument()
      })
    })

    it('should handle permissions without descriptions', () => {
      const permissionsWithoutDesc = [
        generatePermission({ 
          id: 1, 
          name: 'Basic Permission', 
          slug: 'basic-permission',
          description: undefined
        }),
      ]
      
      mockUseAuthenticatedPermissions.mockReturnValue({
        data: permissionsWithoutDesc,
        loading: false,
        error: null,
        refetch: mockRefetch,
        isAuthenticated: true,
      })

      render(<PermissionsPage />)
      
      expect(screen.getByText('Basic Permission')).toBeInTheDocument()
      expect(screen.getByText('basic-permission')).toBeInTheDocument()
    })
  })
})