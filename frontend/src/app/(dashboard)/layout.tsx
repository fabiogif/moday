"use client";

import React from "react";
import { usePathname } from "next/navigation";
import { AppSidebar } from "@/components/app-sidebar";
import { SiteHeader } from "@/components/site-header";
import { SiteFooter } from "@/components/site-footer";
import { SidebarProvider, SidebarInset } from "@/components/ui/sidebar";
import { ThemeCustomizer, ThemeCustomizerTrigger } from "@/components/theme-customizer";
import { useSidebarConfig } from "@/hooks/use-sidebar-config";
import { useAuthSync } from "@/hooks/use-auth-sync";
import { AuthDebug } from "@/components/auth-debug";
import { ForceLogoutButton } from "@/components/force-logout-button";
import { OrderNotificationsProvider } from "@/contexts/order-notifications-context";
import { NotificationsSidebar } from "@/components/notifications/notifications-sidebar";
import { POSHeaderProvider } from "@/contexts/pos-header-context";
import { PlanLimitNotification } from "@/components/plan-limit-notification";
import { AuthGuard } from "@/components/auth-guard";
import { TrialBanner } from "@/components/trial-banner";

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const [themeCustomizerOpen, setThemeCustomizerOpen] = React.useState(false);
  const [notificationsSidebarOpen, setNotificationsSidebarOpen] = React.useState(false);
  const { config } = useSidebarConfig();
  const pathname = usePathname();
  const hideFooter = pathname?.startsWith("/pdv");
  
  // Sincronizar autenticação entre AuthContext e ApiClient
  useAuthSync();

  return (
    <AuthGuard>
    <OrderNotificationsProvider>
      <POSHeaderProvider>
      <SidebarProvider
      style={{
        "--sidebar-width": "16rem",
        "--sidebar-width-icon": "3rem",
        "--header-height": "calc(var(--spacing) * 14)",
      } as React.CSSProperties}
      className={config.collapsible === "none" ? "sidebar-none-mode" : ""}
    >
      {config.side === "left" ? (
        <>
          <div className="print:hidden">
            <AppSidebar
              variant={config.variant}
              collapsible={config.collapsible}
              side={config.side}
            />
          </div>
          <SidebarInset>
            <div className="print:hidden">
              <SiteHeader onNotificationsClick={() => setNotificationsSidebarOpen(true)} />
            </div>
            <div className="flex flex-1 flex-col">
              <div className="@container/main flex flex-1 flex-col gap-2">
                <div className="flex flex-col gap-1 py-1 md:gap-1 md:py-1">
                  <div className="px-4 lg:px-6 print:hidden space-y-2">
                    <TrialBanner />
                    <PlanLimitNotification />
                  </div>
                  {children}
                </div>
              </div>
            </div>
            {!hideFooter && (
              <div className="print:hidden">
                <SiteFooter />
              </div>
            )}
          </SidebarInset>
        </>
      ) : (
        <>
          <SidebarInset>
            <div className="print:hidden">
              <SiteHeader onNotificationsClick={() => setNotificationsSidebarOpen(true)} />
            </div>
            <div className="flex flex-1 flex-col">
              <div className="@container/main flex flex-1 flex-col gap-2">
                <div className="flex flex-col gap-1 py-1 md:gap-1 md:py-1">
                  <div className="px-4 lg:px-6 print:hidden space-y-2">
                    <TrialBanner />
                    <PlanLimitNotification />
                  </div>
                  {children}
                </div>
              </div>
            </div>
            {!hideFooter && (
              <div className="print:hidden">
                <SiteFooter />
              </div>
            )}
          </SidebarInset>
          <div className="print:hidden">
            <AppSidebar
              variant={config.variant}
              collapsible={config.collapsible}
              side={config.side}
            />
          </div>
        </>
      )}

      {/* Notifications Sidebar */}
      <div className="print:hidden">
        <NotificationsSidebar
          open={notificationsSidebarOpen}
          onClose={() => setNotificationsSidebarOpen(false)}
        />
      </div>

      {/* Theme Customizer */}
      {/* <ThemeCustomizerTrigger onClick={() => setThemeCustomizerOpen(true)} /> */}
      {/* <ThemeCustomizer
        open={themeCustomizerOpen}
        onOpenChange={setThemeCustomizerOpen}
      /> */}
      
      {/* Debug de autenticação (apenas em desenvolvimento) */}
      {/* <AuthDebug /> */}
      
      {/* Botão para forçar logout em caso de token inválido */}
    {/* <ForceLogoutButton /> */}
      </SidebarProvider>
      </POSHeaderProvider>
    </OrderNotificationsProvider>
    </AuthGuard>
  );
}
