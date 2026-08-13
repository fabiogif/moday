/**
 * URL base da API.
 *
 * - Browser (produção): vazio → /api/... (mesma origem via Nginx, sem CORS)
 * - Browser (dev): http://localhost:8000
 * - Servidor Next.js (Route Handlers): URL interna absoluta em produção
 */

function normalizeBaseUrl(url: string): string {
  return url.replace(/\/$/, '')
}

export function getApiBaseUrl(): string {
  const configured = process.env.NEXT_PUBLIC_API_URL?.trim()

  if (configured) {
    return normalizeBaseUrl(configured)
  }

  if (process.env.NODE_ENV === 'production') {
    return ''
  }

  return 'http://localhost:8000'
}

/** URL absoluta para Route Handlers / SSR (fetch no servidor Node) */
export function getServerApiBaseUrl(): string {
  const internal = process.env.API_URL_INTERNAL?.trim()
  if (internal) {
    return normalizeBaseUrl(internal)
  }

  const configured = process.env.NEXT_PUBLIC_API_URL?.trim()
  if (configured) {
    return normalizeBaseUrl(configured)
  }

  if (process.env.NODE_ENV === 'production') {
    return 'http://127.0.0.1:8000'
  }

  return 'http://localhost:8000'
}

/** Monta URL da API (browser ou servidor) */
export function buildApiUrl(path: string, options?: { server?: boolean }): string {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  const base = options?.server ? getServerApiBaseUrl() : getApiBaseUrl()

  return base ? `${base}${normalizedPath}` : normalizedPath
}
