// Leaflet over the layout SVG (CRS.Simple): the shared core of the public
// interactive map and the admin plot builder. The SVG is fetched, parsed and
// placed as an svgOverlay covering its viewBox, so every
// <g class="plan-plot"> stays a live DOM node that can be clicked and styled.
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import './plan.css'

export type PlotStatus = 'available' | 'booked' | 'sold'

export type PlotState = 'idle' | 'selected' | 'dimmed'

export const STATUS_COLORS: Record<PlotStatus, string> = {
  available: '#16a34a',
  booked: '#d97706',
  sold: '#94a3b8',
}

export const STATUS_LABELS: Record<PlotStatus, string> = {
  available: 'Available',
  booked: 'Booked',
  sold: 'Sold',
}

export const PLOT_SELECTOR = 'g.plan-plot'

/** Fetch a stored layout and parse it into a detached <svg> element. */
export async function loadLayout(url: string): Promise<SVGSVGElement> {
  const response = await fetch(url, { credentials: 'same-origin' })
  if (!response.ok) throw new Error(`Could not load the layout (${response.status}).`)
  return parseLayout(await response.text())
}

export function parseLayout(markup: string): SVGSVGElement {
  const doc = new DOMParser().parseFromString(markup, 'image/svg+xml')
  const root = doc.documentElement
  if (root.namespaceURI !== 'http://www.w3.org/2000/svg' || root.localName !== 'svg') {
    throw new Error('The file is not an SVG.')
  }
  return document.importNode(root, true) as unknown as SVGSVGElement
}

/** The key of a plot group: the server's data-plot, or the id for unsaved files. */
export function plotKey(group: Element): string {
  return group.getAttribute('data-plot') || group.id
}

/** The text label drawn inside a plot group, e.g. "07". */
export function plotLabel(group: Element): string {
  return (group.querySelector('text')?.textContent ?? '').replace(/\s+/g, ' ').trim()
}

export function layoutSize(svg: SVGSVGElement): { width: number; height: number } {
  const box = svg.viewBox.baseVal
  if (box && box.width > 0 && box.height > 0) return { width: box.width, height: box.height }
  return { width: svg.width.baseVal.value || 1600, height: svg.height.baseVal.value || 1000 }
}

export interface PlanMap {
  map: L.Map
  svg: SVGSVGElement
  width: number
  height: number
  /** Plot groups by key, in document order. */
  plots: Map<string, SVGGElement>
  fit(): void
  destroy(): void
}

export function createPlanMap(
  container: HTMLElement,
  svg: SVGSVGElement,
  options: { zoomControl?: boolean; onPlotClick?: (key: string | null) => void } = {},
): PlanMap {
  const { width, height } = layoutSize(svg)
  const bounds: L.LatLngBoundsExpression = [
    [0, 0],
    [height, width],
  ]

  const map = L.map(container, {
    crs: L.CRS.Simple,
    minZoom: -5,
    maxZoom: 3,
    zoomSnap: 0.25,
    zoomDelta: 0.5,
    attributionControl: false,
    zoomControl: options.zoomControl ?? true,
    // The keyboard handler focuses the container on mousedown, which scrolls
    // a parent pane and shifts the plot under the pointer before mouseup.
    keyboard: false,
    maxBoundsViscosity: 0.8,
  })

  svg.classList.add('plan-layout')
  svg.setAttribute('preserveAspectRatio', 'xMidYMid meet')
  L.svgOverlay(svg, bounds, { interactive: true }).addTo(map)
  map.fitBounds(bounds)
  map.setMaxBounds(L.latLngBounds(bounds).pad(0.5))
  // Do not let the visitor zoom out past the whole layout by much.
  map.setMinZoom(map.getBoundsZoom(bounds) - 1)

  const plots = new Map<string, SVGGElement>()
  for (const group of svg.querySelectorAll<SVGGElement>(PLOT_SELECTOR)) {
    const key = plotKey(group)
    if (key && !plots.has(key)) plots.set(key, group)
  }

  // A click is a press and release without a drag in between; the group under
  // the pointer (if any) is the selection.
  let pressed: { x: number; y: number } | null = null
  const onDown = (e: PointerEvent) => {
    pressed = { x: e.clientX, y: e.clientY }
  }
  const onClick = (e: MouseEvent) => {
    if (!options.onPlotClick) return
    if (pressed && Math.hypot(e.clientX - pressed.x, e.clientY - pressed.y) > 6) return
    const group = (e.target as Element | null)?.closest?.(PLOT_SELECTOR) ?? null
    options.onPlotClick(group ? plotKey(group) : null)
  }
  container.addEventListener('pointerdown', onDown)
  container.addEventListener('click', onClick)

  return {
    map,
    svg,
    width,
    height,
    plots,
    fit: () => map.fitBounds(bounds),
    destroy: () => {
      container.removeEventListener('pointerdown', onDown)
      container.removeEventListener('click', onClick)
      map.remove()
    },
  }
}

const STATUS_CLASSES = ['is-available', 'is-booked', 'is-sold', 'is-empty']

/**
 * Paint a plot group for its status (null = no details yet) and state, and
 * stamp it with the id of its Plot row (data-plot-id) when it has one.
 */
export function setPlotState(group: Element, status: PlotStatus | null, state: PlotState = 'idle', plotId?: number | null): void {
  group.classList.remove(...STATUS_CLASSES, 'is-selected', 'is-dimmed')
  group.classList.add(status ? `is-${status}` : 'is-empty')
  if (state !== 'idle') group.classList.add(`is-${state}`)
  if (plotId) group.setAttribute('data-plot-id', String(plotId))
  else if (plotId === null) group.removeAttribute('data-plot-id')
}
