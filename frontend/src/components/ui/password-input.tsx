"use client"

import * as React from "react"
import { Eye, EyeOff } from "lucide-react"
import { Input } from "@/components/ui/input"
import { cn } from "@/lib/utils"

const PasswordInput = React.forwardRef<
  HTMLInputElement,
  Omit<React.ComponentProps<"input">, "type">
>(({ className, disabled, ...props }, ref) => {
  const [show, setShow] = React.useState(false)

  return (
    <div
      className={cn(
        "flex h-9 w-full min-w-0 items-center rounded-md border border-input bg-transparent shadow-xs",
        "focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]",
        className,
      )}
    >
      <Input
        {...props}
        ref={ref}
        type={show ? "text" : "password"}
        disabled={disabled}
        className="h-full min-h-0 flex-1 rounded-none border-0 bg-transparent shadow-none focus-visible:border-0 focus-visible:ring-0 [&::-ms-reveal]:hidden [&::-ms-clear]:hidden"
      />
      <button
        type="button"
        disabled={disabled}
        className="flex h-full w-11 shrink-0 items-center justify-center border-l border-input text-muted-foreground hover:text-foreground disabled:pointer-events-none disabled:opacity-50"
        onClick={() => setShow((prev) => !prev)}
        tabIndex={-1}
        aria-pressed={show}
        aria-label={show ? "Ocultar senha" : "Mostrar senha"}
      >
        {show ? (
          <EyeOff className="size-4" aria-hidden="true" />
        ) : (
          <Eye className="size-4" aria-hidden="true" />
        )}
      </button>
    </div>
  )
})
PasswordInput.displayName = "PasswordInput"

export { PasswordInput }
