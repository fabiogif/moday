"use client"

import { ChevronRight, Lock, type LucideIcon } from "lucide-react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import { cn } from "@/lib/utils"

import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible"
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from "@/components/ui/sidebar"

export function NavMain({
  label,
  items,
  locked = false,
}: {
  label: string
  items: {
    title: string
    url: string
    icon?: LucideIcon
    isActive?: boolean
    badge?: number | string
    items?: {
      title: string
      url: string
      isActive?: boolean
    }[]
  }[]
  /** Quando true, módulos ficam visíveis mas sem navegação (trial expirado). */
  locked?: boolean
}) {
  const pathname = usePathname()

  // Check if any subitem is active to determine if parent should be open
  const shouldBeOpen = (item: typeof items[0]) => {
    if (item.isActive) return true
    return item.items?.some(subItem => pathname === subItem.url) || false
  }

  return (
    <SidebarGroup className={cn(locked && "opacity-55")}>
      <SidebarGroupLabel>{label}</SidebarGroupLabel>
      <SidebarMenu>
        {items.map((item) => (
          <Collapsible
            key={item.title}
            asChild
            defaultOpen={locked ? false : shouldBeOpen(item)}
            className="group/collapsible"
          >
            <SidebarMenuItem>
              {item.items?.length ? (
                <>
                  <CollapsibleTrigger asChild>
                    <SidebarMenuButton
                      tooltip={locked ? `${item.title} (bloqueado — assine um plano)` : item.title}
                      className={cn("cursor-pointer", locked && "cursor-not-allowed pointer-events-none")}
                      isActive={!locked && pathname === item.url}
                      disabled={locked}
                      aria-disabled={locked || undefined}
                    >
                      {item.icon && <item.icon />}
                      <span>{item.title}</span>
                      {locked ? (
                        <Lock className="ml-auto h-3.5 w-3.5 text-muted-foreground" aria-hidden />
                      ) : (
                        <>
                          {item.badge != null && (
                            <span className="ml-auto flex h-5 min-w-5 items-center justify-center rounded-md bg-sidebar-primary px-1 text-xs font-medium tabular-nums text-sidebar-primary-foreground">
                              {item.badge}
                            </span>
                          )}
                          <ChevronRight className={cn(
                            "transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90",
                            item.badge == null && "ml-auto"
                          )} />
                        </>
                      )}
                    </SidebarMenuButton>
                  </CollapsibleTrigger>
                  {!locked && (
                    <CollapsibleContent>
                      <SidebarMenuSub>
                        {item.items?.map((subItem) => (
                          <SidebarMenuSubItem key={subItem.title}>
                            <SidebarMenuSubButton asChild className="cursor-pointer" isActive={pathname === subItem.url}>
                              <Link
                                href={subItem.url}
                                target={(item.title === "Auth Pages" || item.title === "Errors") ? "_blank" : undefined}
                                rel={(item.title === "Auth Pages" || item.title === "Errors") ? "noopener noreferrer" : undefined}
                              >
                                <span>{subItem.title}</span>
                              </Link>
                            </SidebarMenuSubButton>
                          </SidebarMenuSubItem>
                        ))}
                      </SidebarMenuSub>
                    </CollapsibleContent>
                  )}
                </>
              ) : locked ? (
                <SidebarMenuButton
                  tooltip={`${item.title} (bloqueado — assine um plano)`}
                  className="cursor-not-allowed opacity-90"
                  disabled
                  aria-disabled
                >
                  {item.icon && <item.icon />}
                  <span>{item.title}</span>
                  <Lock className="ml-auto h-3.5 w-3.5 text-muted-foreground" aria-hidden />
                </SidebarMenuButton>
              ) : (
                <SidebarMenuButton asChild tooltip={item.title} className="cursor-pointer" isActive={pathname === item.url}>
                  <Link href={item.url}>
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                    {item.badge != null && (
                      <span className="ml-auto flex h-5 min-w-5 items-center justify-center rounded-md bg-sidebar-primary px-1 text-xs font-medium tabular-nums text-sidebar-primary-foreground">
                        {item.badge}
                      </span>
                    )}
                  </Link>
                </SidebarMenuButton>
              )}
            </SidebarMenuItem>
          </Collapsible>
        ))}
      </SidebarMenu>
    </SidebarGroup>
  )
}
