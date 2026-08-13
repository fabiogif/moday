/**
 * Rotas públicas (sem autenticação de tenant).
 * Demais caminhos exigem login e redirecionam para /login.
 */

const PUBLIC_EXACT = new Set(['/', '/login', '/auth/login'])

const PUBLIC_PREFIXES = [
  '/landing',
  '/plans',
  '/store/',
  '/demo/',
  '/auth/',
  '/admin/login',
  '/admin/forgot-password',
  '/admin/reset-password',
  '/sign-up',
  '/sign-in',
  '/forgot-password',
  '/reset-password',
]

export function isPublicRoute(pathname: string): boolean {
  if (PUBLIC_EXACT.has(pathname)) {
    return true
  }

  return PUBLIC_PREFIXES.some((prefix) => {
    if (prefix.endsWith('/')) {
      return pathname.startsWith(prefix)
    }
    return pathname === prefix || pathname.startsWith(`${prefix}/`)
  })
}

export function isAdminRoute(pathname: string): boolean {
  return pathname.startsWith('/admin')
}

export function getLoginRedirectUrl(pathname: string, search = ''): string {
  const target = `${pathname}${search}`
  if (!target || target === '/login' || target === '/auth/login' || isPublicRoute(pathname)) {
    return '/auth/login'
  }
  return `/auth/login?redirect=${encodeURIComponent(target)}`
}
