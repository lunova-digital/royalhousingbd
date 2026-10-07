import { FileCodeIcon, UploadIcon } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'

import { Button } from '@/components/ui/button'

import { parseLayout, PLOT_SELECTOR } from '../plan-map'

/** What the browser learns about an SVG before it is uploaded. */
export interface PickedLayout {
  file: File
  svg: SVGSVGElement
  keys: string[]
  width: number
  height: number
  problems: string[]
}

export async function inspectLayout(file: File): Promise<PickedLayout> {
  const problems: string[] = []
  const text = await file.text()
  const svg = parseLayout(text)
  const box = svg.viewBox.baseVal
  let width = box?.width ?? 0
  let height = box?.height ?? 0
  if (!(width > 0 && height > 0)) {
    width = parseFloat(svg.getAttribute('width') ?? '') || 0
    height = parseFloat(svg.getAttribute('height') ?? '') || 0
    if (width > 0 && height > 0) problems.push('The SVG has no viewBox; its width and height are used instead.')
    else problems.push('The SVG needs a viewBox (or a width and height).')
  }
  // The server keys plots plot-N by document order (any id in the file is
  // replaced), so predict the same keys here.
  const keys = [...svg.querySelectorAll(PLOT_SELECTOR)].map((_, i) => `plot-${i + 1}`)
  if (keys.length === 0) problems.push('No plots found. Every plot must be a <g class="plan-plot"> group.')
  if (svg.querySelector('script')) problems.push('Scripts inside the SVG are removed on upload.')
  return { file, svg, keys, width, height, problems }
}

/**
 * Pick an SVG from disk, inspect it in the browser and preview it. A layout is
 * usable when at least one plot group was found and the size is known.
 */
export function LayoutPicker({
  value,
  onChange,
  replaceLabel = 'Choose SVG',
}: {
  value: PickedLayout | null
  onChange: (layout: PickedLayout | null) => void
  replaceLabel?: string
}) {
  const input = useRef<HTMLInputElement>(null)
  const [error, setError] = useState<string | null>(null)
  const [preview, setPreview] = useState<string>('')

  useEffect(() => {
    if (!value) {
      setPreview('')
      return
    }
    const url = URL.createObjectURL(value.file)
    setPreview(url)
    return () => URL.revokeObjectURL(url)
  }, [value])

  const pick = async (file: File | undefined) => {
    if (!file) return
    setError(null)
    try {
      onChange(await inspectLayout(file))
    } catch (err) {
      onChange(null)
      setError(err instanceof Error ? err.message : 'Could not read the file.')
    } finally {
      if (input.current) input.current.value = ''
    }
  }

  const usable = value !== null && value.keys.length > 0 && value.width > 0 && value.height > 0

  return (
    <div className="grid gap-3">
      <div
        className="bg-muted/40 hover:bg-muted/70 flex min-h-56 cursor-pointer items-center justify-center overflow-hidden rounded-md border border-dashed p-2 transition-colors"
        onClick={() => input.current?.click()}
        onDragOver={(e) => e.preventDefault()}
        onDrop={(e) => {
          e.preventDefault()
          void pick(e.dataTransfer.files?.[0])
        }}
        role="button"
        tabIndex={0}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') input.current?.click()
        }}
      >
        {preview ? (
          <img src={preview} alt="" className="max-h-96 w-full object-contain" />
        ) : (
          <div className="text-muted-foreground grid justify-items-center gap-2 text-center text-sm">
            <FileCodeIcon className="size-8" />
            <p>Drop the SVG here or click to choose a file.</p>
          </div>
        )}
      </div>
      <input ref={input} type="file" accept=".svg,image/svg+xml" hidden onChange={(e) => void pick(e.target.files?.[0])} />
      <div className="flex flex-wrap items-center gap-3">
        <Button type="button" variant="outline" size="sm" onClick={() => input.current?.click()}>
          <UploadIcon /> {value ? 'Replace file' : replaceLabel}
        </Button>
        {value && (
          <span className="text-sm">
            <span className="font-medium">{value.file.name}</span>
            <span className="text-muted-foreground">
              {' '}· {value.keys.length} {value.keys.length === 1 ? 'plot' : 'plots'}
              {value.width > 0 && ` · ${Math.round(value.width)} × ${Math.round(value.height)}`}
            </span>
          </span>
        )}
      </div>
      {error && <p className="text-destructive text-sm">{error}</p>}
      {value?.problems.map((p) => (
        <p key={p} className={`text-sm ${usable ? 'text-amber-700' : 'text-destructive'}`}>{p}</p>
      ))}
    </div>
  )
}
