"use client"

import { ReactNode } from 'react'
import { usePermissions } from '@/hooks/usePermissions'

interface PermissionGuardProps {
  permission?: string
  permissions?: string[]
  requireAll?: boolean
  fallback?: ReactNode
  children: ReactNode
}

export function PermissionGuard({
  permission,
  permissions,
  requireAll = false,
  fallback = null,
  children
}: PermissionGuardProps) {
  const { hasPermission, hasAnyPermission, hasAllPermissions } = usePermissions()

  let hasAccess = false

  if (permission) {
    hasAccess = hasPermission(permission)
  } else if (permissions) {
    if (requireAll) {
      hasAccess = hasAllPermissions(permissions)
    } else {
      hasAccess = hasAnyPermission(permissions)
    }
  } else {
    // Se não especificar permissões, sempre permite acesso
    hasAccess = true
  }

  return hasAccess ? <>{children}</> : <>{fallback}</>
}

// Componentes específicos para casos comuns
export function AdminOnly({ children, fallback = null }: { children: ReactNode; fallback?: ReactNode }) {
  const { isAdmin } = usePermissions()
  return isAdmin() ? <>{children}</> : <>{fallback}</>
}

export function ManagerOnly({ children, fallback = null }: { children: ReactNode; fallback?: ReactNode }) {
  const { isManager } = usePermissions()
  return isManager() ? <>{children}</> : <>{fallback}</>
}

export function CanManage({ resource, children, fallback = null }: { 
  resource: string
  children: ReactNode
  fallback?: ReactNode 
}) {
  const { canManage } = usePermissions()
  return canManage(resource) ? <>{children}</> : <>{fallback}</>
}
