import { ExternalLinkIcon, FileTextIcon, Trash2Icon, UploadIcon } from 'lucide-react'
import { useRef, useState } from 'react'

import { Button } from '@/components/ui/button'
import { api, ApiError, assetUrl, upload } from '@/lib/api'

import type { Listing } from '../types'

export function fileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

// The listing's PDF brochure: one file, upload (replaces the current one), open, remove.
export function Brochure({ listing, onChange }: { listing: Listing; onChange: (listing: Listing) => void }) {
  const input = useRef<HTMLInputElement>(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const has = listing.brochure_path !== ''

  const add = async (files: FileList | null) => {
    const file = files?.[0]
    if (!file) return
    setBusy(true)
    setError(null)
    try {
      const form = new FormData()
      form.append('file', file)
      const { listing: saved } = await upload<{ listing: Listing }>(`/admin/listings/${listing.id}/brochure`, form)
      onChange({ ...listing, brochure_path: saved.brochure_path, brochure_size: saved.brochure_size })
    } catch (err) {
      setError(err instanceof ApiError ? (err.fieldError('file') ?? err.message) : 'Upload failed.')
    } finally {
      setBusy(false)
      if (input.current) input.current.value = ''
    }
  }

  const remove = async () => {
    setBusy(true)
    setError(null)
    try {
      await api(`/admin/listings/${listing.id}/brochure`, { method: 'DELETE' })
      onChange({ ...listing, brochure_path: '', brochure_size: 0 })
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not remove the brochure.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="grid gap-3">
      {has ? (
        <div className="flex items-center gap-3 rounded-lg border p-3">
          <span className="bg-muted text-muted-foreground flex size-10 shrink-0 items-center justify-center rounded-md">
            <FileTextIcon className="size-5" />
          </span>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium">{listing.slug}-brochure.pdf</p>
            <p className="text-muted-foreground text-xs">PDF · {fileSize(listing.brochure_size)}</p>
          </div>
          <Button type="button" variant="ghost" size="icon-sm" aria-label="Open brochure" nativeButton={false} render={<a href={assetUrl(listing.brochure_path)} target="_blank" rel="noreferrer" />}>
            <ExternalLinkIcon />
          </Button>
          <Button type="button" variant="ghost" size="icon-sm" aria-label="Remove brochure" className="text-destructive hover:text-destructive" disabled={busy} onClick={() => void remove()}>
            <Trash2Icon />
          </Button>
        </div>
      ) : null}
      <Button type="button" variant="outline" disabled={busy} onClick={() => input.current?.click()}>
        <UploadIcon /> {busy ? 'Uploading…' : has ? 'Replace PDF' : 'Upload PDF'}
      </Button>
      <input ref={input} type="file" accept="application/pdf,.pdf" hidden onChange={(e) => void add(e.target.files)} />
      <p className="text-muted-foreground text-xs">A PDF up to 20 MB. Visitors download it from the detail page.</p>
      {error && <p className="text-destructive text-sm">{error}</p>}
    </div>
  )
}
