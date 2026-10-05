import type { ReactNode } from 'react'

import { Label } from '@/components/ui/label'

// Label + control + hint/error, the one shape every admin form uses.
export function Field({
  id,
  label,
  hint,
  error,
  children,
  className,
}: {
  id?: string
  label: string
  hint?: string
  error?: string
  children: ReactNode
  className?: string
}) {
  return (
    <div className={`grid gap-2 ${className ?? ''}`}>
      <Label htmlFor={id}>{label}</Label>
      {children}
      {error ? (
        <p className="text-destructive text-sm">{error}</p>
      ) : hint ? (
        <p className="text-muted-foreground text-xs">{hint}</p>
      ) : null}
    </div>
  )
}
