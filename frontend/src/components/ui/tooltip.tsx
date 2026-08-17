"use client"

import * as React from "react"
import * as TooltipPrimitive from "@radix-ui/react-tooltip"
import { X } from "lucide-react"

import { cn } from "@/lib/utils"

function TooltipProvider({
  delayDuration = 300,
  skipDelayDuration = 200,
  ...props
}: React.ComponentProps<typeof TooltipPrimitive.Provider>) {
  return (
    <TooltipPrimitive.Provider
      data-slot="tooltip-provider"
      delayDuration={delayDuration}
      skipDelayDuration={skipDelayDuration}
      {...props}
    />
  )
}

const TooltipDismissContext = React.createContext<(() => void) | null>(null)

function Tooltip({
  open: openProp,
  defaultOpen,
  onOpenChange,
  ...props
}: React.ComponentProps<typeof TooltipPrimitive.Root>) {
  const [internalOpen, setInternalOpen] = React.useState(defaultOpen ?? false)
  const open = openProp ?? internalOpen

  const setOpen = React.useCallback(
    (next: boolean) => {
      if (openProp === undefined) {
        setInternalOpen(next)
      }
      onOpenChange?.(next)
    },
    [openProp, onOpenChange],
  )

  return (
    <TooltipDismissContext.Provider value={() => setOpen(false)}>
      <TooltipPrimitive.Root
        data-slot="tooltip"
        open={open}
        onOpenChange={setOpen}
        {...props}
      />
    </TooltipDismissContext.Provider>
  )
}

function TooltipTrigger({
  ...props
}: React.ComponentProps<typeof TooltipPrimitive.Trigger>) {
  return <TooltipPrimitive.Trigger data-slot="tooltip-trigger" {...props} />
}

function TooltipContent({
  className,
  sideOffset = 8,
  side = "top",
  children,
  dismissible = false,
  ...props
}: React.ComponentProps<typeof TooltipPrimitive.Content> & {
  dismissible?: boolean
}) {
  const dismiss = React.useContext(TooltipDismissContext)

  return (
    <TooltipPrimitive.Portal>
      <TooltipPrimitive.Content
        data-slot="tooltip-content"
        side={side}
        sideOffset={sideOffset}
        collisionPadding={8}
        className={cn(
          "bg-foreground text-background animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 w-fit max-w-xs origin-(--radix-tooltip-content-transform-origin) rounded-md px-3 py-1.5 text-xs text-balance",
          className
        )}
        {...props}
      >
        <div className={cn("min-w-0", dismissible && "flex items-start gap-2")}>
          <div className="min-w-0">{children}</div>
          {dismissible && (
            <button
              type="button"
              className="shrink-0 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus-visible:ring-1 focus-visible:ring-background"
              aria-label="Fechar dica"
              onPointerDown={(event) => {
                event.preventDefault()
                event.stopPropagation()
              }}
              onClick={() => dismiss?.()}
            >
              <X className="size-3" />
            </button>
          )}
        </div>
        <TooltipPrimitive.Arrow className="bg-foreground fill-foreground z-50 size-2.5 translate-y-[calc(-50%_-_2px)] rotate-45 rounded-[2px]" />
      </TooltipPrimitive.Content>
    </TooltipPrimitive.Portal>
  )
}

export { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider }
