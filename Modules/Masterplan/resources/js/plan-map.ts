// Leaflet over a plain image (CRS.Simple): the shared core of the public
// interactive map and the admin plot builder. Plot polygons are stored in
// image-pixel coordinates (x right, y down); Leaflet's simple CRS has y up,
// so every point is flipped through the image height on the way in and out.
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

export type PlotStatus = 'available' | 'booked' | 'sold'

export interface Point {
  x: number
  y: number
}

export const STATUS_COLORS: Record<PlotStatus, string> = {
  available: '#22c55e',
  booked: '#f59e0b',
  sold: '#ef4444',
}

export const STATUS_LABELS: Record<PlotStatus, string> = {
  available: 'Available',
  booked: 'Booked',
  sold: 'Sold',
}

export interface PlanMap {
  map: L.Map
  width: number
  height: number
  toLatLng(p: Point): L.LatLngExpression
  fromLatLng(ll: L.LatLng): Point
  fit(): void
  destroy(): void
}

export function createPlanMap(
  container: HTMLElement,
  plan: { image: string; width: number; height: number },
  options: { zoomControl?: boolean } = {},
): PlanMap {
  const { width, height } = plan
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
    maxBoundsViscosity: 0.8,
  })

  L.imageOverlay(plan.image, bounds).addTo(map)
  map.fitBounds(bounds)
  map.setMaxBounds(L.latLngBounds(bounds).pad(0.5))
  // Do not let the visitor zoom out past the whole layout by much.
  map.setMinZoom(map.getBoundsZoom(bounds) - 1)

  return {
    map,
    width,
    height,
    toLatLng: (p) => [height - p.y, p.x],
    fromLatLng: (ll) => ({ x: round(ll.lng), y: round(height - ll.lat) }),
    fit: () => map.fitBounds(bounds),
    destroy: () => map.remove(),
  }
}

// Fill/stroke per status and state, matching the reference plot layer:
// idle 0.65 fill + white 0.8 stroke, hover 0.70 + 1.5, selected 0.85 + a
// darker 2px stroke of the same hue, dimmed when filtered out.
const RGB: Record<PlotStatus, [number, number, number]> = {
  available: [34, 197, 94],
  booked: [245, 158, 11],
  sold: [239, 68, 68],
}
const SELECTED_STROKE: Record<PlotStatus, string> = {
  available: '#15803d',
  booked: '#b45309',
  sold: '#b91c1c',
}

export function plotStyle(status: PlotStatus, state: 'idle' | 'hover' | 'selected' | 'dimmed' = 'idle'): L.PathOptions {
  const [r, g, b] = RGB[status] ?? [100, 116, 139]
  const rgba = (a: number) => `rgba(${r}, ${g}, ${b}, ${a})`
  switch (state) {
    case 'selected':
      return { color: SELECTED_STROKE[status] ?? '#334155', weight: 2, lineJoin: 'round', fillColor: rgba(0.85), fillOpacity: 1, opacity: 1 }
    case 'hover':
      return { color: '#ffffff', weight: 1.5, lineJoin: 'round', fillColor: rgba(0.7), fillOpacity: 1, opacity: 1 }
    case 'dimmed':
      return { color: '#ffffff', weight: 0.8, lineJoin: 'round', fillColor: rgba(0.12), fillOpacity: 1, opacity: 0.4 }
    default:
      return { color: '#ffffff', weight: 0.8, lineJoin: 'round', fillColor: rgba(0.65), fillOpacity: 1, opacity: 1 }
  }
}

function round(n: number): number {
  return Math.round(n * 10) / 10
}
