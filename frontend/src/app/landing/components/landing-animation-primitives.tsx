import { cn } from '@/lib/utils'

export function GlassCard({
  className,
  children,
}: {
  className?: string
  children: React.ReactNode
}) {
  return (
    <div
      className={cn(
        'rounded-2xl border border-zinc-200/90 bg-white/95 shadow-lg shadow-zinc-200/60 backdrop-blur-sm',
        className,
      )}
    >
      {children}
    </div>
  )
}

export function FlowStage({
  className,
  children,
  minHeight = 'min-h-[400px] sm:min-h-[460px]',
  ...props
}: {
  className?: string
  children: React.ReactNode
  minHeight?: string
} & React.ComponentPropsWithoutRef<'div'>) {
  return (
    <div className={cn('relative mx-auto w-full max-w-xl lg:max-w-none', className)} {...props}>
      <div className="hero-glow-drift absolute -inset-6 rounded-[2rem] bg-gradient-to-br from-orange-200/50 via-primary/20 to-violet-200/40 blur-3xl" />
      <div className="hero-glow-drift absolute left-1/4 top-0 h-32 w-32 rounded-full bg-orange-200/40 blur-2xl [animation-delay:2s]" />

      <div
        className={cn(
          'relative overflow-hidden rounded-[1.75rem] border border-zinc-200/90 bg-gradient-to-br from-white via-stone-50 to-orange-50/50 p-5 shadow-xl shadow-orange-100/40 sm:p-7',
          minHeight,
        )}
      >
        <div
          className="absolute inset-0 opacity-[0.35]"
          style={{
            backgroundImage:
              'radial-gradient(circle at 2px 2px, rgba(161,161,170,0.25) 1px, transparent 0)',
            backgroundSize: '22px 22px',
          }}
        />
        <div className="relative h-full">{children}</div>
      </div>
    </div>
  )
}
