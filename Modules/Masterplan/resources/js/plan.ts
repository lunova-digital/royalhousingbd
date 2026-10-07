// The public interactive map (FR-MAP-002..008). Reads the plan JSON the page
// embedded, draws each plot as a tappable polygon, and fills the side panel.
import L from 'leaflet'

import { createPlanMap, plotStyle, STATUS_LABELS, type PlotStatus, type Point } from './plan-map'

interface PlanPlot {
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

interface Plan {
  image: string
  width: number
  height: number
  plots: PlanPlot[]
}

const container = document.getElementById('plan-map')
const dataEl = document.getElementById('plan-data')
if (container && dataEl) {
  const plan = JSON.parse(dataEl.textContent ?? '{}') as Plan
  const plotMap = createPlanMap(container, plan, { zoomControl: false })

  const panel = {
    empty: document.querySelector<HTMLElement>('[data-plot-empty]'),
    details: document.querySelector<HTMLElement>('[data-plot-details]'),
    number: document.querySelector<HTMLElement>('[data-plot-number]'),
    status: document.querySelector<HTMLElement>('[data-plot-status]'),
    size: document.querySelector<HTMLElement>('[data-plot-size]'),
    price: document.querySelector<HTMLElement>('[data-plot-price]'),
    position: document.querySelector<HTMLElement>('[data-plot-position]'),
    ref: document.querySelector<HTMLElement>('[data-plot-ref]'),
    whatsapp: document.querySelectorAll<HTMLAnchorElement>('[data-plot-whatsapp]'),
  }

  const statusClass: Record<PlotStatus, string> = {
    available: 'border-success/20 bg-success-50 text-success',
    booked: 'border-warn/20 bg-warn-50 text-warn',
    sold: 'border-brand/20 bg-brand-50 text-brand',
  }

  let selected: PlanPlot | null = null
  // Legend items toggle statuses on and off (FR-MAP-007).
  const shown = new Set<PlotStatus>(['available', 'booked', 'sold'])
  const layers = new Map<number, L.Polygon>()

  const stateOf = (plot: PlanPlot, hover = false) => {
    if (!shown.has(plot.status)) return 'dimmed'
    if (selected?.id === plot.id) return 'selected'
    return hover ? 'hover' : 'idle'
  }

  const restyle = () => {
    for (const plot of plan.plots) {
      layers.get(plot.id)?.setStyle(plotStyle(plot.status, stateOf(plot)))
    }
  }

  const select = (plot: PlanPlot) => {
    selected = plot
    restyle()
    if (panel.empty) panel.empty.classList.add('lg:hidden')
    if (panel.details) panel.details.hidden = false
    if (panel.number) panel.number.textContent = plot.number
    if (panel.status) {
      panel.status.textContent = STATUS_LABELS[plot.status] ?? plot.status
      panel.status.className = `inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-semibold ${statusClass[plot.status] ?? ''}`
    }
    if (panel.size) panel.size.textContent = plot.size || '—'
    if (panel.price) panel.price.textContent = plot.price || 'Call for Price'
    if (panel.position) panel.position.textContent = plot.position || (panel.position.dataset.default ?? '—')
    if (panel.ref) panel.ref.textContent = plot.ref
    for (const link of panel.whatsapp) link.href = plot.whatsapp
    // Below lg the panel sits under the map; bring it into view.
    if (window.innerWidth < 1024) {
      panel.details?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }
  }

  const deselect = () => {
    selected = null
    restyle()
    if (panel.details) panel.details.hidden = true
    if (panel.empty) panel.empty.classList.remove('lg:hidden')
  }
  document.querySelector('[data-plot-close]')?.addEventListener('click', deselect)
  if (panel.position) panel.position.dataset.default = panel.position.textContent ?? ''

  for (const plot of plan.plots) {
    const polygon = L.polygon(plot.polygon.map(plotMap.toLatLng), plotStyle(plot.status)).addTo(plotMap.map)
    // Every plot shows its number, like a printed layout.
    polygon.bindTooltip(plot.number, { direction: 'center', permanent: true, className: 'plan-label', opacity: 1 })
    polygon.on('click', () => select(plot))
    polygon.on('mouseover', () => polygon.setStyle(plotStyle(plot.status, stateOf(plot, true))))
    polygon.on('mouseout', () => polygon.setStyle(plotStyle(plot.status, stateOf(plot))))
    layers.set(plot.id, polygon)
  }

  // Zoom controls: −, percentage, +, reset (FR-MAP-006).
  const level = document.querySelector<HTMLElement>('[data-plan-zoom-level]')
  const baseZoom = plotMap.map.getZoom()
  const showLevel = () => {
    if (level) level.textContent = `${Math.round(Math.pow(2, plotMap.map.getZoom() - baseZoom) * 100)}%`
  }
  plotMap.map.on('zoomend', showLevel)
  showLevel()
  // Keep the whole layout in view when the viewport changes (orientation, resize).
  let resizeTimer: number | undefined
  window.addEventListener('resize', () => {
    window.clearTimeout(resizeTimer)
    resizeTimer = window.setTimeout(() => {
      plotMap.map.invalidateSize()
      plotMap.fit()
    }, 150)
  })
  for (const button of document.querySelectorAll<HTMLButtonElement>('[data-plan-zoom]')) {
    button.addEventListener('click', () => {
      const action = button.dataset.planZoom
      if (action === 'in') plotMap.map.zoomIn()
      else if (action === 'out') plotMap.map.zoomOut()
      else plotMap.fit()
    })
  }

  for (const button of document.querySelectorAll<HTMLButtonElement>('[data-plan-filter]')) {
    button.addEventListener('click', () => {
      const status = button.dataset.planFilter as PlotStatus
      if (shown.has(status) && shown.size > 1) shown.delete(status)
      else shown.add(status)
      button.setAttribute('aria-pressed', String(shown.has(status)))
      restyle()
    })
  }
}
