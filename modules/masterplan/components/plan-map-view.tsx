'use client'

import L from 'leaflet'
import { useEffect, useRef, useState } from 'react'

import { Icon } from '@/modules/site/components/icon'

import { createPlanMap, plotStyle, STATUS_LABELS, type PlanMap as PlanMapCore } from '../plan-map'
import type { PlotStatus, Point } from '../types'

export interface PlanPlot {
  id: number
  number: string
  size: string
  price: string
  position: string
  status: PlotStatus
  polygon: Point[]
  whatsapp: string
  ref: string
}

export interface PlanData {
  image: string
  width: number
  height: number
  plots: PlanPlot[]
}

const STATUS_CLASS: Record<PlotStatus, string> = {
  available: 'border-emerald-500/30 bg-emerald-500/20 text-emerald-400',
  booked: 'border-amber-500/30 bg-amber-500/20 text-amber-400',
  sold: 'border-red-500/30 bg-red-500/20 text-red-400',
}

const LEGEND: { status: PlotStatus; dot: string; label: string }[] = [
  { status: 'available', dot: 'bg-emerald-500', label: 'Available' },
  { status: 'booked', dot: 'bg-amber-500', label: 'Booked' },
  { status: 'sold', dot: 'bg-red-500', label: 'Sold' },
]

/**
 * The public interactive map (FR-MAP-002..008): legend that filters, zoom
 * controls, tappable plot polygons and the side panel with the WhatsApp link.
 */
export function PlanMapView({ plan, name, counts, whatsappEnabled, phone }: { plan: PlanData; name: string; counts: Record<PlotStatus, number>; whatsappEnabled: boolean; phone: string }) {
  const container = useRef<HTMLDivElement>(null)
  const core = useRef<PlanMapCore | null>(null)
  const layers = useRef(new Map<number, L.Polygon>())
  const [selected, setSelected] = useState<PlanPlot | null>(null)
  const [shown, setShown] = useState<Set<PlotStatus>>(() => new Set(['available', 'booked', 'sold']))
  const [zoomLevel, setZoomLevel] = useState(100)
  const hover = useRef<number | null>(null)
  const panelRef = useRef<HTMLDivElement>(null)

  // Mount Leaflet once; polygons are created here and restyled from state below.
  useEffect(() => {
    if (!container.current) return
    const map = createPlanMap(container.current, plan, { zoomControl: false })
    const polygons = layers.current
    core.current = map
    const baseZoom = map.map.getZoom()
    const showLevel = () => setZoomLevel(Math.round(Math.pow(2, map.map.getZoom() - baseZoom) * 100))
    map.map.on('zoomend', showLevel)

    for (const plot of plan.plots) {
      const polygon = L.polygon(plot.polygon.map(map.toLatLng), plotStyle(plot.status)).addTo(map.map)
      // Every plot shows its number, like a printed layout.
      polygon.bindTooltip(plot.number, { direction: 'center', permanent: true, className: 'plan-label', opacity: 1 })
      polygon.on('click', () => setSelected(plot))
      polygon.on('mouseover', () => { hover.current = plot.id; setShown((s) => new Set(s)) })
      polygon.on('mouseout', () => { hover.current = null; setShown((s) => new Set(s)) })
      polygons.set(plot.id, polygon)
    }

    // Keep the whole layout in view when the viewport changes (orientation, resize).
    let timer: number | undefined
    const onResize = () => {
      window.clearTimeout(timer)
      timer = window.setTimeout(() => { map.map.invalidateSize(); map.fit() }, 150)
    }
    window.addEventListener('resize', onResize)
    return () => {
      window.removeEventListener('resize', onResize)
      polygons.clear()
      map.destroy()
      core.current = null
    }
  }, [plan])

  // Restyle: dimmed when filtered out, selected, hovered or idle (FR-MAP-007).
  useEffect(() => {
    for (const plot of plan.plots) {
      const state = !shown.has(plot.status) ? 'dimmed' : selected?.id === plot.id ? 'selected' : hover.current === plot.id ? 'hover' : 'idle'
      layers.current.get(plot.id)?.setStyle(plotStyle(plot.status, state))
    }
  }, [plan, shown, selected])

  // Below lg the panel sits under the map; bring it into view.
  useEffect(() => {
    if (selected && window.innerWidth < 1024) panelRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
  }, [selected])

  const toggle = (status: PlotStatus) => {
    setShown((s) => {
      const next = new Set(s)
      if (next.has(status) && next.size > 1) next.delete(status)
      else next.add(status)
      return next
    })
  }

  return (
    <>
      {/* Legend (FR-MAP-005); each item also filters the map (FR-MAP-007) */}
      <section className="border-b border-navy-500 bg-navy-900 py-3 sm:py-4">
        <div className="container-x">
          <div className="flex flex-wrap items-center justify-center gap-4 sm:gap-8" role="group" aria-label="Plot status">
            {LEGEND.map((item) => (
              <button key={item.status} type="button" aria-pressed={shown.has(item.status)} onClick={() => toggle(item.status)} className="plan-filter flex items-center gap-2 sm:gap-3">
                <span className={`size-3 rounded-full sm:size-4 ${item.dot}`} />
                <span className="text-sm font-medium text-fg sm:text-base">{counts[item.status]} {item.label}</span>
              </button>
            ))}
          </div>
        </div>
      </section>

      <section className="py-8">
        <div className="container-x">
          <div className="grid gap-6 lg:grid-cols-3">
            <div className="order-1 lg:col-span-2">
              <div className="rounded-xl border border-navy-500 bg-navy-800 p-4 sm:p-6">
                <div className="mb-4 flex items-center justify-between">
                  <h3 className="font-display text-base font-bold text-fg sm:text-lg">Project Layout</h3>
                  <div className="flex items-center gap-1.5 rounded-lg bg-navy-600/50 p-1 sm:gap-2">
                    <button type="button" aria-label="Zoom out" onClick={() => core.current?.map.zoomOut()} className="inline-flex size-9 items-center justify-center rounded-lg text-fg transition-all duration-300 hover:bg-navy-600"><Icon name="zoom-out" className="size-4" /></button>
                    <span className="w-12 text-center text-xs font-medium text-muted-fg sm:text-sm">{zoomLevel}%</span>
                    <button type="button" aria-label="Zoom in" onClick={() => core.current?.map.zoomIn()} className="inline-flex size-9 items-center justify-center rounded-lg text-fg transition-all duration-300 hover:bg-navy-600"><Icon name="zoom" className="size-4" /></button>
                    <span className="mx-1 h-6 w-px bg-navy-500" />
                    <button type="button" aria-label="Reset view" onClick={() => core.current?.fit()} className="inline-flex size-9 items-center justify-center rounded-lg text-fg transition-all duration-300 hover:bg-navy-600"><Icon name="reset" className="size-4" /></button>
                  </div>
                </div>
                {/* FR-MAP-002/003/006: the layout, tappable plots, zoom and pan */}
                <div ref={container} className="max-h-[75vh] w-full min-w-0 overflow-hidden rounded-xl border border-navy-500/50 bg-navy-950/30" style={{ aspectRatio: `${Math.max(1, plan.width)} / ${Math.max(1, plan.height)}` }} aria-label="Project map" />
                <p className="mt-4 text-center text-xs text-muted-fg">Tap on any plot to view details. Drag to navigate the map.</p>
              </div>
            </div>

            {/* FR-MAP-004: selected plot details; FR-MAP-008: WhatsApp with map + plot reference */}
            <div className="order-2 lg:col-span-1" ref={panelRef}>
              {!selected ? (
                <div className="hidden rounded-xl border border-navy-500 bg-navy-800 p-8 text-center lg:block">
                  <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-navy-600/50"><Icon name="pin" className="size-8 text-muted-fg" /></div>
                  <h3 className="mb-2 font-display text-xl font-bold text-fg">Select a Plot</h3>
                  <p className="text-sm text-muted-fg">Click on any plot in the map to view its details and pricing information.</p>
                </div>
              ) : (
                <div className="rounded-xl border border-navy-500 bg-navy-800 p-6 lg:sticky lg:top-24">
                  <div className="mb-5 flex items-center justify-between">
                    <h3 className="font-display text-xl font-bold text-fg">Plot {selected.number}</h3>
                    <button type="button" aria-label="Close" onClick={() => setSelected(null)} className="rounded-lg p-2 hover:bg-navy-600"><Icon name="x" className="size-5" /></button>
                  </div>
                  <div className="mb-5 flex aspect-video flex-col items-center justify-center rounded-xl border border-dashed border-navy-500 bg-navy-600/50">
                    <Icon name="pin" className="mb-2 size-10 text-muted-fg/30" />
                    <p className="text-sm text-muted-fg">No images available yet</p>
                    <p className="mt-1 text-xs text-muted-fg/70">Contact us for site photos</p>
                  </div>
                  <div className="mb-5"><span className={`inline-flex items-center gap-1.5 rounded-full border px-4 py-2 text-sm font-semibold ${STATUS_CLASS[selected.status]}`}>{STATUS_LABELS[selected.status]}</span></div>
                  <div className="mb-6 space-y-3">
                    <div className="flex items-center gap-3 rounded-xl bg-navy-600/50 p-4"><div className="rounded-lg bg-brand/10 p-2"><Icon name="expand" className="size-5 text-brand" /></div><div><p className="text-xs text-muted-fg">Plot Size</p><p className="font-semibold text-fg">{selected.size || '—'}</p></div></div>
                    <div className="flex items-center gap-3 rounded-xl bg-navy-600/50 p-4"><div className="rounded-lg bg-brand/10 p-2"><Icon name="tag" className="size-5 text-brand" /></div><div><p className="text-xs text-muted-fg">Price</p><p className="text-lg font-bold text-fg">{selected.price || 'Call for Price'}</p></div></div>
                    <div className="flex items-center gap-3 rounded-xl bg-navy-600/50 p-4"><div className="rounded-lg bg-brand/10 p-2"><Icon name="pin" className="size-5 text-brand" /></div><div><p className="text-xs text-muted-fg">Location</p><p className="font-medium text-fg">{selected.position || name}</p></div></div>
                  </div>
                  <div className="space-y-3">
                    {whatsappEnabled && (
                      <>
                        <a href={selected.whatsapp} target="_blank" rel="noreferrer" className="btn-green h-12 w-full text-base"><Icon name="calendar" className="size-4" /> Book Now</a>
                        <a href={selected.whatsapp} target="_blank" rel="noreferrer" className="btn-primary h-11 w-full"><Icon name="whatsapp" className="size-4" /> Send Inquiry</a>
                      </>
                    )}
                    {phone && <a href={`tel:${phone}`} className="btn-outline h-11 w-full"><Icon name="phone" className="size-4" /> Call Now</a>}
                  </div>
                  <p className="mt-3 text-center text-xs text-muted-fg">Ref: {selected.ref}</p>
                </div>
              )}
            </div>
          </div>
        </div>
      </section>
    </>
  )
}
