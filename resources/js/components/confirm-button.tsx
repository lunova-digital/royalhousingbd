import { useState, type ComponentProps, type ReactNode } from 'react'

import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

// A button that asks before running a destructive action.
export function ConfirmButton({
  title,
  description,
  confirmLabel = 'Delete',
  onConfirm,
  children,
  ...props
}: ComponentProps<typeof Button> & {
  title: string
  description?: string
  confirmLabel?: string
  onConfirm: () => Promise<void> | void
  children: ReactNode
}) {
  const [open, setOpen] = useState(false)
  const [busy, setBusy] = useState(false)

  const run = async () => {
    setBusy(true)
    try {
      await onConfirm()
      setOpen(false)
    } finally {
      setBusy(false)
    }
  }

  return (
    <>
      <Button type="button" {...props} onClick={() => setOpen(true)}>
        {children}
      </Button>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{title}</DialogTitle>
            {description && <DialogDescription>{description}</DialogDescription>}
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)} disabled={busy}>
              Cancel
            </Button>
            <Button variant="destructive" onClick={() => void run()} disabled={busy}>
              {busy ? 'Working…' : confirmLabel}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  )
}
