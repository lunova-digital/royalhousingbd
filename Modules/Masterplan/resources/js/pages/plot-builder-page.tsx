import { ChevronRightIcon, MaximizeIcon, SaveIcon, Trash2Icon } from 'lucide-react'
import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Link, useLocation, useNavigate, useParams } from 'react-router'

import { Field } from '@/components/field'
import { Alert } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { NativeSelect } from '@/components/ui/native-select'
import { api, ApiError, assetUrl } from '@/lib/api'

import { Steps } from '../components/steps'
import { createPlanMap, loadLayout, plotLabel, setPlotState, STATUS_COLORS, type PlanMap } from '../plan-map'
import { STATUS_OPTIONS, type Masterplan, type Plot, type PlotStatus } from '../types'

/** A plot's details in the builder, keyed by its SVG group. */
interface Draft {
  id: number
  svg_key: string
  number: string
  size: string
  price: string
  position: string
  status: PlotStatus
}

const toDraft = (p: Plot): Draft => ({ id: p.id, svg_key: p.svg_key, number: p.number, size: p.size, price: p.price, position: p.position, status: p.status })

type Filter = 'all' | 'empty' | 'detailed'

export function PlotBuilderPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const location = useLocation()
  const justCreated = Boolean((location.state as { created?: boolean } | null)?.created)
  const container = useRef<HTMLDivElement>(null)
  const planMap = useRef<PlanMap | null>(null)
  const [plan, setPlan] = useState<Masterplan | null>(null)
  const [drafts, setDrafts] = useState<Record<string, Draft>>({})
  const [labels, setLabels] = useState<Record<string, string>>({})
  const [selectedKey, setSelectedKey] = useState<string | null>(null)
  const [filter, setFilter] = useState<Filter>('all')
  const [dirty, setDirty] = useState(false)
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<{ kind: 'success' | 'destructive'; text: string } | null>(null)
  const [layoutError, setLayoutError] = useState<string | null>(null)

  // Load the masterplan.
  useEffect(() => {
    api<{ masterplan: Masterplan }>(`/admin/masterplans/${id}`)
      .then(({ masterplan }) => {
        setPlan(masterplan)
        setDrafts(Object.fromEntries((masterplan.plots ?? []).map((p) => [p.svg_key, toDraft(p)])))
      })
      .catch(() => navigate('/masterplans', { replace: true }))
  }, [id, navigate])

  // Mount Leaflet with the SVG once the layout is known.
  useEffect(() => {
    const el = container.current
    if (!plan?.image_path || !el) return
    let cancelled = false
    void loadLayout(assetUrl(plan.image_path))
      .then((svg) => {
        if (cancelled) return
        const instance = createPlanMap(el, svg, { onPlotClick: (key) => setSelectedKey(key) })
        planMap.current = instance
        setLabels(Object.fromEntries([...instance.plots].map(([key, g]) => [key, plotLabel(g)])))
        // Trigger a first paint now that the groups exist.
        setDrafts((d) => ({ ...d }))
      })
      .catch((err: unknown) => setLayoutError(err instanceof Error ? err.message : 'Could not load the layout.'))
    return () => {
      cancelled = true
      planMap.current?.destroy()
      planMap.current = null
    }
  }, [plan?.image_path])

  // Keep the SVG in sync with React state.
  useEffect(() => {
    const instance = planMap.current
    if (!instance) return
    for (const [key, group] of instance.plots) {
      setPlotState(group, drafts[key]?.status ?? null, key === selectedKey ? 'selected' : 'idle', drafts[key]?.id || null)
    }
  }, [drafts, selectedKey, labels])

  // Warn before leaving with unsaved changes.
  useEffect(() => {
    if (!dirty) return
    const handler = (e: BeforeUnloadEvent) => {
      e.preventDefault()
    }
    window.addEventListener('beforeunload', handler)
    return () => window.removeEventListener('beforeunload', handler)
  }, [dirty])

  const keys = useMemo(() => plan?.layout_keys ?? [], [plan])
  const selected = selectedKey ? (drafts[selectedKey] ?? null) : null
  const detailedCount = keys.filter((k) => drafts[k]).length

  // Typing into an empty plot's form creates its draft, numbered after the
  // label drawn in the SVG.
  const update = (key: string, patch: Partial<Draft>) => {
    setDrafts((current) => ({
      ...current,
      [key]: { ...(current[key] ?? { id: 0, svg_key: key, number: labels[key] || key, size: '', price: '', position: '', status: 'available' }), ...patch },
    }))
    setDirty(true)
  }

  const clearSelected = () => {
    if (!selectedKey) return
    setDrafts((current) => {
      const next = { ...current }
      delete next[selectedKey]
      return next
    })
    setDirty(true)
  }

  const selectNextEmpty = () => {
    const start = selectedKey ? keys.indexOf(selectedKey) + 1 : 0
    const order = [...keys.slice(start), ...keys.slice(0, start)]
    const next = order.find((k) => !drafts[k]) ?? null
    setSelectedKey(next)
    if (next && planMap.current) {
      const g = planMap.current.plots.get(next)
      g?.scrollIntoView?.({ block: 'nearest' })
    }
  }

  const save = useCallback(async () => {
    if (!plan) return
    setBusy(true)
    setMessage(null)
    try {
      const plots = keys.filter((k) => drafts[k]).map((k) => drafts[k]!)
      const { masterplan } = await api<{ masterplan: Masterplan }>(`/admin/masterplans/${plan.id}/plots`, {
        method: 'PUT',
        body: JSON.stringify({ plots }),
      })
      setPlan(masterplan)
      setDrafts(Object.fromEntries((masterplan.plots ?? []).map((p) => [p.svg_key, toDraft(p)])))
      setDirty(false)
      setMessage({ kind: 'success', text: `Saved ${masterplan.plots.length} of ${keys.length} plots.` })
    } catch (err) {
      const text = err instanceof ApiError ? (Object.values(err.fieldErrors())[0] ?? err.message) : 'Could not save.'
      setMessage({ kind: 'destructive', text })
    } finally {
      setBusy(false)
    }
  }, [plan, keys, drafts])

  if (!plan) return <p className="text-muted-foreground text-sm">Loading…</p>

  if (!plan.image_path) {
    return (
      <Alert>
        This masterplan has no layout.{' '}
        <Link to={`/masterplans/${plan.id}`} className="underline underline-offset-4">Upload one first</Link>
      </Alert>
    )
  }

  const listed = keys.filter((k) => (filter === 'all' ? true : filter === 'empty' ? !drafts[k] : Boolean(drafts[k])))

  return (
    <div className="-m-4 flex h-[calc(100svh-4rem)] flex-col md:-m-6">
      {/* Toolbar */}
      <div className="flex flex-wrap items-center gap-2 border-b px-4 py-2">
        <div className="mr-auto min-w-0">
          <p className="truncate font-semibold">{plan.name}</p>
          <p className="text-muted-foreground text-xs">
            {detailedCount} of {keys.length} plots have details {dirty && <span className="text-amber-600">· unsaved changes</span>}
          </p>
        </div>
        {justCreated && <div className="hidden xl:block"><Steps current={3} /></div>}
        <Button size="sm" variant="outline" onClick={selectNextEmpty} disabled={detailedCount >= keys.length}>Next without details <ChevronRightIcon /></Button>
        <Button size="sm" variant="outline" onClick={() => planMap.current?.fit()} aria-label="Fit to view"><MaximizeIcon /></Button>
        <Button size="sm" onClick={() => void save()} disabled={busy}><SaveIcon /> {busy ? 'Saving…' : 'Save all'}</Button>
        <Button size="sm" variant="ghost" nativeButton={false} render={<Link to={`/masterplans/${plan.id}`} />}>{justCreated ? 'Finish' : 'Back'}</Button>
      </div>

      {(message || layoutError) && (
        <div className="px-4 pt-2">
          <Alert variant={layoutError ? 'destructive' : message!.kind}>{layoutError ?? message!.text}</Alert>
        </div>
      )}

      <div className="grid min-h-0 flex-1 grid-cols-1 lg:grid-cols-[1fr_320px]">
        <div ref={container} className="bg-muted min-h-[50svh]" />

        {/* Side panel */}
        <aside className="flex min-h-0 flex-col border-t lg:border-t-0 lg:border-l">
          {selectedKey ? (
            <div className="grid gap-3 border-b p-4">
              <div className="flex items-center justify-between gap-2">
                <p className="min-w-0 truncate font-semibold" title={selectedKey}>
                  {selected ? `Plot ${selected.number || selectedKey}` : `Plot ${labels[selectedKey] || selectedKey}`}
                  {!selected && <span className="text-muted-foreground ml-2 text-xs font-normal">no details yet</span>}
                </p>
                {selected && (
                  <Button size="icon" variant="ghost" onClick={clearSelected} aria-label="Remove details" title="Remove details"><Trash2Icon /></Button>
                )}
              </div>
              <Field id="number" label="Plot number">
                <Input id="number" value={selected?.number ?? ''} onChange={(e) => update(selectedKey, { number: e.target.value })} placeholder={labels[selectedKey] || 'e.g. A12'} autoFocus />
              </Field>
              <Field id="status" label="Status">
                <NativeSelect id="status" value={selected?.status ?? 'available'} onChange={(e) => update(selectedKey, { status: e.target.value as PlotStatus })}>
                  {STATUS_OPTIONS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                </NativeSelect>
              </Field>
              <Field id="size" label="Size">
                <Input id="size" value={selected?.size ?? ''} onChange={(e) => update(selectedKey, { size: e.target.value })} placeholder="e.g. 3 katha" />
              </Field>
              <Field id="price" label="Price" hint="Leave empty for Call for Price.">
                <Input id="price" value={selected?.price ?? ''} onChange={(e) => update(selectedKey, { price: e.target.value })} placeholder="e.g. ৳ 35 Lakh / katha" />
              </Field>
              <Field id="position" label="Position / Location">
                <Input id="position" value={selected?.position ?? ''} onChange={(e) => update(selectedKey, { position: e.target.value })} placeholder="e.g. Road 14, corner, facing north" />
              </Field>
              {!selected && (
                <Button type="button" size="sm" variant="outline" onClick={() => update(selectedKey, {})}>Add with defaults</Button>
              )}
            </div>
          ) : (
            <div className="text-muted-foreground border-b p-4 text-sm">
              Click a plot on the layout to fill in its details. Greyed, dashed plots have none yet.
            </div>
          )}

          <div className="flex flex-wrap items-center justify-between gap-2 px-4 py-2">
            <div className="text-muted-foreground flex items-center gap-3 text-xs">
              {STATUS_OPTIONS.map((s) => (
                <span key={s.value} className="inline-flex items-center gap-1"><span className="size-2.5 rounded-sm" style={{ background: STATUS_COLORS[s.value] }} />{s.label}</span>
              ))}
            </div>
            <NativeSelect aria-label="Show" className="h-7 w-auto text-xs" value={filter} onChange={(e) => setFilter(e.target.value as Filter)}>
              <option value="all">All</option>
              <option value="empty">Without details</option>
              <option value="detailed">With details</option>
            </NativeSelect>
          </div>
          <div className="min-h-0 flex-1 overflow-auto">
            <ul className="divide-y">
              {listed.map((key) => {
                const d = drafts[key]
                return (
                  <li key={key}>
                    <button
                      type="button"
                      onClick={() => setSelectedKey(key)}
                      className={`hover:bg-muted flex w-full items-center justify-between gap-2 px-4 py-2 text-left text-sm ${key === selectedKey ? 'bg-muted' : ''}`}
                    >
                      <span className="flex min-w-0 items-center gap-2">
                        <span className={`size-2.5 shrink-0 rounded-sm ${d ? '' : 'border border-dashed border-gray-400'}`} style={d ? { background: STATUS_COLORS[d.status] } : undefined} />
                        <span className={`truncate ${d ? 'font-medium' : 'text-muted-foreground'}`}>{d ? d.number || key : labels[key] || key}</span>
                      </span>
                      <span className="text-muted-foreground shrink-0 text-xs">{d ? d.size : <Badge variant="outline">empty</Badge>}</span>
                    </button>
                  </li>
                )
              })}
              {listed.length === 0 && <li className="text-muted-foreground px-4 py-6 text-center text-sm">Nothing to show.</li>}
            </ul>
          </div>
        </aside>
      </div>
    </div>
  )
}
