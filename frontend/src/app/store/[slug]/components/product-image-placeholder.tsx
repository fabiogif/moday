import { CakeSlice, Coffee, CupSoda, Pizza, Sandwich, Soup } from "lucide-react"
import { cn } from "@/lib/utils"

const ICONS = [Pizza, CupSoda, Sandwich, Soup, CakeSlice, Coffee]

/** Ilustração neutra para produto sem foto — só SVG inline, sem requisição de imagem. */
export function ProductImagePlaceholder({ className }: { className?: string }) {
  return (
    <div
      aria-hidden
      data-testid="product-image-placeholder"
      className={cn("grid h-full w-full grid-cols-3 place-items-center gap-1 bg-muted p-2 text-muted-foreground/25", className)}
    >
      {ICONS.map((Icon, index) => (
        <Icon key={index} className={cn("h-5 w-5", index % 2 === 1 && "translate-y-2")} strokeWidth={1.5} />
      ))}
    </div>
  )
}
