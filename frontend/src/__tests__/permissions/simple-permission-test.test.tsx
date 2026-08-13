import { describe, it, expect } from '@jest/globals'
import { z } from 'zod'

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

  it('should validate required field lengths', () => {
    const invalidData = {
      name: 'A', // Muito curto
      module: 'test',
      action: 'permission',
      resource: 'test'
    }

    const result = permissionFormSchema.safeParse(invalidData)
    expect(result.success).toBe(false)
    
    if (!result.success) {
      expect(result.error.issues[0].message).toContain('pelo menos 2 caracteres')
    }
  })

  it('should validate module, action and resource are required', () => {
    const invalidData = {
      name: 'Test Permission',
      module: '', // Vazio
      action: '', // Vazio
      resource: '' // Vazio
    }

    const result = permissionFormSchema.safeParse(invalidData)
    expect(result.success).toBe(false)
    
    if (!result.success) {
      const errorMessages = result.error.issues.map(issue => issue.message)
      expect(errorMessages).toContain('Módulo é obrigatório.')
      expect(errorMessages).toContain('Ação é obrigatória.')
      expect(errorMessages).toContain('Recurso é obrigatório.')
    }
  })
})
