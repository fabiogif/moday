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
    <div className="relative w-full">
      <Input
        {...props}
        ref={ref}
        type={show ? "text" : "password"}
        disabled={disabled}
        className={cn(
          "pr-9 [&::-ms-reveal]:hidden [&::-ms-clear]:hidden",
          className,
        )}
      />
      <button
        type="button"
        disabled={disabled}
        className="absolute inset-y-0 right-0 z-10 flex w-9 items-center justify-center text-muted-foreground hover:text-foreground disabled:pointer-events-none disabled:opacity-50"
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
