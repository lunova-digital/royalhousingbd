// The admin plot builder's map layer (FR-MAP-009): renders plots from React
// state, and turns clicks into new polygons (draw mode) or vertex drags into
// edited ones (edit mode). React owns the data; this class owns Leaflet.
import L from 'leaflet'

import { createPlanMap, plotStyle, type PlanMap, type PlotStatus, type Point } from './plan-map'

export interface EditorPlot {
  key: string
  status: PlotStatus
  number: string
  polygon: Point[]
}

export interface EditorEvents {
  onSelect(key: string | null): void
  onCreate(points: Point[]): void
  onEdit(key: string, points: Point[]): void
  onDrawProgress(points: number): void
}

const handleStyle: L.CircleMarkerOptions = { radius: 6, color: '#111827', weight: 2, fillColor: '#fff', fillOpacity: 1 }
const midStyle: L.CircleMarkerOptions = { radius: 4, color: '#111827', weight: 1, fillColor: '#fff', fillOpacity: 0.7 }

export class PlanEditor {
  private plan: PlanMap
  private layers = new Map<string, L.Polygon>()
  private drawing: { points: L.LatLng[]; markers: L.CircleMarker[]; line: L.Polyline } | null = null
  private editing: { key: string; points: L.LatLng[]; polygon: L.Polygon; handles: L.Layer[] } | null = null

  constructor(
    container: HTMLElement,
    plan: { image: string; width: number; height: number },
    private events: EditorEvents,
  ) {
    this.plan = createPlanMap(container, plan)
    this.plan.map.on('click', (e) => this.onMapClick(e))
    this.plan.map.on('dblclick', () => this.finishDraw())
  }

  get map() {
    return this.plan.map
  }

  fit() {
    this.plan.fit()
  }

  // render syncs the polygons to React's state. The plot being edited keeps
  // its live layer so drag handles survive re-renders.
  render(plots: EditorPlot[], selectedKey: string | null) {
    const seen = new Set<string>()
    for (const plot of plots) {
      seen.add(plot.key)
      if (this.editing?.key === plot.key) continue
      const style = plotStyle(plot.status, plot.key === selectedKey ? 'selected' : 'idle')
      let layer = this.layers.get(plot.key)
      const latlngs = plot.polygon.map(this.plan.toLatLng)
      if (!layer) {
        layer = L.polygon(latlngs, style).addTo(this.plan.map)
        layer.on('click', (e) => {
          L.DomEvent.stopPropagation(e)
          // While drawing, a click over an existing plot still places a
          // vertex — neighbouring plots share edges.
          if (this.drawing) {
            this.onMapClick(e)
            return
          }
          this.events.onSelect(plot.key)
        })
        this.layers.set(plot.key, layer)
      } else {
        layer.setLatLngs(latlngs)
        layer.setStyle(style)
      }
      layer.unbindTooltip()
      if (plot.number) layer.bindTooltip(plot.number, { direction: 'center', permanent: true, className: 'plan-label' })
    }
    for (const [key, layer] of this.layers) {
      if (!seen.has(key)) {
        layer.remove()
        this.layers.delete(key)
      }
    }
  }

  // --- draw mode -----------------------------------------------------------

  get isDrawing() {
    return this.drawing !== null
  }

  startDraw() {
    this.stopEdit()
    this.cancelDraw()
    this.plan.map.doubleClickZoom.disable()
    this.plan.map.getContainer().style.cursor = 'crosshair'
    this.drawing = { points: [], markers: [], line: L.polyline([], { color: '#111827', weight: 2, dashArray: '6 4' }).addTo(this.plan.map) }
    this.events.onDrawProgress(0)
  }

  finishDraw() {
    if (!this.drawing) return
    const points = this.drawing.points.map(this.plan.fromLatLng)
    this.cancelDraw()
    if (points.length >= 3) this.events.onCreate(points)
  }

  cancelDraw() {
    if (!this.drawing) return
    this.drawing.line.remove()
    for (const m of this.drawing.markers) m.remove()
    this.drawing = null
    this.plan.map.doubleClickZoom.enable()
    this.plan.map.getContainer().style.cursor = ''
    this.events.onDrawProgress(-1)
  }

  private onMapClick(e: L.LeafletMouseEvent) {
    if (!this.drawing) {
      if (!this.editing) this.events.onSelect(null)
      return
    }
    const last = this.drawing.points[this.drawing.points.length - 1]
    // A double-click fires two clicks at the same spot; keep one point.
    if (last && this.plan.map.latLngToContainerPoint(last).distanceTo(this.plan.map.latLngToContainerPoint(e.latlng)) < 4) return
    this.drawing.points.push(e.latlng)
    const marker = L.circleMarker(e.latlng, handleStyle).addTo(this.plan.map)
    if (this.drawing.markers.length === 0) {
      // Clicking the first vertex again closes the shape.
      marker.on('click', (ev) => {
        L.DomEvent.stopPropagation(ev)
        this.finishDraw()
      })
    }
    this.drawing.markers.push(marker)
    this.drawing.line.setLatLngs(this.drawing.points)
    this.events.onDrawProgress(this.drawing.points.length)
  }

  // --- edit mode -----------------------------------------------------------

  get editingKey() {
    return this.editing?.key ?? null
  }

  startEdit(key: string, polygon: Point[], status: PlotStatus) {
    this.cancelDraw()
    this.stopEdit()
    const existing = this.layers.get(key)
    existing?.remove()
    this.layers.delete(key)
    const points = polygon.map((p) => L.latLng(this.plan.toLatLng(p) as [number, number]))
    const layer = L.polygon(points, plotStyle(status, 'selected')).addTo(this.plan.map)
    this.editing = { key, points, polygon: layer, handles: [] }
    this.refreshHandles()
  }

  stopEdit() {
    if (!this.editing) return
    for (const h of this.editing.handles) h.remove()
    this.editing.polygon.remove()
    this.editing = null
  }

  private commitEdit() {
    if (!this.editing) return
    this.editing.polygon.setLatLngs(this.editing.points)
    this.events.onEdit(this.editing.key, this.editing.points.map(this.plan.fromLatLng))
  }

  private refreshHandles() {
    const editing = this.editing
    if (!editing) return
    for (const h of editing.handles) h.remove()
    editing.handles = []

    editing.points.forEach((point, index) => {
      const handle = L.marker(point, { draggable: true, icon: dotIcon(handleStyle) }).addTo(this.plan.map)
      handle.on('drag', () => {
        editing.points[index] = handle.getLatLng()
        editing.polygon.setLatLngs(editing.points)
      })
      handle.on('dragend', () => {
        this.commitEdit()
        this.refreshHandles()
      })
      // Right-click (or double-click) removes a vertex while three remain.
      const remove = (ev: L.LeafletEvent) => {
        L.DomEvent.stopPropagation(ev as L.LeafletMouseEvent)
        if (editing.points.length <= 3) return
        editing.points.splice(index, 1)
        this.commitEdit()
        this.refreshHandles()
      }
      handle.on('contextmenu', remove)
      handle.on('dblclick', remove)
      editing.handles.push(handle)
    })

    // Midpoints: drag one to insert a vertex.
    editing.points.forEach((point, index) => {
      const next = editing.points[(index + 1) % editing.points.length]!
      const mid = L.latLng((point.lat + next.lat) / 2, (point.lng + next.lng) / 2)
      const handle = L.marker(mid, { draggable: true, icon: dotIcon(midStyle) }).addTo(this.plan.map)
      let inserted = false
      handle.on('dragstart', () => {
        editing.points.splice(index + 1, 0, handle.getLatLng())
        inserted = true
      })
      handle.on('drag', () => {
        if (!inserted) return
        editing.points[index + 1] = handle.getLatLng()
        editing.polygon.setLatLngs(editing.points)
      })
      handle.on('dragend', () => {
        this.commitEdit()
        this.refreshHandles()
      })
      editing.handles.push(handle)
    })
  }

  destroy() {
    this.cancelDraw()
    this.stopEdit()
    this.plan.destroy()
  }
}

// Vertex handles are markers (draggable), styled as dots.
function dotIcon(style: L.CircleMarkerOptions): L.DivIcon {
  const size = (style.radius ?? 5) * 2
  return L.divIcon({
    className: 'plan-handle',
    iconSize: [size, size],
    iconAnchor: [size / 2, size / 2],
    html: `<span style="display:block;width:${size}px;height:${size}px;border-radius:50%;background:${style.fillColor};border:${style.weight}px solid ${style.color};box-sizing:border-box;opacity:${style.fillOpacity ?? 1}"></span>`,
  })
}
