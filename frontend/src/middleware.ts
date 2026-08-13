import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'
import { isAdminRoute, isPublicRoute } from '@/lib/auth-routes'

const AUTH_COOKIE = 'auth-token'

function resolvePostLoginPath(redirectParam: string | null): string {
  if (!redirectParam || !redirectParam.startsWith('/') || redirectParam.startsWith('//')) {
    return '/dashboard'
  }
  if (redirectParam === '/login' || redirectParam === '/auth/login' || isPublicRoute(redirectParam.split('?')[0])) {
    return '/dashboard'
  }
  return redirectParam
}

export function middleware(request: NextRequest) {
  const token = request.cookies.get(AUTH_COOKIE)?.value
  const { pathname, search } = request.nextUrl

  // Admin tem fluxo próprio (admin-auth-context)
  if (isAdminRoute(pathname)) {
    return NextResponse.next()
  }

  if (isPublicRoute(pathname)) {
    if (token && (pathname === '/login' || pathname === '/auth/login')) {
      const redirectParam = request.nextUrl.searchParams.get('redirect')
      const target = resolvePostLoginPath(redirectParam)
      return NextResponse.redirect(new URL(target, request.url))
    }
    return NextResponse.next()
  }

  if (!token) {
    const loginUrl = new URL('/auth/login', request.url)
    const destination = `${pathname}${search}`
    if (destination && destination !== '/auth/login' && destination !== '/login') {
      loginUrl.searchParams.set('redirect', destination)
    }
    return NextResponse.redirect(loginUrl)
  }

  return NextResponse.next()
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico|manifest.json|.*\\.(?:svg|png|jpg|jpeg|gif|webp|ico)$).*)',
  ],
}
