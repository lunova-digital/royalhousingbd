'use client'

import { CheckIcon, MaximizeIcon, PencilIcon, PlusIcon, SaveIcon, Trash2Icon, XIcon } from 'lucide-react'
import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import Link from 'next/link'
import { useParams, useRouter } from 'next/navigation'

import { Field } from '@/modules/admin/components/field'
import { Alert } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { NativeSelect } from '@/components/ui/native-select'
import { api, ApiError, assetUrl } from '@/modules/admin/lib/api'

import { PlanEditor } from '../plan-editor'
import { STATUS_COLORS } from '../plan-map'
import { STATUS_OPTIONS, type Masterplan, type Plot, type PlotStatus, type Point } from '../types'

// A plot in the builder: the server row plus a stable client key (new plots
// have id 0 until saved).
interface DraftPlot {
  key: string
  id: number
  number: string
  size: string
  price: string
  position: string
  status: PlotStatus
  polygon: Point[]
}

type Mode = 'idle' | 'draw' | 'edit'

let nextKey = 1
const keyFor = (p: Plot) => `p${p.id}`
const newKey = () => `n${nextKey++}`

export function PlotBuilder() {
  const { id } = useParams<{ id: string }>()
  const router = useRouter()
  const container = useRef<HTMLDivElement>(null)
  const editor = useRef<PlanEditor | null>(null)
  const [plan, setPlan] = useState<Masterplan | null>(null)
  const [plots, setPlots] = useState<DraftPlot[]>([])
  const [selectedKey, setSelectedKey] = useState<string | null>(null)
  const [mode, setMode] = useState<Mode>('idle')
  const [drawPoints, setDrawPoints] = useState(0)
  const [dirty, setDirty] = useState(false)
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<{ kind: 'success' | 'destructive'; text: string } | null>(null)

  // Load the masterplan.
  useEffect(() => {
    api<{ masterplan: Masterplan }>(`/admin/masterplans/${id}`)
      .then(({ masterplan }) => {
        setPlan(masterplan)
        setPlots((masterplan.plots ?? []).map((p) => ({ ...p, key: keyFor(p) })))
      })
      .catch(() => router.replace('/admin/masterplans'))
  }, [id, router])

  // Mount Leaflet once the image is known.
  useEffect(() => {
    if (!plan?.image_path || !container.current || editor.current) return
    const instance = new PlanEditor(
      container.current,
      { image: assetUrl(plan.image_path), width: plan.image_width, height: plan.image_height },
      {
        onSelect: (key) => {
          setSelectedKey(key)
          setMode('idle')
          instance.stopEdit()
        },
        onCreate: (points) => {
          const draft: DraftPlot = { key: newKey(), id: 0, number: '', size: '', price: '', position: '', status: 'available', polygon: points }
          setPlots((current) => [...current, draft])
          setSelectedKey(draft.key)
          setMode('idle')
          setDirty(true)
        },
        onEdit: (key, points) => {
          setPlots((current) => current.map((p) => (p.key === key ? { ...p, polygon: points } : p)))
          setDirty(true)
        },
        onDrawProgress: (n) => setDrawPoints(n),
      },
    )
    editor.current = instance
    return () => {
      instance.destroy()
      editor.current = null
    }
  }, [plan?.image_path, plan?.image_width, plan?.image_height])

  // Keep Leaflet in sync with React state.
  useEffect(() => {
    editor.current?.render(plots, selectedKey)
  }, [plots, selectedKey])

  // Warn before leaving with unsaved changes.
  useEffect(() => {
    if (!dirty) return
    const handler = (e: BeforeUnloadEvent) => {
      e.preventDefault()
    }
    window.addEventListener('beforeunload', handler)
    return () => window.removeEventListener('beforeunload', handler)
  }, [dirty])

  const selected = useMemo(() => plots.find((p) => p.key === selectedKey) ?? null, [plots, selectedKey])

  const update = (key: string, patch: Partial<DraftPlot>) => {
    setPlots((current) => current.map((p) => (p.key === key ? { ...p, ...patch } : p)))
    setDirty(true)
  }

  const startDraw = () => {
    setSelectedKey(null)
    setMode('draw')
    editor.current?.startDraw()
  }

  const cancelDraw = () => {
    editor.current?.cancelDraw()
    setMode('idle')
  }

  const finishDraw = () => {
    editor.current?.finishDraw()
  }

  const startEdit = () => {
    if (!selected) return
    setMode('edit')
    editor.current?.startEdit(selected.key, selected.polygon, selected.status)
  }

  const stopEdit = () => {
    editor.current?.stopEdit()
    setMode('idle')
  }

  const removeSelected = () => {
    if (!selected) return
    editor.current?.stopEdit()
    setPlots((current) => current.filter((p) => p.key !== selected.key))
    setSelectedKey(null)
    setMode('idle')
    setDirty(true)
  }

  const save = useCallback(async () => {
    if (!plan) return
    editor.current?.stopEdit()
    setMode('idle')
    setBusy(true)
    setMessage(null)
    try {
      const { masterplan } = await api<{ masterplan: Masterplan }>(`/admin/masterplans/${plan.id}/plots`, {
        method: 'PUT',
        body: JSON.stringify({ plots: plots.map((p) => ({ id: p.id, number: p.number, size: p.size, price: p.price, position: p.position, status: p.status, polygon: p.polygon })) }),
      })
      // Re-key from the server so new plots get their ids; keep the selection
      // by matching order.
      const fresh = (masterplan.plots ?? []).map((p) => ({ ...p, key: keyFor(p) }))
      const selectedIndex = plots.findIndex((p) => p.key === selectedKey)
      setPlots(fresh)
      setSelectedKey(selectedIndex >= 0 ? (fresh[selectedIndex]?.key ?? null) : null)
      setPlan(masterplan)
      setDirty(false)
      setMessage({ kind: 'success', text: `Saved ${fresh.length} plots.` })
    } catch (err) {
      const text = err instanceof ApiError ? (Object.values(err.fieldErrors())[0] ?? err.message) : 'Could not save.'
      setMessage({ kind: 'destructive', text })
    } finally {
      setBusy(false)
    }
  }, [plan, plots, selectedKey])

  if (!plan) return <p className="text-muted-foreground text-sm">Loading…</p>

  if (!plan.image_path) {
    return (
      <Alert>
        Upload the layout image first.{' '}
        <Link href={`/admin/masterplans/${plan.id}`} className="underline underline-offset-4">Open the masterplan</Link>
      </Alert>
    )
  }

  return (
    <div className="-m-4 flex h-[calc(100svh-4rem)] flex-col md:-m-6">
      {/* Toolbar */}
      <div className="flex flex-wrap items-center gap-2 border-b px-4 py-2">
        <div className="mr-auto min-w-0">
          <p className="truncate font-semibold">{plan.name}</p>
          <p className="text-muted-foreground text-xs">
            {plots.length} plots {dirty && <span className="text-amber-600">· unsaved changes</span>}
          </p>
        </div>

        {mode === 'draw' ? (
          <>
            <span className="text-muted-foreground text-sm">Click the plot corners on the map ({Math.max(drawPoints, 0)} points). Double-click or click the first point to finish.</span>
            <Button size="sm" onClick={finishDraw} disabled={drawPoints < 3}><CheckIcon /> Finish</Button>
            <Button size="sm" variant="outline" onClick={cancelDraw}><XIcon /> Cancel</Button>
          </>
        ) : mode === 'edit' ? (
          <>
            <span className="text-muted-foreground text-sm">Drag corners to move them, drag a midpoint to add one, right-click a corner to remove it.</span>
            <Button size="sm" onClick={stopEdit}><CheckIcon /> Done</Button>
          </>
        ) : (
          <>
            <Button size="sm" variant="outline" onClick={startDraw}><PlusIcon /> Add plot</Button>
            <Button size="sm" variant="outline" onClick={() => editor.current?.fit()} aria-label="Fit to view"><MaximizeIcon /></Button>
          </>
        )}
        <Button size="sm" onClick={() => void save()} disabled={busy || mode !== 'idle'}><SaveIcon /> {busy ? 'Saving…' : 'Save all'}</Button>
        <Button size="sm" variant="ghost" nativeButton={false} render={<Link href={`/admin/masterplans/${plan.id}`} />}>Back</Button>
      </div>

      {message && (
        <div className="px-4 pt-2">
          <Alert variant={message.kind}>{message.text}</Alert>
        </div>
      )}

      <div className="grid min-h-0 flex-1 grid-cols-1 lg:grid-cols-[1fr_320px]">
        <div ref={container} className="bg-muted min-h-[50svh]" />

        {/* Side panel */}
        <aside className="flex min-h-0 flex-col border-t lg:border-t-0 lg:border-l">
          {selected ? (
            <div className="grid gap-3 border-b p-4">
              <div className="flex items-center justify-between">
                <p className="font-semibold">{selected.id ? `Plot #${selected.id}` : 'New plot'}</p>
                <div className="flex gap-1">
                  {mode === 'edit' ? (
                    <Button size="sm" variant="outline" onClick={stopEdit}><CheckIcon /> Done</Button>
                  ) : (
                    <Button size="sm" variant="outline" onClick={startEdit} disabled={mode === 'draw'}><PencilIcon /> Shape</Button>
                  )}
                  <Button size="icon" variant="ghost" onClick={removeSelected} aria-label="Remove plot"><Trash2Icon /></Button>
                </div>
              </div>
              <Field id="number" label="Plot number">
                <Input id="number" value={selected.number} onChange={(e) => update(selected.key, { number: e.target.value })} placeholder="e.g. A12" autoFocus={!selected.number} />
              </Field>
              <Field id="status" label="Status">
                <NativeSelect id="status" value={selected.status} onChange={(e) => update(selected.key, { status: e.target.value as PlotStatus })}>
                  {STATUS_OPTIONS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                </NativeSelect>
              </Field>
              <Field id="size" label="Size">
                <Input id="size" value={selected.size} onChange={(e) => update(selected.key, { size: e.target.value })} placeholder="e.g. 3 katha" />
              </Field>
              <Field id="price" label="Price" hint="Leave empty for Call for Price.">
                <Input id="price" value={selected.price} onChange={(e) => update(selected.key, { price: e.target.value })} placeholder="e.g. ৳ 35 Lakh / katha" />
              </Field>
              <Field id="position" label="Position / Location">
                <Input id="position" value={selected.position} onChange={(e) => update(selected.key, { position: e.target.value })} placeholder="e.g. Block A, corner, facing north" />
              </Field>
            </div>
          ) : (
            <div className="text-muted-foreground border-b p-4 text-sm">
              {mode === 'draw' ? 'Drawing a new plot…' : 'Select a plot on the map to edit it, or add a new one.'}
            </div>
          )}

          <div className="min-h-0 flex-1 overflow-auto">
            <div className="text-muted-foreground flex items-center gap-3 px-4 py-2 text-xs">
              {STATUS_OPTIONS.map((s) => (
                <span key={s.value} className="inline-flex items-center gap-1"><span className="size-2.5 rounded-sm" style={{ background: STATUS_COLORS[s.value] }} />{s.label}</span>
              ))}
            </div>
            <ul className="divide-y">
              {plots.map((p) => (
                <li key={p.key}>
                  <button
                    type="button"
                    onClick={() => {
                      editor.current?.stopEdit()
                      setMode('idle')
                      setSelectedKey(p.key)
                    }}
                    className={`hover:bg-muted flex w-full items-center justify-between gap-2 px-4 py-2 text-left text-sm ${p.key === selectedKey ? 'bg-muted' : ''}`}
                  >
                    <span className="flex items-center gap-2">
                      <span className="size-2.5 rounded-sm" style={{ background: STATUS_COLORS[p.status] }} />
                      <span className="font-medium">{p.number || <span className="text-muted-foreground italic">unnamed</span>}</span>
                    </span>
                    <span className="text-muted-foreground text-xs">{p.size}</span>
                    {!p.id && <Badge variant="outline">new</Badge>}
                  </button>
                </li>
              ))}
            </ul>
          </div>
        </aside>
      </div>
    </div>
  )
}
