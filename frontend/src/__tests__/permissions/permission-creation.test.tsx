import React from 'react'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, it, expect, beforeEach, jest } from '@jest/globals'
import { PermissionFormDialog } from '../../app/(dashboard)/permissions/components/permission-form-dialog'
import { z } from 'zod'

jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn()
  }
}))

const permissionFormSchema = z.object({
  name: z.string().min(2, { message: "Nome deve ter pelo menos 2 caracteres." }),
  slug: z.string().optional(),
  description: z.string().optional(),
  module: z.string().optional(),
  action: z.string().optional(),
  resource: z.string().optional(),
})

type PermissionFormValues = z.infer<typeof permissionFormSchema>

describe('PermissionFormDialog', () => {
  const mockOnAddPermission = jest.fn()
  const mockOnEditPermission = jest.fn()
  const mockOnOpenChange = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
  })

  const renderDialog = (props: Partial<React.ComponentProps<typeof PermissionFormDialog>> = {}) =>
    render(
      <PermissionFormDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        onAddPermission={mockOnAddPermission}
        onEditPermission={mockOnEditPermission}
        {...props}
      />
    )

  it('should render permission creation form with all required fields', () => {
    renderDialog()

    expect(screen.getByLabelText(/^nome$/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/^módulo$/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/^ação$/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/^recurso$/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/descrição/i)).toBeInTheDocument()
  })

  it('should validate required fields', async () => {
    renderDialog()

    fireEvent.click(screen.getByRole('button', { name: /criar permissão/i }))

    await waitFor(() => {
      expect(screen.getByText('Nome deve ter pelo menos 2 caracteres.')).toBeInTheDocument()
    })
  })

  it('should call onAddPermission when form is submitted with valid data', async () => {
    const user = userEvent.setup()
    renderDialog()

    await user.type(screen.getByLabelText(/^nome$/i), 'Test Permission')
    await user.click(screen.getByRole('button', { name: /criar permissão/i }))

    await waitFor(() => {
      expect(mockOnAddPermission).toHaveBeenCalledWith(
        expect.objectContaining({ name: 'Test Permission' })
      )
    })
  })

  it('should show edit mode when editingPermission is provided', () => {
    const editingPermission = {
      id: 1,
      name: 'Test Permission',
      slug: 'test.permission',
      description: 'Test description',
      module: 'test',
      action: 'permission',
      resource: 'test',
      created_at: '2023-01-01T00:00:00Z',
      updated_at: '2023-01-01T00:00:00Z'
    }

    renderDialog({ editingPermission })

    expect(screen.getByText(/editar permissão/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /atualizar permissão/i })).toBeInTheDocument()
  })

  it('should handle form submission for editing', async () => {
    const user = userEvent.setup()
    const editingPermission = {
      id: 1,
      name: 'Test Permission',
      slug: 'test.permission',
      description: 'Test description',
      module: 'test',
      action: 'permission',
      resource: 'test',
      created_at: '2023-01-01T00:00:00Z',
      updated_at: '2023-01-01T00:00:00Z'
    }

    renderDialog({ editingPermission })

    await user.click(screen.getByRole('button', { name: /atualizar permissão/i }))

    await waitFor(() => {
      expect(mockOnEditPermission).toHaveBeenCalledWith(1, expect.any(Object))
    })
  })
})

describe('Permission Form Validation', () => {
  it('should validate permission data structure', () => {
    const validData: PermissionFormValues = {
      name: 'Test Permission',
      slug: 'test.permission',
      description: 'Test description',
      module: 'test',
      action: 'permission',
      resource: 'test'
    }

    const result = permissionFormSchema.safeParse(validData)
    expect(result.success).toBe(true)
  })

  it('should reject invalid permission data', () => {
    const invalidData = {
      name: '',
    }

    const result = permissionFormSchema.safeParse(invalidData)
    expect(result.success).toBe(false)
  })

  it('should allow optional fields to be empty', () => {
    const minimalData: PermissionFormValues = {
      name: 'Test Permission',
    }

    const result = permissionFormSchema.safeParse(minimalData)
    expect(result.success).toBe(true)
  })
})
