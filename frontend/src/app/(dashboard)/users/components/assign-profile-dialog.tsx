"use client"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { useToast } from "@/hooks/use-toast"
import { apiClient, endpoints } from "@/lib/api-client"

interface Profile {
  id: number
  name: string
  description: string
  is_active: boolean
}

interface AssignProfileDialogProps {
  userId: number
  userName: string
  currentProfiles?: Profile[]
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess: () => void
}

export function AssignProfileDialog({
  userId,
  userName,
  currentProfiles = [],
  open,
  onOpenChange,
  onSuccess,
}: AssignProfileDialogProps) {
  const [profileId, setProfileId] = useState<string>("")
  const [profiles, setProfiles] = useState<Profile[]>([])
  const [loading, setLoading] = useState(false)
  const [loadingProfiles, setLoadingProfiles] = useState(false)
  const { toast } = useToast()

  useEffect(() => {
    if (open) {
      loadProfiles()
    }
  }, [open])

  const loadProfiles = async () => {
    setLoadingProfiles(true)
    try {
      const response = await apiClient.get<{ profiles: Profile[] }>(
        endpoints.profiles.list
      )

      if (response.success) {
        const data = response.data as any
        const profilesList = data.profiles || data || []
        setProfiles(Array.isArray(profilesList) ? profilesList : [])
      }
    } catch (error) {

      toast({
        title: "Erro",
        description: "Erro ao carregar perfis",
        variant: "destructive",
      })
    } finally {
      setLoadingProfiles(false)
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!profileId) {
      toast({
        title: "Erro",
        description: "Selecione um perfil",
        variant: "destructive",
      })
      return
    }

    setLoading(true)

    try {
      const response = await apiClient.post(
        endpoints.users.assignProfile(userId.toString()),
        { profile_id: parseInt(profileId) }
      )

      if (response.success) {
        toast({
          title: "Sucesso",
          description: "Perfil vinculado com sucesso",
        })
        setProfileId("")
        onOpenChange(false)
        onSuccess()
      } else {
        toast({
          title: "Erro",
          description: response.message || "Erro ao vincular perfil",
          variant: "destructive",
        })
      }
    } catch (error: any) {
      toast({
        title: "Erro",
        description: error.message || "Erro ao vincular perfil",
        variant: "destructive",
      })
    } finally {
      setLoading(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <form onSubmit={handleSubmit}>
          <DialogHeader>
            <DialogTitle>Vincular Perfil</DialogTitle>
            <DialogDescription>
              Vincular perfil ao usuário: {userName}
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            {currentProfiles.length > 0 && (
              <div className="space-y-2">
                <Label>Perfis Atuais</Label>
                <div className="flex flex-wrap gap-2">
                  {currentProfiles.map((profile) => (
                    <div
                      key={profile.id}
                      className="px-2 py-1 bg-primary/10 text-primary rounded-md text-sm"
                    >
                      {profile.name}
                    </div>
                  ))}
                </div>
              </div>
            )}
            <div className="space-y-2">
              <Label htmlFor="profile">Selecione o Perfil</Label>
              <Select
                value={profileId}
                onValueChange={setProfileId}
                disabled={loadingProfiles}
              >
                <SelectTrigger id="profile">
                  <SelectValue
                    placeholder={
                      loadingProfiles
                        ? "Carregando perfis..."
                        : "Selecione um perfil"
                    }
                  />
                </SelectTrigger>
                <SelectContent>
                  {profiles.map((profile) => (
                    <SelectItem key={profile.id} value={profile.id.toString()}>
                      {profile.name}
                      {profile.description && (
                        <span className="text-muted-foreground">
                          {" "}
                          - {profile.description}
                        </span>
                      )}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={loading || loadingProfiles}>
              {loading ? "Vinculando..." : "Vincular Perfil"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
