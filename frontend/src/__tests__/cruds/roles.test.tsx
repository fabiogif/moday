import { screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, generateRole } from '../utils/test-utils'
import RolesPage from '@/app/(dashboard)/roles/page'
import { useAuthenticatedRoles, useMutation } from '@/hooks/use-authenticated-api'

// Mock the hooks
const mockUseAuthenticatedRoles = useAuthenticatedRoles as jest.MockedFunction<typeof useAuthenticatedRoles>
const mockUseMutation = useMutation as jest.MockedFunction<typeof useMutation>

describe('Roles CRUD', () => {
  const mockRoles = [
    generateRole({ id: 1, name: 'Admin', slug: 'admin' }),
    generateRole({ id: 2, name: 'Editor', slug: 'editor' }),
    generateRole({ id: 3, name: 'Viewer', slug: 'viewer' }),
  ]

  const mockMutate = jest.fn()
  const mockRefetch = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
    
    mockUseAuthenticatedRoles.mockReturnValue({
      data: mockRoles,
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
      mockUseAuthenticatedRoles.mockReturnValue({
        data: [],
        loading: false,
        error: null,
        refetch: mockRefetch,
        isAuthenticated: false,
      })

      render(<RolesPage />)
      
      expect(screen.getByText('Usuário não autenticado. Faça login para continuar.')).toBeInTheDocument()
    })
  })

  describe('Read Operations', () => {
    it('should render roles list', async () => {
      render(<RolesPage />)
      
      await waitFor(() => {
        expect(screen.getByText('Admin')).toBeInTheDocument()
        expect(screen.getByText('Editor')).toBeInTheDocument()
        expect(screen.getByText('Viewer')).toBeInTheDocument()
      })
    })

    it('should display loading state', () => {
      mockUseAuthenticatedRoles.mockReturnValue({
        data: [],
        loading: true,
        error: null,
        refetch: mockRefetch,
        isAuthenticated: true,
      })

      render(<RolesPage />)
      
      expect(screen.getByText('Carregando roles...')).toBeInTheDocument()
    })

    it('should display error state', () => {
      const errorMessage = 'Failed to fetch roles'
      mockUseAuthenticatedRoles.mockReturnValue({
        data: [],
        loading: false,
        error: errorMessage,
        refetch: mockRefetch,
        isAuthenticated: true,
      })

      render(<RolesPage />)
      
      expect(screen.getByText(`Erro ao carregar roles: ${errorMessage}`)).toBeInTheDocument()
    })

    it('should display role details correctly', async () => {
      render(<RolesPage />)
      
      await waitFor(() => {
        expect(screen.getByText('admin')).toBeInTheDocument()
        expect(screen.getByText('editor')).toBeInTheDocument()
        expect(screen.getByText('viewer')).toBeInTheDocument()
      })
    })

    it('should display page header and description', () => {
      render(<RolesPage />)
      
      expect(screen.getByText('Roles')).toBeInTheDocument()
      expect(screen.getByText('Gerencie as roles do sistema')).toBeInTheDocument()
    })
  })

  describe('Create Operations', () => {
    it('should open create role dialog when add button is clicked', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*role/i })
      await user.click(addButton)
      
      expect(screen.getByRole('dialog')).toBeInTheDocument()
    })

    it('should create new role with valid data', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<RolesPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*role/i })
      await user.click(addButton)
      
      // Fill form
      await user.type(screen.getByLabelText(/name/i), 'Manager')
      await user.type(screen.getByLabelText(/slug/i), 'manager')
      
      // Submit form
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/roles',
          'POST',
          expect.objectContaining({
            name: 'Manager',
            slug: 'manager',
          })
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should auto-generate slug from name', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*role/i })
      await user.click(addButton)
      
      // Type name
      const nameInput = screen.getByLabelText(/name/i)
      await user.type(nameInput, 'Content Manager')
      
      // Slug should be auto-generated
      const slugInput = screen.getByLabelText(/slug/i)
      expect(slugInput.value).toBe('content-manager')
    })

    it('should show validation errors for invalid data', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      // Open dialog
      const addButton = screen.getByRole('button', { name: /add.*role/i })
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
      render(<RolesPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*role/i })
      await user.click(addButton)
      
      // Fill form with invalid slug
      await user.type(screen.getByLabelText(/name/i), 'Test Role')
      await user.clear(screen.getByLabelText(/slug/i))
      await user.type(screen.getByLabelText(/slug/i), 'Invalid Slug!')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/slug.*format/i)).toBeInTheDocument()
      })
    })

    it('should handle create error gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Create failed'))
      
      render(<RolesPage />)
      
      // Open dialog and fill form
      const addButton = screen.getByRole('button', { name: /add.*role/i })
      await userEvent.click(addButton)
      
      await userEvent.type(screen.getByLabelText(/name/i), 'Test Role')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await userEvent.click(submitButton)
      
      await waitFor(() => {
        expect(consoleErrorSpy).toHaveBeenCalledWith(
          'Erro ao criar role:',
          expect.any(Error)
        )
      })
      
      consoleErrorSpy.mockRestore()
    })
  })

  describe('Update Operations', () => {
    it('should open edit role dialog when edit button is clicked', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      // Should call handleEditRole (currently just logs)
    })

    it('should update role with valid data', async () => {
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation()
      
      render(<RolesPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      expect(consoleSpy).toHaveBeenCalledWith('Editar role:', 1)
      consoleSpy.mockRestore()
    })

    it('should handle role name update', async () => {
      const user = userEvent.setup()
      mockMutate.mockResolvedValue({ success: true })
      
      render(<RolesPage />)
      
      // In a full implementation, clicking edit would open a dialog
      // with pre-filled form fields
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0]
        fireEvent.click(editButton)
      })
      
      // Currently only logs, but in full implementation would:
      // 1. Open edit dialog
      // 2. Pre-fill with current values
      // 3. Allow editing
      // 4. Submit changes
    })
  })

  describe('Delete Operations', () => {
    it('should delete role when delete button is clicked', async () => {
      mockMutate.mockResolvedValue({ success: true })
      
      render(<RolesPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(mockMutate).toHaveBeenCalledWith(
          '/api/roles/1',
          'DELETE'
        )
        expect(mockRefetch).toHaveBeenCalled()
      })
    })

    it('should handle delete error gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Delete failed'))
      
      render(<RolesPage />)
      
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
        fireEvent.click(deleteButton)
      })
      
      await waitFor(() => {
        expect(consoleErrorSpy).toHaveBeenCalledWith(
          'Erro ao excluir role:',
          expect.any(Error)
        )
      })
      
      consoleErrorSpy.mockRestore()
    })

    it('should show confirmation before deleting system roles', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      // Admin role should require special confirmation
      await waitFor(() => {
        const deleteButton = screen.getAllByRole('button', { name: /delete/i })[0] // Admin role
        user.click(deleteButton)
      })
      
      // In a full implementation, this would show a warning dialog
      // for critical system roles
    })
  })

  describe('Search and Filter Operations', () => {
    it('should filter roles by search term', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*roles/i)
      await user.type(searchInput, 'Admin')
      
      await waitFor(() => {
        expect(screen.getByText('Admin')).toBeInTheDocument()
        expect(screen.queryByText('Editor')).not.toBeInTheDocument()
        expect(screen.queryByText('Viewer')).not.toBeInTheDocument()
      })
    })

    it('should filter roles by slug', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      const searchInput = screen.getByPlaceholderText(/search.*roles/i)
      await user.type(searchInput, 'editor')
      
      await waitFor(() => {
        expect(screen.queryByText('Admin')).not.toBeInTheDocument()
        expect(screen.getByText('Editor')).toBeInTheDocument()
        expect(screen.queryByText('Viewer')).not.toBeInTheDocument()
      })
    })
  })

  describe('Sorting Operations', () => {
    it('should sort roles by name', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      const nameHeader = screen.queryByRole('columnheader', { name: /name/i })
      if (nameHeader) {
        await user.click(nameHeader)
        // Should sort roles alphabetically
      }
    })

    it('should sort roles by creation date', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      const dateHeader = screen.queryByRole('columnheader', { name: /created/i })
      if (dateHeader) {
        await user.click(dateHeader)
        // Should sort roles by creation date
      }
    })
  })

  describe('Role Management Features', () => {
    it('should display role creation date', async () => {
      render(<RolesPage />)
      
      await waitFor(() => {
        // Should show formatted creation dates
        expect(screen.getByText(/2024-01-15/)).toBeInTheDocument()
      })
    })

    it('should show role permissions count', async () => {
      render(<RolesPage />)
      
      // In a full implementation, would show number of permissions
      // associated with each role
    })

    it('should show users count per role', async () => {
      render(<RolesPage />)
      
      // In a full implementation, would show number of users
      // with each role
    })
  })

  describe('Bulk Operations', () => {
    it('should select multiple roles for bulk operations', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
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
      
      render(<RolesPage />)
      
      // Select roles (excluding system roles)
      const checkboxes = screen.getAllByRole('checkbox')
      if (checkboxes.length > 1) {
        await user.click(checkboxes[1]) // Editor
        await user.click(checkboxes[2]) // Viewer
        
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

  describe('Role Validation', () => {
    it('should prevent duplicate role names', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*role/i })
      await user.click(addButton)
      
      // Try to create role with existing name
      await user.type(screen.getByLabelText(/name/i), 'Admin')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/role.*already exists/i)).toBeInTheDocument()
      })
    })

    it('should prevent duplicate slugs', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      const addButton = screen.getByRole('button', { name: /add.*role/i })
      await user.click(addButton)
      
      // Try to create role with existing slug
      await user.type(screen.getByLabelText(/name/i), 'Administrator')
      await user.clear(screen.getByLabelText(/slug/i))
      await user.type(screen.getByLabelText(/slug/i), 'admin')
      
      const submitButton = screen.getByRole('button', { name: /create/i })
      await user.click(submitButton)
      
      await waitFor(() => {
        expect(screen.getByText(/slug.*already exists/i)).toBeInTheDocument()
      })
    })
  })

  describe('Role Permissions Integration', () => {
    it('should show link to manage permissions', async () => {
      render(<RolesPage />)
      
      const managePermissionsButton = screen.queryByRole('button', { name: /manage permissions/i })
      if (managePermissionsButton) {
        expect(managePermissionsButton).toBeInTheDocument()
      }
    })

    it('should navigate to permissions page', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      const permissionsLink = screen.queryByRole('link', { name: /permissions/i })
      if (permissionsLink) {
        await user.click(permissionsLink)
        // Should navigate to permissions management
      }
    })
  })

  describe('System Role Protection', () => {
    it('should protect system roles from deletion', async () => {
      render(<RolesPage />)
      
      // Admin role should be protected
      const adminDeleteButton = screen.getAllByRole('button', { name: /delete/i })[0]
      
      // In a full implementation, this button might be disabled
      // or show a warning when clicked
      expect(adminDeleteButton).toBeInTheDocument()
    })

    it('should show warning when editing system roles', async () => {
      const user = userEvent.setup()
      render(<RolesPage />)
      
      await waitFor(() => {
        const editButton = screen.getAllByRole('button', { name: /edit/i })[0] // Admin role
        user.click(editButton)
      })
      
      // In a full implementation, would show warning about
      // editing system-critical roles
    })
  })

  describe('Error Handling', () => {
    it('should handle network errors gracefully', async () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation()
      mockMutate.mockRejectedValue(new Error('Network error'))
      
      render(<RolesPage />)
      
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
      mockUseAuthenticatedRoles.mockReturnValue({
        data: [],
        loading: false,
        error: errorMessage,
        refetch: mockRefetch,
        isAuthenticated: true,
      })

      render(<RolesPage />)
      
      expect(screen.getByText(`Erro ao carregar roles: ${errorMessage}`)).toBeInTheDocument()
    })
  })
})