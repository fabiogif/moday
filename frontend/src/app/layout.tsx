import type { Metadata, Viewport } from "next";
import "./globals.css";

import { ThemeProvider } from "@/components/theme-provider";
import { SidebarConfigProvider } from "@/contexts/sidebar-context";
import { AuthProvider } from "@/contexts/auth-context";
import { TooltipProvider } from "@/components/ui/tooltip";
import { inter } from "@/lib/fonts";
import { ToasterProvider } from "@/components/toaster-provider";
import { GoogleAnalyticsHead } from "@/components/google-analytics";
import { SITE_DESCRIPTION, SITE_NAME, SITE_URL, LANDING_TITLE } from "@/lib/site-config";
import { SITE_ICON_METADATA } from "@/lib/site-icons";

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: {
    default: LANDING_TITLE,
    template: `%s | ${SITE_NAME}`,
  },
  description: SITE_DESCRIPTION,
  manifest: "/manifest.json",
  icons: SITE_ICON_METADATA,
  robots: {
    index: true,
    follow: true,
  },
};

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  themeColor: "#6400F4",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="pt-BR" className={`${inter.variable} antialiased`} data-scroll-behavior="smooth" suppressHydrationWarning>
      <head>
        <GoogleAnalyticsHead />
      </head>
      <body className={inter.className}>
        <ThemeProvider defaultTheme="system" storageKey="nextjs-ui-theme">
          <TooltipProvider>
          <AuthProvider>
            <SidebarConfigProvider>
              {children}
              <ToasterProvider />
            </SidebarConfigProvider>
          </AuthProvider>
          </TooltipProvider>
        </ThemeProvider>
      </body>
    </html>
  );
}
