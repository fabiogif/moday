import { buildApiUrl } from '@/lib/api-config'
import { getAuthToken } from '@/lib/auth-storage'

type ApiErrorBody = {
  success?: boolean
  message?: string
  error?: string
  retry_after?: number
  errors?: Record<string, string[]>
}

export class EmailVerificationError extends Error {
  errorCode?: string
  retryAfter?: number

  constructor(message: string, errorCode?: string, retryAfter?: number) {
    super(message)
    this.name = 'EmailVerificationError'
    this.errorCode = errorCode
    this.retryAfter = retryAfter
  }
}

async function parseApiError(response: Response, fallback: string): Promise<EmailVerificationError> {
  let errorMessage = fallback
  let errorCode: string | undefined
  let retryAfter: number | undefined
  const contentType = response.headers.get('content-type')

  if (contentType?.includes('application/json')) {
    try {
      const error = (await response.json()) as ApiErrorBody
      if (error.message) errorMessage = error.message
      errorCode = error.error
      retryAfter = error.retry_after
      if (!error.message && error.errors) {
        const firstError = Object.values(error.errors)[0]
        if (Array.isArray(firstError) && firstError.length > 0) {
          errorMessage = firstError[0]
        }
      }
    } catch {
      errorMessage = `${response.status}: ${response.statusText || fallback}`
    }
  }

  return new EmailVerificationError(errorMessage, errorCode, retryAfter)
}

function authHeaders(): HeadersInit {
  const token = getAuthToken()
  return {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  }
}

export function maskEmail(email: string): string {
  const [local, domain] = email.split('@')
  if (!local || !domain) return '***'
  return `${local.slice(0, 1)}***@${domain}`
}

export async function verifyEmailCode(code: string): Promise<string> {
  const response = await fetch(buildApiUrl('/api/auth/email/verify'), {
    method: 'POST',
    headers: authHeaders(),
    credentials: 'include',
    body: JSON.stringify({ code }),
  })

  if (!response.ok) {
    throw await parseApiError(response, 'Não foi possível verificar o código')
  }

  const result = (await response.json()) as ApiErrorBody & {
    data?: { email_verified?: boolean; user?: unknown }
  }
  if (result.success === false) {
    throw new EmailVerificationError(result.message || 'Código inválido', result.error)
  }

  return result.message || 'E-mail confirmado com sucesso'
}

export async function resendVerificationEmail(): Promise<{ message: string; email?: string }> {
  const response = await fetch(buildApiUrl('/api/auth/email/verification-notification'), {
    method: 'POST',
    headers: authHeaders(),
    credentials: 'include',
  })

  if (!response.ok) {
    throw await parseApiError(response, 'Não foi possível reenviar o código')
  }

  const result = (await response.json()) as ApiErrorBody & {
    data?: { email?: string }
  }
  if (result.success === false) {
    throw new EmailVerificationError(result.message || 'Erro ao reenviar', result.error, result.retry_after)
  }

  return {
    message: result.message || 'Novo código enviado',
    email: result.data?.email,
  }
}
