"use client"

import { useState, useEffect } from "react"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Search, Loader2 } from "lucide-react"
import { useAuthenticatedPermissions, useMutation } from "@/hooks/use-authenticated-api"
import { toast } from "sonner"
import { ScrollArea } from "@/components/ui/scroll-area"
import { endpoints } from "@/lib/api-client"

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost'

interface Permission {
  id: number
  name: string
  slug: string
  description?: string
  module?: string
  action?: string
  resource?: string
}

interface Profile {
  id: number
  name: string
  permissions?: Permission[]
}

interface AssignPermissionsDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  profile: Profile | null
  onSuccess: () => void
}

export function AssignPermissionsDialog({
  open,
  onOpenChange,
  profile,
  onSuccess,
}: AssignPermissionsDialogProps) {
  const [selectedPermissions, setSelectedPermissions] = useState<number[]>([])
  const [searchTerm, setSearchTerm] = useState("")
  const { data: allPermissions, loading: loadingPermissions, refetch: refetchPermissions } = useAuthenticatedPermissions()
  const { mutate: syncPermissions, loading: syncing } = useMutation()

  // Quando abrir o dialog, carregar permissões já vinculadas ao perfil
  useEffect(() => {
    if (open && profile) {
      // Recarregar permissões ao abrir o dialog
      refetchPermissions()
      
      // Se o perfil já tem permissões carregadas, usá-las
      if (profile.permissions && Array.isArray(profile.permissions)) {
        setSelectedPermissions(profile.permissions.map((p) => p.id))
      } else {
        // Caso contrário, buscar do servidor
        fetchProfilePermissions()
      }
    }
  }, [open, profile])

  const fetchProfilePermissions = async () => {
    if (!profile) return

    try {
      const response = await fetch(API_BASE_URL + endpoints.profiles.permissions(profile.id), {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json',
        },
      })

      if (response.ok) {
        const data = await response.json()
        const permissions = data.data || []
        setSelectedPermissions(permissions.map((p: Permission) => p.id))
      }
    } catch (error) {
      console.error('Erro ao carregar permissões do perfil:', error)
    }
  }

  const handleTogglePermission = (permissionId: number) => {
    setSelectedPermissions((prev) =>
      prev.includes(permissionId)
        ? prev.filter((id) => id !== permissionId)
        : [...prev, permissionId]
    )
  }

  const handleSelectAll = () => {
    if (!allPermissions) return
    
    const filtered = filterPermissions()
    const allIds = filtered.map((p) => p.id)
    setSelectedPermissions(allIds)
  }

  const handleDeselectAll = () => {
    setSelectedPermissions([])
  }

  const handleSubmit = async () => {
    if (!profile) return

    try {
      console.log('Vinculando permissões:', { profileId: profile.id, permissionIds: selectedPermissions })
      const result = await syncPermissions(
        endpoints.profiles.syncPermissions(profile.id),
        'PUT',
        { permission_ids: selectedPermissions }
      )

      if (result) {
        toast.success(`Permissões vinculadas ao perfil "${profile.name}" com sucesso!`)
        onSuccess()
        onOpenChange(false)
      }
    } catch (error: any) {
      console.error('Erro ao vincular permissões:', error)
      toast.error(error.message || 'Erro ao vincular permissões')
    }
  }

  const filterPermissions = () => {
    // Extrair array de permissions do objeto retornado pela API
    let permissionsArray: Permission[] = []
    
    if (!allPermissions) {
      return []
    }
    
    // A API retorna { permissions: [...], pagination: {...} }
    // Tentar extrair o array de permissions
    if (Array.isArray(allPermissions)) {
      permissionsArray = allPermissions
    } else if (typeof allPermissions === 'object' && allPermissions !== null) {
      // Tentar acessar a propriedade 'permissions' diretamente
      const perms = (allPermissions as any).permissions
      if (Array.isArray(perms)) {
        permissionsArray = perms
      }
    }
    
    if (!searchTerm.trim()) {
      return permissionsArray
    }

    const search = searchTerm.toLowerCase()
    return permissionsArray.filter(
      (permission: Permission) =>
        permission.name?.toLowerCase().includes(search) ||
        permission.slug?.toLowerCase().includes(search) ||
        permission.description?.toLowerCase().includes(search) ||
        permission.module?.toLowerCase().includes(search)
    )
  }

  const filteredPermissions = filterPermissions()
  const selectedCount = selectedPermissions.length
  const totalCount = filteredPermissions.length

  // Agrupar permissões por módulo
  const groupedPermissions = filteredPermissions.reduce((acc: any, permission: Permission) => {
    const module = permission.module || 'Outros'
    if (!acc[module]) {
      acc[module] = []
    }
    acc[module].push(permission)
    return acc
  }, {})

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl max-h-[90vh]">
        <DialogHeader>
          <DialogTitle>Vincular Permissões ao Perfil</DialogTitle>
          <DialogDescription>
            Selecione as permissões para o perfil <strong>{profile?.name}</strong>
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          {/* Busca */}
          <div className="flex items-center gap-2">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder="Buscar permissões por nome, slug ou módulo..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-9"
              />
            </div>
          </div>

          {/* Contador e Ações */}
          <div className="flex items-center justify-between">
            <div className="text-sm text-muted-foreground">
              {selectedCount} de {totalCount} selecionadas
            </div>
            <div className="flex gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={handleSelectAll}
                disabled={loadingPermissions}
              >
                Selecionar Todas
              </Button>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={handleDeselectAll}
                disabled={loadingPermissions}
              >
                Limpar Seleção
              </Button>
            </div>
          </div>

          {/* Lista de Permissões */}
          <ScrollArea className="h-[400px] rounded-md border p-4">
            {loadingPermissions ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
              </div>
            ) : Object.keys(groupedPermissions).length === 0 ? (
              <div className="text-center py-8 text-muted-foreground">
                {searchTerm ? 'Nenhuma permissão encontrada' : 'Nenhuma permissão disponível'}
              </div>
            ) : (
              <div className="space-y-6">
                {Object.entries(groupedPermissions).map(([module, permissions]: [string, any]) => (
                  <div key={module} className="space-y-3">
                    <div className="flex items-center gap-2">
                      <Badge variant="outline" className="text-xs">
                        {module}
                      </Badge>
                      <span className="text-xs text-muted-foreground">
                        ({permissions.length})
                      </span>
                    </div>
                    <div className="space-y-2 ml-4">
                      {permissions.map((permission: Permission) => (
                        <div
                          key={permission.id}
                          className="flex items-start space-x-3 rounded-lg border p-3 hover:bg-accent transition-colors"
                        >
                          <Checkbox
                            id={`permission-${permission.id}`}
                            checked={selectedPermissions.includes(permission.id)}
                            onCheckedChange={() => handleTogglePermission(permission.id)}
                          />
                          <div className="flex-1 space-y-1">
                            <Label
                              htmlFor={`permission-${permission.id}`}
                              className="text-sm font-medium leading-none cursor-pointer"
                            >
                              {permission.name}
                            </Label>
                            {permission.description && (
                              <p className="text-xs text-muted-foreground">
                                {permission.description}
                              </p>
                            )}
                            <div className="flex gap-2">
                              <Badge variant="secondary" className="text-xs font-mono">
                                {permission.slug}
                              </Badge>
                              {permission.action && (
                                <Badge variant="outline" className="text-xs">
                                  {permission.action}
                                </Badge>
                              )}
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </ScrollArea>
        </div>

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={syncing}
          >
            Cancelar
          </Button>
          <Button
            type="button"
            onClick={handleSubmit}
            disabled={syncing || loadingPermissions}
          >
            {syncing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Salvar Permissões
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
