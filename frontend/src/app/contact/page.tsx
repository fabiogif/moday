import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { MessageCircle, Mail, ArrowLeft } from "lucide-react"
import Link from "next/link"
import { AlbaTecLogo } from "@/components/albatec-logo"
import { SITE_CONTACT } from "@/lib/site-config"

export default function ContactPage() {
  return (
    <div className="bg-gradient-to-b from-muted/80 via-background to-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
      <div className="w-full max-w-md flex flex-col items-center gap-6">
        <AlbaTecLogo variant="horizontal" height={40} />

        <Card className="w-full shadow-xl">
          <CardHeader className="text-center">
            <CardTitle className="text-2xl font-bold">Fale com o Suporte</CardTitle>
            <CardDescription>
              Estamos aqui para ajudar. Escolha o canal mais rápido para você.
            </CardDescription>
          </CardHeader>

          <CardContent className="space-y-4">
            <a href={SITE_CONTACT.whatsappUrl} target="_blank" rel="noopener noreferrer" className="block">
              <Button className="w-full justify-start gap-3" size="lg" variant="outline">
                <MessageCircle className="h-5 w-5 text-green-600" />
                <div className="text-left">
                  <div className="font-medium">WhatsApp</div>
                  <div className="text-xs text-muted-foreground">{SITE_CONTACT.whatsappDisplay}</div>
                </div>
              </Button>
            </a>

            <a href={`mailto:${SITE_CONTACT.supportEmail}`} className="block">
              <Button className="w-full justify-start gap-3" size="lg" variant="outline">
                <Mail className="h-5 w-5 text-primary" />
                <div className="text-left">
                  <div className="font-medium">E-mail</div>
                  <div className="text-xs text-muted-foreground">{SITE_CONTACT.supportEmail}</div>
                </div>
              </Button>
            </a>

            <div className="pt-2 text-center">
              <Link href="/trial-expired" className="text-sm text-muted-foreground hover:text-primary hover:underline inline-flex items-center gap-1">
                <ArrowLeft className="h-3.5 w-3.5" />
                Voltar
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
