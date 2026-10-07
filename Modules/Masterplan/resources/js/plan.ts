// The public interactive map (FR-MAP-002..008). Reads the plan JSON the page
// embedded, loads the SVG layout, paints each detailed plot by status, and
// fills the side panel when one is tapped.
import { createPlanMap, loadLayout, setPlotState, STATUS_LABELS, type PlotStatus } from './plan-map'

interface PlanPlot {
  id: number
  key: string
  number: string
  size: string
  price: string
  position: string
  status: PlotStatus
  whatsapp: string
  ref: string
}

interface Plan {
  layout: string
  width: number
  height: number
  plots: PlanPlot[]
}

const container = document.getElementById('plan-map')
const dataEl = document.getElementById('plan-data')
if (container && dataEl) {
  const plan = JSON.parse(dataEl.textContent ?? '{}') as Plan
  void loadLayout(plan.layout)
    .then((svg) => mount(container, svg, plan))
    .catch((err: unknown) => {
      container.innerHTML = `<p class="p-8 text-center text-sm text-muted-fg">${err instanceof Error ? err.message : 'Could not load the layout.'}</p>`
    })
}

function mount(container: HTMLElement, svg: SVGSVGElement, plan: Plan) {
  svg.classList.add('plan-public')
  const byKey = new Map(plan.plots.map((p) => [p.key, p]))
  let selected: PlanPlot | null = null
  // Legend items toggle statuses on and off (FR-MAP-007).
  const shown = new Set<PlotStatus>(['available', 'booked', 'sold'])

  const plotMap = createPlanMap(container, svg, {
    zoomControl: false,
    onPlotClick: (key) => {
      const plot = key ? byKey.get(key) : undefined
      if (plot) select(plot)
      else if (key === null) deselect()
    },
  })

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

  const restyle = () => {
    for (const [key, group] of plotMap.plots) {
      const plot = byKey.get(key)
      if (!plot) {
        setPlotState(group, null, 'idle', null)
        continue
      }
      const state = !shown.has(plot.status) ? 'dimmed' : selected?.id === plot.id ? 'selected' : 'idle'
      setPlotState(group, plot.status, state, plot.id)
      // Every plot names itself on hover, like a printed layout.
      if (!group.querySelector(':scope > title')) {
        const title = document.createElementNS('http://www.w3.org/2000/svg', 'title')
        title.textContent = `Plot ${plot.number} · ${STATUS_LABELS[plot.status]}`
        group.prepend(title)
      }
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

  restyle()

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
