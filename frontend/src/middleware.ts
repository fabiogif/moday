import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

export function middleware(request: NextRequest) {
  const token = request.cookies.get('auth-token')?.value
  const { pathname } = request.nextUrl

  // Rotas públicas que não precisam de autenticação
  const publicRoutes = [
    '/login',
    '/sign-up-3', 
    '/forgot-password-3',
    '/reset-password',
    '/'
  ]

  // Rotas protegidas que precisam de autenticação
  const protectedRoutes = [
    '/dashboard',
    '/users',
    '/orders',
    '/categories',
    '/products',
    '/tables',
    '/clients',
    '/reports',
    '/permissions',
    '/profiles',
    '/settings'
  ]

  // Se está em uma rota pública, permitir acesso
  if (publicRoutes.some(route => pathname.startsWith(route))) {
    return NextResponse.next()
  }

  // Se está em uma rota protegida e não tem token, redirecionar para login
  if (protectedRoutes.some(route => pathname.startsWith(route))) {
    if (!token) {
      return NextResponse.redirect(new URL('/login', request.url))
    }
  }

  // Se tem token e está tentando acessar login, redirecionar para dashboard
  if (token && pathname === '/login') {
    return NextResponse.redirect(new URL('/dashboard', request.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico).*)',
  ],
}