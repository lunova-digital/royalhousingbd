// The public interactive map (FR-MAP-002..008). Reads the plan JSON the page
// embedded, loads the SVG layout, paints each detailed plot by status, and
// lists the tapped plots in the side panel, where one WhatsApp message asks
// about all of them at once.
import { createPlanMap, loadLayout, setPlotState, STATUS_LABELS, type PlotStatus } from './plan-map'

interface PlanPlot {
  id: number
  key: string
  number: string
  size: string
  price: string
  position: string
  status: PlotStatus
  ref: string
}

/**
 * The words the panel needs, translated by the page (the public site is
 * bilingual). The message templates keep Laravel's :placeholders, and
 * `selected` is a "singular|plural" pair.
 */
interface PlanLabels extends Record<PlotStatus, string> {
  plot: string
  callForPrice: string
  loadError: string
  selected: string
  removePlot: string
  messageOne: string
  messageMany: string
  messagePlot: string
}

interface Plan {
  layout: string
  width: number
  height: number
  plots: PlanPlot[]
  whatsapp?: { number: string; project: string; url: string }
  labels?: Partial<PlanLabels>
}

const DEFAULT_LABELS: PlanLabels = {
  ...STATUS_LABELS,
  plot: 'Plot',
  callForPrice: 'Call for Price',
  loadError: 'Could not load the layout.',
  selected: ':count plot selected|:count plots selected',
  removePlot: 'Remove plot :number',
  messageOne: "Hello, I'm interested in Plot :number on :project (Ref: :ref). :url",
  messageMany: "Hello, I'm interested in these plots on :project:\n:plots\n:url",
  messagePlot: '- Plot :number (Ref: :ref)',
}

/** Fill a Laravel-style template: ":number" → values.number. */
function fill(template: string, values: Record<string, string>): string {
  return template.replace(/:([a-z]+)/gi, (match, key: string) => values[key] ?? match)
}

/** Pick the branch of a "singular|plural" string for a count. */
function plural(template: string, count: number): string {
  const [one, many] = template.split('|')
  return fill(count === 1 || many === undefined ? one : many, { count: String(count) })
}

/** The click-to-chat link for one or more plots (FR-MAP-008). */
export function whatsappLink(plan: Pick<Plan, 'whatsapp'>, labels: PlanLabels, plots: PlanPlot[]): string {
  const { number = '', project = '', url = '' } = plan.whatsapp ?? {}
  const text =
    plots.length === 1
      ? fill(labels.messageOne, { number: plots[0].number, ref: plots[0].ref, project, url })
      : fill(labels.messageMany, { plots: plots.map((p) => fill(labels.messagePlot, { number: p.number, ref: p.ref })).join('\n'), project, url })
  // Spaces are %20 (not +), which WhatsApp would show literally.
  return `https://wa.me/${number}?text=${encodeURIComponent(text)}`
}

const container = document.getElementById('plan-map')
const dataEl = document.getElementById('plan-data')
if (container && dataEl) {
  const plan = JSON.parse(dataEl.textContent ?? '{}') as Plan
  void loadLayout(plan.layout)
    .then((svg) => mount(container, svg, plan))
    .catch((err: unknown) => {
      const message = err instanceof Error ? err.message : (plan.labels?.loadError ?? 'Could not load the layout.')
      container.innerHTML = `<p class="p-8 text-center text-sm text-muted-fg">${message}</p>`
    })
}

function mount(container: HTMLElement, svg: SVGSVGElement, plan: Plan) {
  svg.classList.add('plan-public')
  const labels: PlanLabels = { ...DEFAULT_LABELS, ...plan.labels }
  const byKey = new Map(plan.plots.map((p) => [p.key, p]))
  // The selection, in the order the plots were tapped.
  const selected = new Map<number, PlanPlot>()
  // Legend items toggle statuses on and off (FR-MAP-007).
  const shown = new Set<PlotStatus>(['available', 'booked', 'sold'])

  const plotMap = createPlanMap(container, svg, {
    zoomControl: false,
    // Tapping a plot adds it to the selection; tapping it again removes it.
    // Tapping the empty map keeps the selection (the panel has a clear button).
    onPlotClick: (key) => {
      const plot = key ? byKey.get(key) : undefined
      if (plot) toggle(plot)
    },
  })

  const panel = {
    empty: document.querySelector<HTMLElement>('[data-plot-empty]'),
    details: document.querySelector<HTMLElement>('[data-plot-details]'),
    count: document.querySelector<HTMLElement>('[data-plot-count]'),
    list: document.querySelector<HTMLElement>('[data-plot-list]'),
    item: document.querySelector<HTMLTemplateElement>('template[data-plot-item]'),
    ref: document.querySelector<HTMLElement>('[data-plot-ref]'),
    whatsapp: document.querySelectorAll<HTMLAnchorElement>('[data-plot-whatsapp]'),
  }
  // The masterplan's name, shown when a plot has no position of its own.
  const defaultPosition = plan.whatsapp?.project ?? ''

  const statusClass: Record<PlotStatus, string> = {
    available: 'border-status-available/30 bg-status-available/10 text-wa-text',
    booked: 'border-status-booked/30 bg-status-booked/10 text-[#92400e]',
    sold: 'border-status-sold/40 bg-status-sold/15 text-muted-fg',
  }

  const restyle = () => {
    for (const [key, group] of plotMap.plots) {
      const plot = byKey.get(key)
      if (!plot) {
        setPlotState(group, null, 'idle', null)
        continue
      }
      const state = !shown.has(plot.status) ? 'dimmed' : selected.has(plot.id) ? 'selected' : 'idle'
      setPlotState(group, plot.status, state, plot.id)
      // Every plot names itself on hover, like a printed layout.
      if (!group.querySelector(':scope > title')) {
        const title = document.createElementNS('http://www.w3.org/2000/svg', 'title')
        title.textContent = `${labels.plot} ${plot.number} · ${labels[plot.status]}`
        group.prepend(title)
      }
    }
  }

  const renderItem = (plot: PlanPlot): HTMLElement => {
    const li = (panel.item?.content.firstElementChild as HTMLElement | null)?.cloneNode(true) as HTMLElement
    const set = (attr: string, text: string) => {
      const el = li.querySelector<HTMLElement>(`[${attr}]`)
      if (el) el.textContent = text
    }
    set('data-item-number', plot.number)
    set('data-item-size', plot.size || '—')
    set('data-item-price', plot.price || labels.callForPrice)
    set('data-item-position', plot.position || defaultPosition || '—')
    const status = li.querySelector<HTMLElement>('[data-item-status]')
    if (status) {
      status.textContent = labels[plot.status] ?? plot.status
      status.classList.add(...(statusClass[plot.status] ?? '').split(' ').filter(Boolean))
    }
    const remove = li.querySelector<HTMLButtonElement>('[data-item-remove]')
    if (remove) {
      remove.setAttribute('aria-label', fill(labels.removePlot, { number: plot.number }))
      remove.addEventListener('click', () => toggle(plot))
    }
    return li
  }

  const render = () => {
    restyle()
    const plots = [...selected.values()]
    if (panel.details) panel.details.hidden = plots.length === 0
    panel.empty?.classList.toggle('lg:hidden', plots.length > 0)
    if (plots.length === 0) return
    if (panel.count) panel.count.textContent = plural(labels.selected, plots.length)
    if (panel.list) panel.list.replaceChildren(...plots.map(renderItem))
    if (panel.ref) panel.ref.textContent = plots.map((p) => p.ref).join(', ')
    const link = whatsappLink(plan, labels, plots)
    for (const a of panel.whatsapp) a.href = link
  }

  const toggle = (plot: PlanPlot) => {
    const added = !selected.has(plot.id)
    if (added) selected.set(plot.id, plot)
    else selected.delete(plot.id)
    render()
  }

  document.querySelector('[data-plot-clear]')?.addEventListener('click', () => {
    selected.clear()
    render()
  })

  render()

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
