import { buildApiUrl } from '@/lib/api-config'

export type PasswordResetScope = 'tenant' | 'admin'

type ApiErrorBody = {
  success?: boolean
  message?: string
  errors?: Record<string, string[]>
}

const ENDPOINTS: Record<PasswordResetScope, { forgot: string; reset: string }> = {
  tenant: {
    forgot: '/api/auth/forgot-password',
    reset: '/api/auth/reset-password',
  },
  admin: {
    forgot: '/api/admin/auth/forgot-password',
    reset: '/api/admin/auth/reset-password',
  },
}

async function parseApiError(response: Response, fallback: string): Promise<Error> {
  let errorMessage = fallback
  const contentType = response.headers.get('content-type')

  if (contentType?.includes('application/json')) {
    try {
      const error = (await response.json()) as ApiErrorBody
      if (error.message) {
        errorMessage = error.message
      } else if (error.errors) {
        const firstError = Object.values(error.errors)[0]
        if (Array.isArray(firstError) && firstError.length > 0) {
          errorMessage = firstError[0]
        }
      }
    } catch {
      errorMessage = `${response.status}: ${response.statusText || fallback}`
    }
  }

  return new Error(errorMessage)
}

export async function requestPasswordReset(
  email: string,
  scope: PasswordResetScope = 'tenant',
): Promise<string> {
  const response = await fetch(buildApiUrl(ENDPOINTS[scope].forgot), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'include',
    body: JSON.stringify({ email }),
  })

  if (!response.ok) {
    throw await parseApiError(response, 'Erro ao enviar link de recuperação')
  }

  const result = (await response.json()) as ApiErrorBody
  if (result.success === false) {
    throw new Error(result.message || 'Erro ao enviar link de recuperação')
  }

  return result.message || 'Link de recuperação enviado para seu email'
}

export async function resetPassword(
  data: {
    token: string
    email: string
    password: string
    password_confirmation: string
  },
  scope: PasswordResetScope = 'tenant',
): Promise<string> {
  const response = await fetch(buildApiUrl(ENDPOINTS[scope].reset), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'include',
    body: JSON.stringify(data),
  })

  if (!response.ok) {
    throw await parseApiError(response, 'Erro ao redefinir senha')
  }

  const result = (await response.json()) as ApiErrorBody
  if (result.success === false) {
    throw new Error(result.message || 'Erro ao redefinir senha')
  }

  return result.message || 'Senha redefinida com sucesso'
}
