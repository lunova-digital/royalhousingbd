import { ImageIcon, Trash2Icon, UploadIcon } from 'lucide-react'
import { useRef, useState } from 'react'

import { Button } from '@/components/ui/button'
import { ApiError, assetUrl, upload } from '@/lib/api'

interface Stored {
  path: string
  width: number
  height: number
}

// One image, stored via POST /admin/uploads; the value is the stored path.
export function ImageUpload({
  value,
  onChange,
  aspect = 'aspect-video',
}: {
  value: string
  onChange: (path: string) => void
  aspect?: string
}) {
  const input = useRef<HTMLInputElement>(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const pick = async (file: File | undefined) => {
    if (!file) return
    setBusy(true)
    setError(null)
    try {
      const form = new FormData()
      form.append('file', file)
      const stored = await upload<Stored>('/admin/uploads', form)
      onChange(stored.path)
    } catch (err) {
      setError(err instanceof ApiError ? (err.fieldError('file') ?? err.message) : 'Upload failed.')
    } finally {
      setBusy(false)
      if (input.current) input.current.value = ''
    }
  }

  return (
    <div className="grid gap-2">
      <div className={`bg-muted relative w-full max-w-md overflow-hidden rounded-md border ${aspect}`}>
        {value ? (
          <img src={assetUrl(value)} alt="" className="size-full object-cover" />
        ) : (
          <div className="text-muted-foreground flex size-full items-center justify-center">
            <ImageIcon className="size-8" />
          </div>
        )}
      </div>
      <div className="flex gap-2">
        <input ref={input} type="file" accept="image/*" hidden onChange={(e) => void pick(e.target.files?.[0])} />
        <Button type="button" variant="outline" size="sm" disabled={busy} onClick={() => input.current?.click()}>
          <UploadIcon /> {busy ? 'Uploading…' : value ? 'Replace' : 'Upload'}
        </Button>
        {value && (
          <Button type="button" variant="ghost" size="sm" onClick={() => onChange('')}>
            <Trash2Icon /> Remove
          </Button>
        )}
      </div>
      {error && <p className="text-destructive text-sm">{error}</p>}
    </div>
  )
}
