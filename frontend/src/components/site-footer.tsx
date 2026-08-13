import { Clock, Users, BarChart3 } from "lucide-react"
import { AlbaTecLogo, PANEL_BRAND_ICON_SIZE } from "@/components/albatec-logo"

export function SiteFooter() {
  return (
    <footer className="border-t bg-background">
      <div className="px-4 py-6 lg:px-6">
        <div className="flex flex-col items-center justify-center space-y-4 text-center">
          <div className="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
            <AlbaTecLogo variant="icon" width={PANEL_BRAND_ICON_SIZE} height={PANEL_BRAND_ICON_SIZE} className="shrink-0" />
            <span>- Sistema de Gestão de Restaurante</span>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-muted-foreground">
            <div className="flex items-center space-x-1">
              <BarChart3 className="h-3 w-3" />
              <span>Gestão de Pedidos</span>
            </div>
            <div className="flex items-center space-x-1">
              <Clock className="h-3 w-3" />
              <span>Controle de Mesas</span>
            </div>
            <div className="flex items-center space-x-1">
              <Users className="h-3 w-3" />
              <span>Gestão de Clientes</span>
            </div>
            <div className="flex items-center space-x-1">
              <BarChart3 className="h-3 w-3" />
              <span>Relatórios Avançados</span>
            </div>
          </div>
          <p className="text-xs text-muted-foreground max-w-md">
            Sistema completo para gestão de restaurantes, com controle de pedidos, mesas, produtos e relatórios em tempo real.
          </p>
        </div>
      </div>
    </footer>
  )
}
