'use client'

import { ChevronLeftIcon, ChevronRightIcon, Trash2Icon, UploadIcon } from 'lucide-react'
import { useRef, useState } from 'react'

import { Button } from '@/components/ui/button'
import { api, ApiError, assetUrl, upload } from '@/modules/admin/lib/api'

import type { ListingImage } from '../types'

// The listing's photo gallery: multi-upload, reorder (first is the cover), delete.
export function Gallery({
  listingId,
  images,
  onChange,
}: {
  listingId: number
  images: ListingImage[]
  onChange: (images: ListingImage[]) => void
}) {
  const input = useRef<HTMLInputElement>(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const add = async (files: FileList | null) => {
    if (!files || files.length === 0) return
    setBusy(true)
    setError(null)
    try {
      const form = new FormData()
      for (const file of files) form.append('files[]', file)
      const { images: added } = await upload<{ images: ListingImage[] }>(`/admin/listings/${listingId}/images`, form)
      onChange([...images, ...added])
    } catch (err) {
      setError(err instanceof ApiError ? (err.fieldError('files') ?? err.message) : 'Upload failed.')
    } finally {
      setBusy(false)
      if (input.current) input.current.value = ''
    }
  }

  const reorder = async (from: number, to: number) => {
    if (to < 0 || to >= images.length) return
    const next = [...images]
    const [moved] = next.splice(from, 1)
    next.splice(to, 0, moved!)
    onChange(next)
    await api(`/admin/listings/${listingId}/images/order`, {
      method: 'PUT',
      body: JSON.stringify({ ids: next.map((i) => i.id) }),
    })
  }

  const remove = async (image: ListingImage) => {
    await api(`/admin/listings/${listingId}/images/${image.id}`, { method: 'DELETE' })
    onChange(images.filter((i) => i.id !== image.id))
  }

  return (
    <div className="grid gap-3">
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        {images.map((image, index) => (
          <figure key={image.id} className="group bg-muted relative aspect-[4/3] overflow-hidden rounded-md border">
            <img src={assetUrl(image.thumb_path || image.path)} alt="" className="size-full object-cover" />
            {index === 0 && <span className="bg-background/90 absolute top-2 left-2 rounded px-1.5 py-0.5 text-xs font-medium">Cover</span>}
            <div className="bg-background/90 absolute inset-x-0 bottom-0 flex justify-between p-1 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
              <Button type="button" variant="ghost" size="icon" aria-label="Move earlier" disabled={index === 0} onClick={() => void reorder(index, index - 1)}>
                <ChevronLeftIcon />
              </Button>
              <Button type="button" variant="ghost" size="icon" aria-label="Delete photo" onClick={() => void remove(image)}>
                <Trash2Icon />
              </Button>
              <Button type="button" variant="ghost" size="icon" aria-label="Move later" disabled={index === images.length - 1} onClick={() => void reorder(index, index + 1)}>
                <ChevronRightIcon />
              </Button>
            </div>
          </figure>
        ))}
        <button
          type="button"
          disabled={busy}
          onClick={() => input.current?.click()}
          className="text-muted-foreground hover:bg-muted flex aspect-[4/3] flex-col items-center justify-center gap-2 rounded-md border border-dashed text-sm"
        >
          <UploadIcon className="size-5" />
          {busy ? 'Uploading…' : 'Add photos'}
        </button>
      </div>
      <input ref={input} type="file" accept="image/*" multiple hidden onChange={(e) => void add(e.target.files)} />
      <p className="text-muted-foreground text-xs">The first photo is the cover. Photos are resized and optimised on upload.</p>
      {error && <p className="text-destructive text-sm">{error}</p>}
    </div>
  )
}
