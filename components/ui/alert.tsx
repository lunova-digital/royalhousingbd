import { cva, type VariantProps } from 'class-variance-authority'
import * as React from 'react'

import { cn } from '@/lib/utils'

const alertVariants = cva('relative w-full rounded-lg border px-4 py-3 text-sm grid gap-0.5', {
  variants: {
    variant: {
      default: 'bg-card text-card-foreground',
      destructive: 'text-destructive bg-card border-destructive/40',
      success: 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100',
    },
  },
  defaultVariants: { variant: 'default' },
})

function Alert({ className, variant, ...props }: React.ComponentProps<'div'> & VariantProps<typeof alertVariants>) {
  return <div role="alert" className={cn(alertVariants({ variant }), className)} {...props} />
}

export { Alert }
