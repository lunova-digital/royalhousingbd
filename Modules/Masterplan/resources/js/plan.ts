// The public plot map (FR-MAP-002..008), rebuilt so the map is the page.
//
//  - The layout is an inline SVG inside one transformed layer, moved by `panzoom` (inertia, pinch, wheel/trackpad at
//    the cursor, double-tap). Panning only changes that layer's CSS transform: no per-move JS state, no re-render.
//  - Portrait screens rotate the whole drawing 90° (its text is counter-rotated so it stays upright).
//  - A tap is a pointer that moved < 6px and lived < 300ms; anything else is a drag and never selects a plot.
//  - Details live in a three-snap bottom sheet on phones and a sticky side panel on desktop.
//  - Every word comes from Blade (data-labels / plan JSON), never from this file.
import createPanZoom, { type PanZoom } from 'panzoom'

import './plan-view.css'

type PlotStatus = 'available' | 'booked' | 'sold'

interface PlanPlot {
  id: number
  key: string
  number: string
  raw: string
  size: string
  sizeRaw: string
  price: string
  position: string
  status: PlotStatus
  ref: string
}

interface Plan {
  slug: string
  name: string
  description: string
  url: string
  layout: string
  width: number
  height: number
  counts: Record<PlotStatus, number>
  plots: PlanPlot[]
  whatsapp: { number: string; project: string; url: string }
}

type Labels = Record<
  | 'available' | 'booked' | 'sold' | 'plot' | 'callForPrice' | 'loadError' | 'selected' | 'selectedTotal' | 'removePlot' | 'plotLabel' | 'plotAria'
  | 'findNone' | 'askSimilar' | 'book' | 'msgOne' | 'msgMany' | 'msgSimilar' | 'msgSimilarMany' | 'msgEach' | 'msgItem' | 'msgItemStatus'
  | 'msgItemNoSize' | 'msgItemStatusNoSize' | 'msgMap' | 'notePlots' | 'fullScreen' | 'exitFullScreen',
  string
>

interface PlotEl {
  data: PlanPlot
  g: SVGGElement
  cx: number
  cy: number
  w: number
  h: number
}

const SVG_NS = 'http://www.w3.org/2000/svg'
const TAP_SLOP = 6
const TAP_MS = 300
const MIN_PLOT_PX = 28
const MAX_ZOOM_X = 6
const STATUSES: PlotStatus[] = ['available', 'booked', 'sold']

const root = document.querySelector<HTMLElement>('[data-plan-app]')
if (root) void boot(root)

// ---- helpers -------------------------------------------------------------------------------------------------------

const fill = (template: string, values: Record<string, string | number>) => template.replace(/:([a-z]+)/gi, (m, k: string) => String(values[k] ?? m))
const plural = (template: string, count: number, values: Record<string, string | number> = {}) => {
  const forms = template.split('|')
  return fill(count !== 1 && forms[1] ? forms[1] : forms[0]!, { count, ...values })
}
const bn = () => document.documentElement.lang === 'bn'
const esc = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
// the ৳ sign sits smaller and lighter than the number
const currency = (s: string) => esc(s).replace(/৳/g, '<span class="cur">৳</span>')
const digits = (s: string) => (bn() ? s.replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[Number(d)]!) : s)
const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
const clamp = (v: number, lo: number, hi: number) => Math.min(hi, Math.max(lo, v))
const ease = (t: number) => (t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2)

function parseLayout(markup: string): SVGSVGElement {
  const doc = new DOMParser().parseFromString(markup, 'image/svg+xml')
  const el = doc.documentElement
  if (el.namespaceURI !== SVG_NS || el.localName !== 'svg') throw new Error('The file is not an SVG.')
  return document.importNode(el, true) as unknown as SVGSVGElement
}

// ---- the map -------------------------------------------------------------------------------------------------------

async function boot(app: HTMLElement) {
  const q = <T extends HTMLElement>(sel: string) => app.querySelector<T>(sel)!
  const labels = JSON.parse(app.dataset.labels ?? '{}') as Labels
  const endpoint = app.dataset.endpoint ?? '/map'
  const viewport = q('[data-viewport]')
  const pan = q('[data-pan]')
  const rot = q('[data-rot]')
  const skeleton = q('[data-skeleton]')
  const errorBox = q('[data-plan-error]')
  const panel = q('[data-panel]')
  const grip = q('[data-grip]')
  const top = q('[data-plan-top]')
  const compass = q('[data-compass-arrow]')
  const coach = q('[data-coach]')
  const mobile = window.matchMedia('(max-width: 1023.98px)')
  const portrait = window.matchMedia('(max-width: 767.98px), (orientation: portrait)')

  let plan = JSON.parse(document.getElementById('plan-data')?.textContent ?? '{}') as Plan
  let svg: SVGSVGElement | null = null
  let instance: PanZoom | null = null
  let plots = new Map<string, PlotEl>()
  let byId = new Map<number, PlotEl>()
  let texts: { wrap: SVGGElement; a: number; cx: number; cy: number }[] = []
  let topLayer: SVGGElement | null = null
  let layoutW = 1
  let layoutH = 1
  let shortSide = 40
  let rotated = false
  let fit = 1
  let viewW = 1
  let viewH = 1
  const selected = new Map<number, PlotEl>()
  const shown = new Set<PlotStatus>(STATUSES)
  let labelScale = 1
  let loadToken = 0
  let rerasterTimer = 0

  // ---- layout sizing --------------------------------------------------------------------------------------------

  const fullscreen = () => app.classList.contains('plan-fs')

  function sizeStage() {
    const header = fullscreen() ? 0 : (document.querySelector<HTMLElement>('body > header')?.offsetHeight ?? 64)
    const nav = mobile.matches && !fullscreen() ? (document.querySelector<HTMLElement>('body > nav.fixed')?.offsetHeight ?? 64) : 0
    const gap = mobile.matches ? 0 : 48
    const h = window.innerHeight - header - top.offsetHeight - nav - gap
    app.style.setProperty('--plan-h', `${Math.max(mobile.matches ? 340 : 520, Math.round(h))}px`)
  }

  // ---- loading a plan -------------------------------------------------------------------------------------------

  async function load(next: Plan, { keepUrl = false } = {}) {
    const token = ++loadToken
    plan = next
    skeleton.hidden = false
    errorBox.hidden = true
    teardown()
    try {
      const response = await fetch(plan.layout, { credentials: 'same-origin' })
      if (!response.ok) throw new Error(String(response.status))
      const parsed = parseLayout(await response.text())
      if (token !== loadToken) return
      mount(parsed)
      skeleton.hidden = true
    } catch {
      if (token !== loadToken) return
      skeleton.hidden = true
      errorBox.textContent = labels.loadError
      errorBox.hidden = false
    }
    if (!keepUrl) syncChrome()
  }

  function teardown() {
    instance?.dispose()
    instance = null
    selected.clear()
    plots = new Map()
    byId = new Map()
    texts = []
    topLayer = null
    svg = null
    rot.replaceChildren()
    render()
  }

  function mount(node: SVGSVGElement) {
    const view = node.viewBox.baseVal
    layoutW = view && view.width > 0 ? view.width : node.width.baseVal.value || plan.width || 1600
    layoutH = view && view.height > 0 ? view.height : node.height.baseVal.value || plan.height || 1000
    node.setAttribute('width', String(layoutW))
    node.setAttribute('height', String(layoutH))
    node.removeAttribute('preserveAspectRatio')
    node.classList.add('plan')

    // Sold plots are hatched as well as grey, so the map does not rely on colour alone.
    const defs = document.createElementNS(SVG_NS, 'defs')
    defs.innerHTML =
      '<pattern id="plan-hatch" width="7" height="7" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect width="7" height="7" fill="#94a3b8"/><line x1="0" y1="0" x2="0" y2="7" stroke="#64748b" stroke-width="2.4"/></pattern>'
    node.prepend(defs)
    topLayer = document.createElementNS(SVG_NS, 'g')
    topLayer.setAttribute('class', 'plan-top-layer')
    node.append(topLayer)

    rot.replaceChildren(node)
    svg = node
    app.classList.add('is-booting')

    // Plots: match each group to its row, measure it, give it a padded hit area, a name and a place in the tab order.
    const byKey = new Map(plan.plots.map((p) => [p.key, p]))
    const found: PlotEl[] = []
    let i = 0
    for (const g of node.querySelectorAll<SVGGElement>('g.plan-plot')) {
      const key = g.getAttribute('data-plot') || g.id
      const data = byKey.get(key)
      g.style.setProperty('--i', String(i++))
      if (!data) {
        g.classList.add('is-empty')
        continue
      }
      const box = g.getBBox()
      const hit = document.createElementNS(SVG_NS, 'rect')
      hit.setAttribute('class', 'plan-hit')
      hit.setAttribute('x', String(box.x - 2))
      hit.setAttribute('y', String(box.y - 2))
      hit.setAttribute('width', String(box.width + 4))
      hit.setAttribute('height', String(box.height + 4))
      g.prepend(hit)
      g.setAttribute('tabindex', '0')
      g.setAttribute('role', 'button')
      g.setAttribute('data-plot-id', String(data.id))
      const label = g.querySelector('text')
      if (label) label.style.setProperty('--fs', label.getAttribute('font-size') ?? '12')
      const el: PlotEl = { data, g, cx: box.x + box.width / 2, cy: box.y + box.height / 2, w: box.width, h: box.height }
      plots.set(key, el)
      byId.set(data.id, el)
      found.push(el)
      paint(el)
    }
    const sides = found.map((p) => Math.min(p.w, p.h)).sort((a, b) => a - b)
    shortSide = sides.length ? sides[Math.floor(sides.length / 2)]! : 40

    // Text that sits in the drawing: remember its angle so it can be kept upright when the map is rotated.
    texts = Array.from(node.querySelectorAll<SVGTextElement>('text')).map((t) => {
      const ctm = t.getCTM()
      const a = ctm ? (Math.atan2(ctm.b, ctm.a) * 180) / Math.PI : 0
      const bb = t.getBBox()
      const own = t.transform.baseVal.numberOfItems ? t.transform.baseVal.consolidate()!.matrix : null
      const mx = bb.x + bb.width / 2
      const my = bb.y + bb.height / 2
      const cx = own ? own.a * mx + own.c * my + own.e : mx
      const cy = own ? own.b * mx + own.d * my + own.f : my
      const wrap = document.createElementNS(SVG_NS, 'g')
      t.parentNode!.insertBefore(wrap, t)
      wrap.append(t)
      return { wrap, a, cx, cy }
    })

    layout(true)
    window.setTimeout(() => app.classList.remove('is-booting'), 900)
    showCoach()
  }

  // ---- rotation, fit and the pan/zoom engine --------------------------------------------------------------------

  function applyRotation() {
    rotated = portrait.matches
    const panW = rotated ? layoutH : layoutW
    const panH = rotated ? layoutW : layoutH
    pan.style.width = `${panW}px`
    pan.style.height = `${panH}px`
    rot.style.width = `${layoutW}px`
    rot.style.height = `${layoutH}px`
    rot.style.transform = rotated ? `translate(${layoutH}px, 0) rotate(90deg)` : ''
    for (const t of texts) t.wrap.setAttribute('transform', rotated ? `rotate(${-(t.a + 90)} ${t.cx} ${t.cy})` : '')
    compass.style.transform = rotated ? 'rotate(90deg)' : ''
    // Map coordinates → pan-layer coordinates
    return { panW, panH }
  }

  const toPan = (x: number, y: number) => (rotated ? { x: layoutH - y, y: x } : { x, y })

  function layout(initial: boolean) {
    if (!svg) return
    sizeStage()
    const wasRotated = rotated
    const { panW, panH } = applyRotation()
    viewW = viewport.clientWidth
    viewH = viewport.clientHeight
    if (viewW < 40 || viewH < 40) return
    fit = Math.min(viewW / panW, viewH / panH)
    const max = fit * MAX_ZOOM_X

    if (!instance) {
      instance = createPanZoom(pan, {
        minZoom: fit,
        maxZoom: max,
        bounds: true,
        boundsPadding: 0.35,
        smoothScroll: !reduced(),
        zoomDoubleClickSpeed: 2.2,
        zoomSpeed: 0.09,
        pinchSpeed: 1.1,
        disableKeyboardInteraction: true,
        enableTextSelection: false,
        beforeMouseDown: (e) => {
          viewport.classList.add('is-grabbing')
          return e.button !== 0 && e.button !== 1
        },
      })
      instance.on('transform', onTransform)
      instance.on('panend', () => viewport.classList.remove('is-grabbing'))
      window.addEventListener('mouseup', () => viewport.classList.remove('is-grabbing'))
    } else {
      instance.setMinZoom(fit)
      instance.setMaxZoom(max)
    }
    if (initial || wasRotated !== rotated) initialView()
    else clampView()
    onTransform()
  }

  function clampView() {
    const t = instance!.getTransform()
    if (t.scale < fit) goTo(0, 0, fit)
  }

  function goTo(x: number, y: number, scale: number) {
    if (!instance) return
    instance.zoomAbs(0, 0, scale)
    instance.moveTo(x, y)
  }

  // Phones start close enough that an average plot is ≥ 28px on its short side, centred on the busiest patch of
  // available plots; desktop starts fitted to the whole masterplan.
  function initialView() {
    if (!instance) return
    let scale = fit
    if (mobile.matches) scale = clamp(Math.max(fit, MIN_PLOT_PX / shortSide), fit, fit * MAX_ZOOM_X)
    let focus = { x: (rotated ? layoutH : layoutW) / 2, y: (rotated ? layoutW : layoutH) / 2 }
    if (scale > fit * 1.02) {
      const winW = viewW / scale
      const winH = viewH / scale
      const avail = [...plots.values()].filter((p) => p.data.status === 'available').map((p) => toPan(p.cx, p.cy))
      let best = -1
      for (const c of avail) {
        const n = avail.filter((p) => Math.abs(p.x - c.x) <= winW / 2 && Math.abs(p.y - c.y) <= winH / 2).length
        if (n > best) {
          best = n
          focus = c
        }
      }
    }
    goTo(viewW / 2 - focus.x * scale, viewH / 2 - focus.y * scale, scale)
  }

  function animateTo(x: number, y: number, scale: number, ms = 300) {
    if (!instance) return
    const from = instance.getTransform()
    if (reduced() || ms === 0) return goTo(x, y, scale)
    const start = performance.now()
    const step = (now: number) => {
      const t = ease(clamp((now - start) / ms, 0, 1))
      goTo(from.x + (x - from.x) * t, from.y + (y - from.y) * t, from.scale + (scale - from.scale) * t)
      if (t < 1) requestAnimationFrame(step)
    }
    requestAnimationFrame(step)
  }

  // Where the sheet's top edge will be once it has settled (its CSS transition may still be running)
  const sheetTop = () => (mobile.matches && panel.dataset.state !== 'empty' ? window.innerHeight - panel.offsetHeight + snapY(panel.dataset.state as Snap) : Infinity)

  // Keep a plot clear of the controls and the bottom sheet.
  function reveal(el: PlotEl, smooth = true) {
    if (!instance) return
    const r = el.g.getBoundingClientRect()
    const v = viewport.getBoundingClientRect()
    const topEdge = v.top + 64
    const bottomEdge = Math.min(v.bottom, sheetTop()) - 20
    const leftEdge = v.left + 20
    const rightEdge = v.right - 64
    let dx = 0
    let dy = 0
    if (r.bottom > bottomEdge) dy = bottomEdge - r.bottom
    else if (r.top < topEdge) dy = topEdge - r.top
    if (r.right > rightEdge) dx = rightEdge - r.right
    else if (r.left < leftEdge) dx = leftEdge - r.left
    if (dx || dy) instance.moveBy(dx, dy, smooth && !reduced())
  }

  function focusOn(el: PlotEl, minScale: number) {
    if (!instance) return
    const t = instance.getTransform()
    const scale = clamp(Math.max(t.scale, minScale), fit, fit * MAX_ZOOM_X)
    const p = toPan(el.cx, el.cy)
    const areaH = Math.min(viewH, Math.max(120, sheetTop() - viewport.getBoundingClientRect().top))
    animateTo(viewW / 2 - p.x * scale, areaH / 2 - p.y * scale, scale)
  }

  // Zoom changes: scale the plot numbers so they stay readable (hidden when plots are too small), keep badges sized.
  function onTransform() {
    if (!svg || !instance) return
    const s = instance.getTransform().scale
    const ls = Math.round(clamp(11 / (12 * s), 1, 2.4) * 20) / 20
    if (ls !== labelScale) {
      labelScale = ls
      app.style.setProperty('--ls', String(ls))
      placeBadges()
    }
    app.toggleAttribute('data-labels-hidden', shortSide * s < 15)
    app.toggleAttribute('data-roads-hidden', 16 * s < 6.5)
    // will-change:transform pins the raster scale; drop it for a frame after zooming so the vectors re-render sharp.
    window.clearTimeout(rerasterTimer)
    rerasterTimer = window.setTimeout(() => {
      pan.style.willChange = 'auto'
      requestAnimationFrame(() => requestAnimationFrame(() => (pan.style.willChange = 'transform')))
    }, 160)
  }

  // ---- plot state -----------------------------------------------------------------------------------------------

  function paint(el: PlotEl) {
    el.g.classList.remove('is-available', 'is-booked', 'is-sold')
    el.g.classList.add(`is-${el.data.status}`)
    el.g.setAttribute('aria-label', fill(labels.plotAria, { number: el.data.number, status: labels[el.data.status], size: el.data.size || '' }).replace(/,\s*$/, ''))
    el.g.setAttribute('aria-pressed', String(selected.has(el.data.id)))
  }

  function restyle() {
    for (const el of plots.values()) {
      el.g.classList.toggle('is-dimmed', !shown.has(el.data.status))
      el.g.classList.toggle('is-selected', selected.has(el.data.id))
      el.g.setAttribute('aria-pressed', String(selected.has(el.data.id)))
    }
    placeBadges()
  }

  function placeBadges() {
    if (!topLayer) return
    topLayer.replaceChildren()
    const s = instance?.getTransform().scale ?? fit
    const k = Math.max(1, 9 / (10 * s))
    for (const el of selected.values()) {
      const g = document.createElementNS(SVG_NS, 'g')
      g.setAttribute('class', 'plan-badge-check')
      const x = el.cx + el.w / 2 - 6
      const y = el.cy - el.h / 2 + 6
      g.setAttribute('transform', `translate(${x} ${y}) rotate(${rotated ? -90 : 0}) scale(${k})`)
      g.innerHTML = '<circle r="9"/><path d="M-4 0.4 L-1.2 3.4 L4.4 -3"/>'
      topLayer.append(g)
    }
  }

  function pulse(el: PlotEl) {
    if (!topLayer || reduced()) return
    const ring = document.createElementNS(SVG_NS, 'rect')
    ring.setAttribute('class', 'plan-pulse')
    ring.setAttribute('x', String(el.cx - el.w / 2))
    ring.setAttribute('y', String(el.cy - el.h / 2))
    ring.setAttribute('width', String(el.w))
    ring.setAttribute('height', String(el.h))
    ring.setAttribute('rx', '3')
    topLayer.append(ring)
    window.setTimeout(() => ring.remove(), 700)
  }

  function toggle(el: PlotEl, { fromKeyboard = false } = {}) {
    const adding = !selected.has(el.data.id)
    if (adding) selected.set(el.data.id, el)
    else selected.delete(el.data.id)
    restyle()
    render()
    if (adding) {
      navigator.vibrate?.(10)
      pulse(el)
      if (mobile.matches) setSnap(panel.dataset.state === 'empty' || panel.dataset.state === 'peek' ? 'peek' : (panel.dataset.state as Snap))
      window.requestAnimationFrame(() => window.requestAnimationFrame(() => reveal(el, !fromKeyboard)))
    }
  }

  // ---- tap vs drag ----------------------------------------------------------------------------------------------

  const pointers = new Map<number, { x: number; y: number; t: number; moved: boolean }>()
  let multi = false
  let lastTap: { t: number; x: number; y: number; el: PlotEl | null; toggled: boolean } | null = null

  viewport.addEventListener('pointerdown', (e) => {
    hideCoach(true)
    pointers.set(e.pointerId, { x: e.clientX, y: e.clientY, t: performance.now(), moved: false })
    if (pointers.size > 1) multi = true
  })
  viewport.addEventListener('pointermove', (e) => {
    const p = pointers.get(e.pointerId)
    if (p && !p.moved && Math.hypot(e.clientX - p.x, e.clientY - p.y) > TAP_SLOP) p.moved = true
  })
  const end = (e: PointerEvent, cancelled: boolean) => {
    const p = pointers.get(e.pointerId)
    pointers.delete(e.pointerId)
    if (!p) return
    const tap = !cancelled && !p.moved && !multi && performance.now() - p.t < TAP_MS
    if (pointers.size === 0) multi = false
    if (!tap) return
    const hit = document.elementFromPoint(e.clientX, e.clientY)?.closest<SVGGElement>('g.plan-plot')
    const el = hit ? byId.get(Number(hit.getAttribute('data-plot-id'))) ?? null : null
    const now = performance.now()
    // A second tap close to the first is a double-tap zoom: undo the selection the first one made.
    if (lastTap && now - lastTap.t < 320 && Math.hypot(e.clientX - lastTap.x, e.clientY - lastTap.y) < 30) {
      if (lastTap.el && lastTap.toggled) toggle(lastTap.el)
      lastTap = null
      return
    }
    lastTap = { t: now, x: e.clientX, y: e.clientY, el, toggled: !!el }
    if (el) toggle(el)
  }
  viewport.addEventListener('pointerup', (e) => end(e, false))
  viewport.addEventListener('pointercancel', (e) => end(e, true))

  // ---- keyboard -------------------------------------------------------------------------------------------------

  viewport.addEventListener('keydown', (e) => {
    const current = (e.target as Element).closest<SVGGElement>('g.plan-plot')
    if (!current) return
    const el = byId.get(Number(current.getAttribute('data-plot-id')))
    if (!el) return
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault()
      toggle(el, { fromKeyboard: true })
      return
    }
    const dir = ({ ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] } as Record<string, [number, number]>)[e.key]
    if (!dir) return
    e.preventDefault()
    const c = current.getBoundingClientRect()
    const cx = c.x + c.width / 2
    const cy = c.y + c.height / 2
    let best: PlotEl | null = null
    let bestScore = Infinity
    for (const other of plots.values()) {
      if (other === el) continue
      const r = other.g.getBoundingClientRect()
      const dx = r.x + r.width / 2 - cx
      const dy = r.y + r.height / 2 - cy
      const along = dx * dir[0] + dy * dir[1]
      if (along <= 1) continue
      const across = Math.abs(dx * dir[1]) + Math.abs(dy * dir[0])
      const score = along + across * 2.2
      if (score < bestScore) {
        best = other
        bestScore = score
      }
    }
    if (best) {
      best.g.focus({ preventScroll: true })
      reveal(best)
    }
  })

  // ---- the panel / bottom sheet ---------------------------------------------------------------------------------

  type Snap = 'empty' | 'peek' | 'half' | 'full'
  const PEEK_PX = 150

  function snapY(state: Snap): number {
    const h = panel.offsetHeight
    if (state === 'full') return 0
    if (state === 'half') return Math.max(0, h - Math.round(window.innerHeight * 0.58))
    if (state === 'peek') return Math.max(0, h - PEEK_PX - (parseFloat(getComputedStyle(panel).paddingBottom) || 0))
    return h + 12
  }

  function setSnap(state: Snap) {
    panel.dataset.state = state
    if (state === 'peek') q('[data-details]').scrollTop = 0
    panel.style.transform = mobile.matches ? `translateY(${snapY(state)}px)` : ''
    grip.setAttribute('aria-expanded', String(state === 'half' || state === 'full'))
  }

  let drag: { startY: number; startT: number; y0: number; last: { y: number; t: number }[] } | null = null
  const dragTargets = [grip, q('[data-summary]')]
  for (const target of dragTargets) {
    target.addEventListener('pointerdown', (e) => {
      if (!mobile.matches || (e.target as Element).closest('button:not([data-grip])')) return
      drag = { startY: e.clientY, startT: performance.now(), y0: snapY(panel.dataset.state as Snap), last: [{ y: e.clientY, t: performance.now() }] }
      target.setPointerCapture(e.pointerId)
      panel.setAttribute('data-dragging', '')
    })
    target.addEventListener('pointermove', (e) => {
      if (!drag) return
      const y = clamp(drag.y0 + e.clientY - drag.startY, 0, snapY('peek'))
      panel.style.transform = `translateY(${y}px)`
      drag.last.push({ y: e.clientY, t: performance.now() })
      if (drag.last.length > 5) drag.last.shift()
    })
    const release = (e: PointerEvent) => {
      if (!drag) return
      const d = drag
      drag = null
      panel.removeAttribute('data-dragging')
      const moved = Math.abs(e.clientY - d.startY)
      const a = d.last[0]!
      const b = d.last[d.last.length - 1]!
      const velocity = b.t > a.t ? (b.y - a.y) / (b.t - a.t) : 0
      if (moved < 6) {
        // a tap on the handle toggles peek ↔ half
        if (target === grip) setSnap(panel.dataset.state === 'peek' ? 'half' : 'peek')
        else setSnap(panel.dataset.state as Snap)
        return
      }
      const projected = clamp(d.y0 + e.clientY - d.startY + velocity * 180, 0, snapY('peek'))
      const options: Snap[] = ['full', 'half', 'peek']
      const nearest = options.reduce((best, s) => (Math.abs(snapY(s) - projected) < Math.abs(snapY(best) - projected) ? s : best), 'peek' as Snap)
      setSnap(nearest)
    }
    target.addEventListener('pointerup', release)
    target.addEventListener('pointercancel', release)
  }
  grip.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault()
      const order: Snap[] = ['peek', 'half', 'full']
      const next = order[(order.indexOf(panel.dataset.state as Snap) + 1) % order.length]!
      setSnap(next)
    }
  })

  // ---- details content ------------------------------------------------------------------------------------------

  const sizeParts = (p: PlanPlot) => {
    const m = /^\s*(\d+(?:\.\d+)?)\s*(.*)$/.exec(p.sizeRaw)
    return m ? { n: parseFloat(m[1]!), unit: m[2]!.trim().toLowerCase() } : null
  }
  const unitDisplay = (p: PlanPlot) => p.size.replace(/[\d০-৯.,\s]/g, ' ').trim()

  function totalSize(list: PlanPlot[]): string {
    const parts = list.map(sizeParts)
    if (!parts.length || parts.some((x) => !x) || new Set(parts.map((x) => x!.unit)).size !== 1) return ''
    const sum = Math.round(parts.reduce((a, x) => a + x!.n, 0) * 100) / 100
    return `${digits(String(sum))} ${unitDisplay(list[0]!)}`.trim()
  }

  function composeMessage(list: PlanPlot[]): string {
    const allAvailable = list.every((p) => p.status === 'available')
    const noneAvailable = list.every((p) => p.status !== 'available')
    const sameSize = list.length > 1 && list.every((p) => p.sizeRaw !== '' && p.sizeRaw === list[0]!.sizeRaw)
    let plotsText: string
    if (sameSize && allAvailable) plotsText = `${list.map((p) => p.number).join(', ')} ${fill(labels.msgEach, { size: list[0]!.size })}`
    else {
      plotsText = list
        .map((p) => {
          const withStatus = p.status !== 'available'
          const template = p.size ? (withStatus ? labels.msgItemStatus : labels.msgItem) : withStatus ? labels.msgItemStatusNoSize : labels.msgItemNoSize
          return fill(template, { number: p.number, size: p.size, status: labels[p.status] })
        })
        .join(', ')
    }
    const template = noneAvailable ? (list.length > 1 ? labels.msgSimilarMany : labels.msgSimilar) : list.length > 1 ? labels.msgMany : labels.msgOne
    return `${fill(template, { project: plan.name, plots: plotsText })}\n${fill(labels.msgMap, { url: plan.whatsapp.url })}`
  }

  function render() {
    const list = [...selected.values()].map((e) => e.data)
    const details = q('[data-details]')
    const empty = q('[data-empty]')
    details.hidden = list.length === 0
    empty.hidden = list.length !== 0
    if (list.length === 0) {
      if (mobile.matches) setSnap('empty')
      else panel.dataset.state = 'empty'
      return
    }
    if (!mobile.matches) panel.dataset.state = 'full'

    const title = q('[data-summary-title]')
    const meta = q('[data-summary-meta]')
    const status = q('[data-summary-status]')
    if (list.length === 1) {
      const p = list[0]!
      title.textContent = fill(labels.plotLabel, { number: p.number })
      meta.textContent = [p.size, p.price || labels.callForPrice].filter(Boolean).join(' · ')
      status.hidden = false
      status.className = `plan-badge is-${p.status}`
      status.textContent = labels[p.status]
    } else {
      title.textContent = plural(labels.selectedTotal, list.length, { total: totalSize(list) || '—' }).replace(/\s·\s—$/, '').replace(/\s*—\s*$/, '')
      title.textContent = digits(title.textContent)
      meta.textContent = list.map((p) => p.number).join(', ')
      status.hidden = true
    }

    const rows = list.map((p) => {
      const li = (q<HTMLTemplateElement>('template[data-plot-item]').content.firstElementChild as HTMLElement).cloneNode(true) as HTMLElement
      const set = (sel: string, text: string) => (li.querySelector<HTMLElement>(sel)!.textContent = text)
      set('[data-item-label]', fill(labels.plotLabel, { number: p.number }))
      set('[data-item-size]', p.size || '—')
      li.querySelector<HTMLElement>('[data-item-price]')!.innerHTML = currency(p.price || labels.callForPrice)
      set('[data-item-position]', p.position || plan.name)
      const badge = li.querySelector<HTMLElement>('[data-item-status]')!
      badge.textContent = labels[p.status]
      badge.classList.add(`is-${p.status}`)
      const remove = li.querySelector<HTMLButtonElement>('[data-item-remove]')!
      remove.setAttribute('aria-label', fill(labels.removePlot, { number: p.number }))
      remove.addEventListener('click', () => {
        const el = byId.get(p.id)
        if (el) toggle(el)
      })
      return li
    })
    q('[data-plot-list]').replaceChildren(...rows)
    q('[data-plot-ref]').textContent = list.map((p) => p.ref).join(', ')

    const similar = list.every((p) => p.status !== 'available')
    const message = composeMessage(list)
    for (const a of app.querySelectorAll<HTMLAnchorElement>('[data-plot-whatsapp]')) {
      a.href = `https://wa.me/${plan.whatsapp.number}?text=${encodeURIComponent(message)}`
      const label = a.querySelector('[data-wa-label]')
      if (label) label.textContent = similar ? labels.askSimilar : (label.getAttribute('data-default') ?? label.textContent)
    }
    const note = fill(labels.notePlots, { project: plan.name, plots: list.map((p) => p.number).join(', ') })
    for (const b of app.querySelectorAll<HTMLElement>('[data-plot-consult]')) b.dataset.consultNote = note
  }
  for (const label of app.querySelectorAll('[data-wa-label]')) label.setAttribute('data-default', label.textContent ?? '')

  // ---- legend, search, project switcher, controls ---------------------------------------------------------------

  for (const chip of app.querySelectorAll<HTMLButtonElement>('[data-plan-filter]')) {
    chip.addEventListener('click', () => {
      const s = chip.dataset.planFilter as PlotStatus
      if (shown.has(s) && shown.size > 1) shown.delete(s)
      else shown.add(s)
      for (const other of app.querySelectorAll<HTMLButtonElement>('[data-plan-filter]')) other.setAttribute('aria-pressed', String(shown.has(other.dataset.planFilter as PlotStatus)))
      restyle()
    })
  }

  q('[data-plot-clear]').addEventListener('click', () => {
    selected.clear()
    restyle()
    render()
  })

  const find = q<HTMLFormElement>('[data-find]')
  const findInput = q<HTMLInputElement>('[data-find-input]')
  const findMsg = q('[data-find-msg]')
  q('[data-find-toggle]').addEventListener('click', () => {
    const open = !find.hasAttribute('data-open')
    find.toggleAttribute('data-open', open)
    q('[data-find-toggle]').setAttribute('aria-expanded', String(open))
    findMsg.hidden = true
    if (open) findInput.focus({ preventScroll: true })
  })
  find.addEventListener('submit', (e) => {
    e.preventDefault()
    const norm = (s: string) => s.trim().toLowerCase().replace(/^0+(?=\d)/, '')
    const wanted = norm(findInput.value)
    if (!wanted) return
    // "45" finds "45", "045" and "PLOT-45"
    const tail = (s: string) => norm(s.split(/[-/\s]/).pop() ?? s)
    const all = [...plots.values()]
    const el = all.find((p) => norm(p.data.raw) === wanted || norm(p.data.number) === wanted) ?? all.find((p) => tail(p.data.raw) === wanted || tail(p.data.number) === wanted)
    if (!el) {
      findMsg.textContent = labels.findNone
      findMsg.hidden = false
      return
    }
    findMsg.hidden = true
    findInput.blur()
    for (const s of STATUSES) shown.add(s)
    for (const other of app.querySelectorAll<HTMLButtonElement>('[data-plan-filter]')) other.setAttribute('aria-pressed', 'true')
    if (!selected.has(el.data.id)) toggle(el)
    else restyle()
    focusOn(el, Math.max(fit * 2.2, MIN_PLOT_PX / shortSide))
  })

  for (const button of app.querySelectorAll<HTMLButtonElement>('[data-plan-zoom]')) {
    button.addEventListener('click', () => {
      if (!instance) return
      const action = button.dataset.planZoom
      const t = instance.getTransform()
      if (action === 'reset') {
        const keep = selected.size
        initialViewAnimated()
        void keep
        return
      }
      const factor = action === 'in' ? 1.6 : 1 / 1.6
      const next = clamp(t.scale * factor, fit, fit * MAX_ZOOM_X)
      const cx = viewW / 2
      const cy = viewH / 2
      // zoom about the centre of the stage
      const k = next / t.scale
      animateTo(cx - (cx - t.x) * k, cy - (cy - t.y) * k, next, 220)
    })
  }

  function initialViewAnimated() {
    if (!instance) return
    // compute the initial view without applying it, then glide there
    const before = instance.getTransform()
    initialView()
    const after = instance.getTransform()
    goTo(before.x, before.y, before.scale)
    animateTo(after.x, after.y, after.scale, 280)
  }

  const fsButton = q<HTMLButtonElement>('[data-plan-fullscreen]')
  function setFullscreen(on: boolean) {
    app.classList.toggle('plan-fs', on)
    document.body.style.overflow = on ? 'hidden' : ''
    fsButton.setAttribute('aria-pressed', String(on))
    fsButton.setAttribute('aria-label', on ? labels.exitFullScreen : labels.fullScreen)
    window.scrollTo({ top: 0 })
    window.requestAnimationFrame(() => {
      const t = instance?.getTransform()
      layout(false)
      if (t && instance) goTo(t.x, t.y, Math.max(t.scale, fit))
    })
  }
  fsButton.addEventListener('click', () => setFullscreen(!fullscreen()))
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && fullscreen()) setFullscreen(false)
  })

  // Project switcher: swap the map in place.
  async function switchTo(slug: string, push: boolean) {
    if (slug === plan.slug) return
    try {
      const response = await fetch(`${endpoint}/${slug}?json=1`, { headers: { Accept: 'application/json' } })
      if (!response.ok) throw new Error(String(response.status))
      const next = (await response.json()) as Plan
      if (push) history.pushState({ plan: slug }, '', next.url)
      await load(next, { keepUrl: false })
    } catch {
      errorBox.textContent = labels.loadError
      errorBox.hidden = false
    }
  }
  for (const button of app.querySelectorAll<HTMLButtonElement>('[data-plan-project]')) button.addEventListener('click', () => void switchTo(button.dataset.planProject ?? '', true))
  window.addEventListener('popstate', () => {
    const slug = location.pathname.split('/').filter(Boolean).pop() ?? ''
    if (slug && slug !== plan.slug && app.querySelector(`[data-plan-project="${slug}"]`)) void switchTo(slug, false)
  })

  // Title, counts, switcher state and the page title follow the map that is showing.
  function syncChrome() {
    const title = app.querySelector<HTMLElement>('[data-plan-title]')
    const description = app.querySelector<HTMLElement>('[data-plan-description]')
    if (title) title.textContent = plan.name
    if (description) description.textContent = plan.description
    for (const s of STATUSES) {
      const c = app.querySelector<HTMLElement>(`[data-count="${s}"]`)
      if (c) c.textContent = digits(String(plan.counts[s] ?? 0))
    }
    for (const b of app.querySelectorAll<HTMLButtonElement>('[data-plan-project]')) b.setAttribute('aria-selected', String(b.dataset.planProject === plan.slug))
    const suffix = app.dataset.titleSuffix ?? ''
    document.title = document.title.replace(/^.*?(?= \|)/, `${plan.name} — ${suffix}`)
  }

  // ---- first-visit coach mark -----------------------------------------------------------------------------------

  function showCoach() {
    if (!mobile.matches) return
    try {
      if (localStorage.getItem('plan-coach')) return
    } catch {
      /* private mode: show it every time */
    }
    coach.hidden = false
    window.setTimeout(() => hideCoach(false), 6000)
  }
  function hideCoach(remember: boolean) {
    if (coach.hidden) return
    coach.hidden = true
    if (!remember) return
    try {
      localStorage.setItem('plan-coach', '1')
    } catch {
      /* ignore */
    }
  }

  // ---- resize / rotation ----------------------------------------------------------------------------------------

  let resizeTimer = 0
  const relayout = () => {
    window.clearTimeout(resizeTimer)
    resizeTimer = window.setTimeout(() => {
      layout(false)
      if (mobile.matches) setSnap(panel.dataset.state as Snap)
      else panel.style.transform = ''
    }, 150)
  }
  window.addEventListener('resize', relayout)
  window.addEventListener('orientationchange', relayout)
  mobile.addEventListener('change', () => {
    panel.style.transform = ''
    render()
    relayout()
  })

  // go
  sizeStage()
  render()
  await load(plan, { keepUrl: true })
}
