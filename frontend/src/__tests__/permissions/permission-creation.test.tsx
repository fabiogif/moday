import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, beforeEach, jest } from '@jest/globals'
import { PermissionFormDialog } from '../../app/(dashboard)/permissions/components/permission-form-dialog'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'

// Mock do useForm
jest.mock('react-hook-form', () => ({
  useForm: jest.fn()
}))

// Mock do zodResolver
jest.mock('@hookform/resolvers/zod', () => ({
  zodResolver: jest.fn()
}))

// Mock do toast
jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn()
  }
}))

// Schema de validação
const permissionFormSchema = z.object({
  name: z.string().min(2, { message: "Nome deve ter pelo menos 2 caracteres." }),
  slug: z.string().optional(),
  description: z.string().optional(),
  module: z.string().min(1, { message: "Módulo é obrigatório." }),
  action: z.string().min(1, { message: "Ação é obrigatória." }),
  resource: z.string().min(1, { message: "Recurso é obrigatório." }),
})

type PermissionFormValues = z.infer<typeof permissionFormSchema>

describe('PermissionFormDialog', () => {
  const mockOnAddPermission = jest.fn()
  const mockOnEditPermission = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should render permission creation form with all required fields', () => {
    const mockForm = {
      register: jest.fn(),
      handleSubmit: jest.fn(),
      formState: { errors: {} },
      reset: jest.fn(),
      watch: jest.fn(),
      setValue: jest.fn(),
      getValues: jest.fn()
    }

    (useForm as jest.Mock).mockReturnValue(mockForm as any)

    render(
      <PermissionFormDialog
        onAddPermission={mockOnAddPermission}
        onEditPermission={mockOnEditPermission}
      />
    )

    // Verificar se os campos obrigatórios estão presentes
    expect(screen.getByLabelText(/nome/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/módulo/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/ação/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/recurso/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/descrição/i)).toBeInTheDocument()
  })

  it('should validate required fields', async () => {
    const mockForm = {
      register: jest.fn(),
      handleSubmit: jest.fn((fn) => fn),
      formState: { 
        errors: {
          name: { message: 'Nome é obrigatório' },
          module: { message: 'Módulo é obrigatório' },
          action: { message: 'Ação é obrigatória' },
          resource: { message: 'Recurso é obrigatório' }
        }
      },
      reset: jest.fn(),
      watch: jest.fn(),
      setValue: jest.fn(),
      getValues: jest.fn()
    }

    (useForm as jest.Mock).mockReturnValue(mockForm as any)

    render(
      <PermissionFormDialog
        onAddPermission={mockOnAddPermission}
        onEditPermission={mockOnEditPermission}
      />
    )

    // Verificar se as mensagens de erro são exibidas
    expect(screen.getByText('Nome é obrigatório')).toBeInTheDocument()
    expect(screen.getByText('Módulo é obrigatório')).toBeInTheDocument()
    expect(screen.getByText('Ação é obrigatória')).toBeInTheDocument()
    expect(screen.getByText('Recurso é obrigatório')).toBeInTheDocument()
  })

  it('should call onAddPermission when form is submitted with valid data', async () => {
    const mockForm = {
      register: jest.fn(),
      handleSubmit: jest.fn((fn) => fn),
      formState: { errors: {} },
      reset: jest.fn(),
      watch: jest.fn(),
      setValue: jest.fn(),
      getValues: jest.fn()
    }

    (useForm as jest.Mock).mockReturnValue(mockForm as any)

    render(
      <PermissionFormDialog
        onAddPermission={mockOnAddPermission}
        onEditPermission={mockOnEditPermission}
      />
    )

    const submitButton = screen.getByRole('button', { name: /criar permissão/i })
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(mockOnAddPermission).toHaveBeenCalled()
    })
  })

  it('should show edit mode when editingPermission is provided', () => {
    const mockForm = {
      register: jest.fn(),
      handleSubmit: jest.fn(),
      formState: { errors: {} },
      reset: jest.fn(),
      watch: jest.fn(),
      setValue: jest.fn(),
      getValues: jest.fn()
    }

    (useForm as jest.Mock).mockReturnValue(mockForm as any)

    const editingPermission = {
      id: 1,
      name: 'Test Permission',
      slug: 'test.permission',
      description: 'Test description',
      module: 'test',
      action: 'permission',
      resource: 'test',
      is_active: true,
      created_at: '2023-01-01T00:00:00Z',
      updated_at: '2023-01-01T00:00:00Z'
    }

    render(
      <PermissionFormDialog
        onAddPermission={mockOnAddPermission}
        onEditPermission={mockOnEditPermission}
        editingPermission={editingPermission}
      />
    )

    // Verificar se está no modo de edição
    expect(screen.getByText(/editar permissão/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /atualizar permissão/i })).toBeInTheDocument()
  })

  it('should handle form submission for editing', async () => {
    const mockForm = {
      register: jest.fn(),
      handleSubmit: jest.fn((fn) => fn),
      formState: { errors: {} },
      reset: jest.fn(),
      watch: jest.fn(),
      setValue: jest.fn(),
      getValues: jest.fn()
    }

    (useForm as jest.Mock).mockReturnValue(mockForm as any)

    const editingPermission = {
      id: 1,
      name: 'Test Permission',
      slug: 'test.permission',
      description: 'Test description',
      module: 'test',
      action: 'permission',
      resource: 'test',
      is_active: true,
      created_at: '2023-01-01T00:00:00Z',
      updated_at: '2023-01-01T00:00:00Z'
    }

    render(
      <PermissionFormDialog
        onAddPermission={mockOnAddPermission}
        onEditPermission={mockOnEditPermission}
        editingPermission={editingPermission}
      />
    )

    const submitButton = screen.getByRole('button', { name: /atualizar permissão/i })
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(mockOnEditPermission).toHaveBeenCalledWith(1, expect.any(Object))
    })
  })
})

// Teste de integração para validação de dados
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
      name: '', // Nome vazio
      module: '', // Módulo vazio
      action: '', // Ação vazia
      resource: '' // Recurso vazio
    }

    const result = permissionFormSchema.safeParse(invalidData)
    expect(result.success).toBe(false)
  })

  it('should allow optional fields to be empty', () => {
    const minimalData: PermissionFormValues = {
      name: 'Test Permission',
      module: 'test',
      action: 'permission',
      resource: 'test'
      // slug e description são opcionais
    }

    const result = permissionFormSchema.safeParse(minimalData)
    expect(result.success).toBe(true)
  })
})
