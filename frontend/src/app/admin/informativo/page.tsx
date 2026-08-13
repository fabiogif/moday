'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { useAdminAuth } from '@/contexts/admin-auth-context'
import adminApi from '@/lib/admin-api-client'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { PageLoading } from '@/components/ui/loading-progress'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { toast } from 'sonner'
import { Mail, Send, Users, Building2, Megaphone } from 'lucide-react'

interface Tenant {
  id: number
  name: string
  subdomain: string
  account_status: string
}

interface NewsletterSubscriber {
  id: number
  email: string
  subscribed_at: string
}

interface NewsletterStats {
  active: number
  total: number
  unsubscribed: number
}

export default function InformativoPage() {
  const router = useRouter()
  const { isAuthenticated, isLoading: authLoading } = useAdminAuth()
  const [tenants, setTenants] = useState<Tenant[]>([])
  const [subscribers, setSubscribers] = useState<NewsletterSubscriber[]>([])
  const [stats, setStats] = useState<NewsletterStats>({ active: 0, total: 0, unsubscribed: 0 })
  const [isLoading, setIsLoading] = useState(true)
  const [selectedTenants, setSelectedTenants] = useState<number[]>([])
  const [sendToNewsletter, setSendToNewsletter] = useState(true)
  const [sendToTenants, setSendToTenants] = useState(false)
  const [subject, setSubject] = useState('')
  const [message, setMessage] = useState('')
  const [sending, setSending] = useState(false)

  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push('/admin/login')
    }
  }, [authLoading, isAuthenticated, router])

  useEffect(() => {
    if (isAuthenticated) {
      loadData()
    }
  }, [isAuthenticated])

  const loadData = async () => {
    try {
      setIsLoading(true)
      const [tenantsResponse, newsletterResponse] = await Promise.all([
        adminApi.getTenants({ per_page: 100 }),
        adminApi.getNewsletterSubscribers(),
      ])

      setTenants(tenantsResponse.data)
      setSubscribers(newsletterResponse.data.subscribers ?? [])
      setStats(newsletterResponse.data.stats ?? { active: 0, total: 0, unsubscribed: 0 })
    } catch {
      toast.error('Erro ao carregar dados do informativo')
    } finally {
      setIsLoading(false)
    }
  }

  const handleSelectTenant = (tenantId: number, checked: boolean) => {
    if (checked) {
      setSelectedTenants((current) => [...current, tenantId])
    } else {
      setSelectedTenants((current) => current.filter((id) => id !== tenantId))
    }
  }

  const handleSelectAllTenants = (checked: boolean) => {
    setSelectedTenants(checked ? tenants.map((tenant) => tenant.id) : [])
  }

  const handleSend = async () => {
    if (!sendToNewsletter && !sendToTenants) {
      toast.error('Selecione pelo menos um público: inscritos ou empresas')
      return
    }

    if (sendToTenants && selectedTenants.length === 0) {
      toast.error('Selecione pelo menos uma empresa')
      return
    }

    if (!subject.trim() || !message.trim()) {
      toast.error('Preencha o assunto e a mensagem')
      return
    }

    setSending(true)

    try {
      const response = await adminApi.sendBulkEmail({
        send_to_newsletter: sendToNewsletter,
        send_to_tenants: sendToTenants,
        tenant_ids: sendToTenants ? selectedTenants : [],
        subject: subject.trim(),
        message: message.trim(),
      })

      if (response.success) {
        toast.success(response.message, {
          description: `${response.data.queued} e-mail(s) enfileirado(s).`,
        })
        setSubject('')
        setMessage('')
        setSelectedTenants([])
      }
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Erro ao enviar informativo')
    } finally {
      setSending(false)
    }
  }

  if (authLoading || !isAuthenticated) {
    return null
  }

  if (isLoading) {
    return <PageLoading message="Carregando informativo..." />
  }

  const estimatedRecipients =
    (sendToNewsletter ? stats.active : 0) + (sendToTenants ? selectedTenants.length : 0)

  return (
    <div className="flex-1 space-y-6 px-6 pt-0">
      <div className="flex flex-col gap-2">
        <h1 className="text-2xl font-bold tracking-tight">Fique por dentro</h1>
        <p className="text-muted-foreground">
          Gerencie inscrições e envie novidades para inscritos no informativo e empresas cadastradas.
        </p>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Inscritos ativos</CardDescription>
            <CardTitle className="text-3xl">{stats.active}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Total de inscrições</CardDescription>
            <CardTitle className="text-3xl">{stats.total}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Empresas cadastradas</CardDescription>
            <CardTitle className="text-3xl">{tenants.length}</CardTitle>
          </CardHeader>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Megaphone className="h-5 w-5" />
            Enviar informativo
          </CardTitle>
          <CardDescription>
            Escolha o público e redija a mensagem que será enviada por e-mail.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="flex items-start gap-3 rounded-lg border p-4">
              <Checkbox
                id="send-newsletter"
                checked={sendToNewsletter}
                onCheckedChange={(checked) => setSendToNewsletter(checked === true)}
              />
              <div className="space-y-1">
                <Label htmlFor="send-newsletter" className="cursor-pointer font-medium">
                  Inscritos no informativo
                </Label>
                <p className="text-sm text-muted-foreground">
                  Enviar para {stats.active} e-mail(s) inscritos na landing page.
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3 rounded-lg border p-4">
              <Checkbox
                id="send-tenants"
                checked={sendToTenants}
                onCheckedChange={(checked) => setSendToTenants(checked === true)}
              />
              <div className="space-y-1">
                <Label htmlFor="send-tenants" className="cursor-pointer font-medium">
                  Empresas cadastradas
                </Label>
                <p className="text-sm text-muted-foreground">
                  Enviar para as empresas selecionadas na lista abaixo.
                </p>
              </div>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="subject">Assunto</Label>
            <Input
              id="subject"
              placeholder="Ex: Novidades Alba Tec para o seu restaurante"
              value={subject}
              onChange={(event) => setSubject(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="message">Mensagem</Label>
            <Textarea
              id="message"
              placeholder="Digite novidades, dicas e atualizações..."
              rows={10}
              value={message}
              onChange={(event) => setMessage(event.target.value)}
              className="font-mono text-sm"
            />
            {sendToTenants && (
              <p className="text-xs text-muted-foreground">
                Para empresas, você pode usar variáveis: {'{name}'}, {'{plan}'}, {'{mrr}'}, {'{expires_at}'}.
              </p>
            )}
          </div>

          <Alert>
            <Mail className="h-4 w-4" />
            <AlertDescription>
              Destinatários estimados: <strong>{estimatedRecipients}</strong>
            </AlertDescription>
          </Alert>

          <Button
            onClick={handleSend}
            disabled={sending || estimatedRecipients === 0 || !subject.trim() || !message.trim()}
            size="lg"
          >
            {sending ? (
              <>
                <Send className="mr-2 h-4 w-4 animate-pulse" />
                Enviando...
              </>
            ) : (
              <>
                <Send className="mr-2 h-4 w-4" />
                Enviar informativo
              </>
            )}
          </Button>
        </CardContent>
      </Card>

      <div className="grid gap-6 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Users className="h-5 w-5" />
              Inscritos no informativo
            </CardTitle>
            <CardDescription>E-mails captados pelo formulário &quot;Fique por dentro&quot;.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>E-mail</TableHead>
                    <TableHead>Inscrito em</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {subscribers.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={2} className="py-8 text-center text-muted-foreground">
                        Nenhum inscrito ainda
                      </TableCell>
                    </TableRow>
                  ) : (
                    subscribers.map((subscriber) => (
                      <TableRow key={subscriber.id}>
                        <TableCell>{subscriber.email}</TableCell>
                        <TableCell>
                          {new Date(subscriber.subscribed_at).toLocaleDateString('pt-BR')}
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div className="flex items-center justify-between gap-4">
              <div>
                <CardTitle className="flex items-center gap-2">
                  <Building2 className="h-5 w-5" />
                  Empresas
                </CardTitle>
                <CardDescription>Selecione empresas quando o envio para empresas estiver ativo.</CardDescription>
              </div>
              <div className="flex items-center gap-2">
                <Checkbox
                  id="select-all-tenants"
                  checked={selectedTenants.length === tenants.length && tenants.length > 0}
                  onCheckedChange={handleSelectAllTenants}
                  disabled={!sendToTenants}
                />
                <Label htmlFor="select-all-tenants" className="cursor-pointer text-sm">
                  Todas
                </Label>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-12" />
                    <TableHead>Empresa</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {tenants.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={3} className="py-8 text-center text-muted-foreground">
                        Nenhuma empresa cadastrada
                      </TableCell>
                    </TableRow>
                  ) : (
                    tenants.map((tenant) => (
                      <TableRow key={tenant.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedTenants.includes(tenant.id)}
                            onCheckedChange={(checked) => handleSelectTenant(tenant.id, checked === true)}
                            disabled={!sendToTenants}
                          />
                        </TableCell>
                        <TableCell>
                          <div className="font-medium">{tenant.name}</div>
                          <div className="text-sm text-muted-foreground">{tenant.subdomain}</div>
                        </TableCell>
                        <TableCell>
                          <Badge variant="secondary">{tenant.account_status}</Badge>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
