// The public listings filter. One form drives everything: a sticky sidebar on desktop, a bottom sheet on phones.
// Changes update the URL and swap the results block in place (?fragment=1). Nothing here ever scrolls the page,
// so tapping option after option leaves the viewport exactly where it was. All copy comes from Blade (data-labels).

interface Labels {
  type: string
  location: string
  budget: string
  features: string
  sort: string
  between: string
  from: string
  upTo: string
  show: string
  selected: string
  remove: string
  filters: string
  search: string
  loading: string
}

const root = document.querySelector<HTMLElement>('[data-filter-root]')
if (root) init(root)

function init(root: HTMLElement) {
  const labels = JSON.parse(root.dataset.labels ?? '{}') as Labels
  const base = root.dataset.base ?? '/projects'
  const form = root.querySelector<HTMLFormElement>('#lf')!
  const results = root.querySelector<HTMLElement>('[data-results]')!
  const sheet = root.querySelector<HTMLElement>('[data-sheet]')!
  const backdrop = root.querySelector<HTMLElement>('[data-sheet-backdrop]')!
  const activeChips = root.querySelector<HTMLElement>('[data-active-chips]')!
  const countBadge = root.querySelector<HTMLElement>('[data-filter-count]')
  const showButton = root.querySelector<HTMLElement>('[data-show-button]')
  const desktop = window.matchMedia('(min-width: 1024px)')
  const panels = Array.from(form.querySelectorAll<HTMLDetailsElement>('details[data-panel]'))
  const defaults = new Map(panels.map((d) => [d, d.open]))
  const bn = document.documentElement.lang === 'bn'
  const digits = (s: string) => (bn ? s.replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[Number(d)]!) : s)

  // Opening a page from a back/forward navigation must not jump to some remembered offset.
  history.scrollRestoration = 'manual'

  const field = (name: string) => Array.from(form.elements).filter((e): e is HTMLInputElement | HTMLSelectElement => 'name' in e && (e as HTMLInputElement).name === name)
  const checked = (name: string) => field(name).filter((e) => (e as HTMLInputElement).checked) as HTMLInputElement[]
  const labelOf = (el: Element) => (el.closest('label')?.querySelector('[data-label]') ?? el.closest('label')?.querySelector('span'))?.textContent?.trim() ?? ''
  const optionText = (select: HTMLSelectElement) => select.selectedOptions[0]?.textContent?.trim() ?? ''

  // ---- state <-> URL -------------------------------------------------------------------------------------------

  function params(): URLSearchParams {
    const p = new URLSearchParams()
    for (const [key, value] of new FormData(form)) {
      if (typeof value !== 'string' || value === '') continue
      if (key === 'sort' && value === 'newest') continue
      p.append(key, value)
    }
    return p
  }

  function syncFormFromUrl() {
    const p = new URLSearchParams(location.search)
    const pathType = location.pathname !== base ? location.pathname.replace(/^\//, '') : ''
    const type = p.get('type') ?? pathType
    for (const r of field('type')) (r as HTMLInputElement).checked = r.value === type
    for (const r of field('sort')) (r as HTMLInputElement).checked = r.value === (p.get('sort') ?? 'newest')
    const q = form.querySelector<HTMLInputElement>('[name=q]') ?? root.querySelector<HTMLInputElement>('input[name=q]')
    if (q) q.value = p.get('q') ?? ''
    for (const name of ['budget_min', 'budget_max']) field(name).forEach((s) => (s.value = p.get(name) ?? ''))
    const locations = [...p.getAll('locations[]'), ...p.getAll('locations'), ...p.getAll('location')]
    for (const c of field('locations[]')) (c as HTMLInputElement).checked = locations.includes(c.value)
    const features = p.getAll('features[]')
    for (const c of field('features[]')) (c as HTMLInputElement).checked = features.includes(c.value)
  }

  // ---- results -------------------------------------------------------------------------------------------------

  let controller: AbortController | null = null
  let skeletonTimer = 0

  const skeleton = () =>
    `<div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-hidden="true">${Array.from({ length: 6 }, () => '<div class="card overflow-hidden"><div class="skeleton aspect-[4/3]"></div><div class="space-y-3 p-5"><div class="skeleton h-3 w-1/3 rounded"></div><div class="skeleton h-4 w-2/3 rounded"></div><div class="skeleton h-6 w-1/2 rounded"></div><div class="skeleton h-3 w-1/4 rounded"></div></div></div>').join('')}</div>`

  async function load(p: URLSearchParams) {
    controller?.abort()
    controller = new AbortController()
    const pageParams = new URLSearchParams(p)
    pageParams.set('fragment', '1')
    // Keep the block as tall as it was while it reloads so the page below never collapses and drags the viewport.
    results.style.minHeight = `${results.offsetHeight}px`
    results.setAttribute('aria-busy', 'true')
    window.clearTimeout(skeletonTimer)
    skeletonTimer = window.setTimeout(() => {
      results.innerHTML = skeleton()
    }, 140)
    try {
      const response = await fetch(`${base}?${pageParams}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
      if (!response.ok) throw new Error(String(response.status))
      const html = await response.text()
      window.clearTimeout(skeletonTimer)
      results.innerHTML = html
      const total = Number(results.querySelector<HTMLElement>('[data-results-inner]')?.dataset.total ?? 0)
      if (showButton) showButton.textContent = digits(plural(labels.show, total))
      results.setAttribute('aria-busy', 'false')
      requestAnimationFrame(() => (results.style.minHeight = ''))
    } catch (error) {
      if ((error as Error).name === 'AbortError') return
      window.clearTimeout(skeletonTimer)
      window.location.href = `${base}?${p}` // fall back to a normal page load
    }
  }

  let lastWasTyping = false
  function apply({ typing = false, page = 1 } = {}) {
    const p = params()
    if (page > 1) p.set('page', String(page))
    const url = p.size ? `${base}?${p}` : base
    const current = location.pathname + location.search
    if (url !== current) {
      // Typing in the search box replaces its own history entry instead of adding one per keystroke.
      if (typing && lastWasTyping) history.replaceState(null, '', url)
      else history.pushState(null, '', url)
    }
    lastWasTyping = typing
    summarise()
    void load(p)
  }

  // ---- chips, counts, removable filters ------------------------------------------------------------------------

  function summarise() {
    const type = checked('type').find((r) => r.value !== '')
    const locations = checked('locations[]')
    const features = checked('features[]')
    const [min, max] = ['budget_min', 'budget_max'].map((n) => field(n)[0] as HTMLSelectElement | undefined)
    const budgetOn = !!(min?.value || max?.value)
    const budgetText = !budgetOn ? '' : min?.value && max?.value ? labels.between.replace(':min', optionText(min)).replace(':max', optionText(max)) : min?.value ? labels.from.replace(':min', optionText(min!)) : labels.upTo.replace(':max', optionText(max!))
    const sort = checked('sort').find((r) => r.value !== 'newest')

    const set = (panel: string, text: string, on: boolean) => {
      const chip = root.querySelector<HTMLElement>(`[data-chip="${panel}"]`)
      if (!chip) return
      const label = chip.childNodes[0]!
      label.textContent = `${text} `
      if (on) chip.setAttribute('data-active', '')
      else chip.removeAttribute('data-active')
    }
    set('type', type ? labelOf(type) : labels.type, !!type)
    set('location', locations.length === 1 ? labelOf(locations[0]!) : locations.length ? `${labels.location} (${digits(String(locations.length))})` : labels.location, locations.length > 0)
    set('budget', budgetOn ? digits(budgetText) : labels.budget, budgetOn)
    set('features', features.length ? `${labels.features} (${digits(String(features.length))})` : labels.features, features.length > 0)
    set('sort', sort ? labelOf(sort) : labels.sort, !!sort)

    const groups = [!!type, locations.length > 0, budgetOn, features.length > 0, !!sort].filter(Boolean).length
    if (countBadge) {
      countBadge.hidden = groups === 0
      countBadge.textContent = digits(String(groups))
    }
    const anyFilter = groups > 0 || !!root.querySelector<HTMLInputElement>('input[name=q]')?.value
    for (const b of root.querySelectorAll<HTMLButtonElement>('[data-clear-all]')) {
      if (b.classList.contains('hidden')) b.hidden = !anyFilter
      else b.disabled = !anyFilter
    }

    // Removable chips under the bar
    const items: { text: string; remove: () => void }[] = []
    if (type) items.push({ text: labelOf(type), remove: () => ((field('type').find((r) => r.value === '') as HTMLInputElement).checked = true) })
    for (const c of locations) items.push({ text: labelOf(c), remove: () => (c.checked = false) })
    if (budgetOn) items.push({ text: digits(budgetText), remove: () => field('budget_min').concat(field('budget_max')).forEach((s) => (s.value = '')) })
    for (const c of features) items.push({ text: labelOf(c), remove: () => (c.checked = false) })
    activeChips.replaceChildren(
      ...items.map((item) => {
        const li = document.createElement('li')
        const button = document.createElement('button')
        button.type = 'button'
        button.className = 'active-chip'
        button.setAttribute('aria-label', `${labels.remove}: ${item.text}`)
        button.innerHTML = `<span></span><svg viewBox="0 0 24 24" class="size-4 opacity-80" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>`
        button.firstElementChild!.textContent = item.text
        button.addEventListener('click', () => {
          button.classList.add('is-leaving')
          window.setTimeout(() => {
            item.remove()
            apply()
          }, 150)
        })
        li.append(button)
        return li
      }),
    )
  }

  // ---- bottom sheet (phones) -----------------------------------------------------------------------------------

  let lastFocus: HTMLElement | null = null
  const isOpen = () => sheet.hasAttribute('data-open')

  function openSheet(panel: string) {
    if (desktop.matches) return
    lastFocus = document.activeElement as HTMLElement | null
    if (panel === 'all') {
      sheet.removeAttribute('data-single')
      for (const d of panels) {
        d.open = true
        d.removeAttribute('data-active')
      }
    } else {
      sheet.setAttribute('data-single', '')
      for (const d of panels) {
        const on = d.dataset.panel === panel
        d.open = on
        if (on) d.setAttribute('data-active', '')
        else d.removeAttribute('data-active')
      }
    }
    const title = root.querySelector<HTMLElement>('[data-sheet-title]')
    if (title) title.textContent = panel === 'all' ? labels.filters : ((labels as unknown as Record<string, string>)[panel] ?? labels.filters)
    sheet.setAttribute('role', 'dialog')
    sheet.setAttribute('aria-modal', 'true')
    backdrop.hidden = false
    requestAnimationFrame(() => {
      sheet.setAttribute('data-open', '')
      backdrop.style.opacity = '1'
    })
    window.setTimeout(() => sheet.querySelector<HTMLElement>('input, select, button')?.focus({ preventScroll: true }), 260)
  }

  function closeSheet() {
    if (!isOpen()) return
    sheet.removeAttribute('data-open')
    sheet.style.transform = ''
    backdrop.style.opacity = '0'
    window.setTimeout(() => {
      if (!isOpen()) backdrop.hidden = true
    }, 220)
    sheet.removeAttribute('role')
    sheet.removeAttribute('aria-modal')
    lastFocus?.focus({ preventScroll: true })
  }

  function resetForViewport() {
    closeSheet()
    sheet.removeAttribute('data-single')
    for (const d of panels) {
      d.open = desktop.matches ? (defaults.get(d) ?? false) || d.dataset.panel === 'type' || d.dataset.panel === 'budget' : (defaults.get(d) ?? false)
      d.removeAttribute('data-active')
    }
  }
  desktop.addEventListener('change', resetForViewport)

  for (const opener of root.querySelectorAll<HTMLElement>('[data-open-sheet]')) opener.addEventListener('click', () => openSheet(opener.dataset.openSheet ?? 'all'))
  for (const closer of root.querySelectorAll<HTMLElement>('[data-close-sheet]')) closer.addEventListener('click', closeSheet)
  backdrop.addEventListener('click', closeSheet)
  document.addEventListener('keydown', (event) => {
    if (!isOpen()) return
    if (event.key === 'Escape') closeSheet()
    if (event.key === 'Tab') {
      const focusable = Array.from(sheet.querySelectorAll<HTMLElement>('input:not([disabled]), select, button:not([disabled]), summary')).filter((el) => el.offsetParent !== null)
      const first = focusable[0]
      const last = focusable[focusable.length - 1]
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last?.focus()
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first?.focus()
      }
    }
  })

  // Drag the handle down to dismiss.
  const grip = root.querySelector<HTMLElement>('[data-sheet-grip]')
  if (grip) {
    let startY = 0
    let dy = 0
    grip.addEventListener('pointerdown', (event) => {
      startY = event.clientY
      dy = 0
      grip.setPointerCapture(event.pointerId)
      sheet.style.transition = 'none'
    })
    grip.addEventListener('pointermove', (event) => {
      if (!grip.hasPointerCapture(event.pointerId)) return
      dy = Math.max(0, event.clientY - startY)
      sheet.style.transform = `translateY(${dy}px)`
    })
    const end = (event: PointerEvent) => {
      if (!grip.hasPointerCapture(event.pointerId)) return
      grip.releasePointerCapture(event.pointerId)
      sheet.style.transition = ''
      if (dy > 100) closeSheet()
      else sheet.style.transform = ''
    }
    grip.addEventListener('pointerup', end)
    grip.addEventListener('pointercancel', end)
  }

  // ---- wiring --------------------------------------------------------------------------------------------------

  form.addEventListener('change', (event) => {
    if ((event.target as HTMLElement).matches('[data-location-search]')) return
    apply()
  })
  form.addEventListener('submit', (event) => {
    event.preventDefault()
    apply()
  })
  const search = root.querySelector<HTMLInputElement>('input[name=q]')
  let debounce = 0
  search?.addEventListener('input', () => {
    window.clearTimeout(debounce)
    debounce = window.setTimeout(() => apply({ typing: true }), 300)
  })
  search?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault()
      window.clearTimeout(debounce)
      apply({ typing: true })
      search.blur()
    }
  })

  for (const button of root.querySelectorAll<HTMLElement>('[data-clear-all]')) {
    button.addEventListener('click', () => {
      for (const r of field('type')) (r as HTMLInputElement).checked = r.value === ''
      for (const r of field('sort')) (r as HTMLInputElement).checked = r.value === 'newest'
      for (const c of field('locations[]').concat(field('features[]'))) (c as HTMLInputElement).checked = false
      for (const s of field('budget_min').concat(field('budget_max'))) s.value = ''
      if (search) search.value = ''
      apply()
    })
  }

  // Delegated: pagination and "clear filters" live inside the swapped results block.
  results.addEventListener('click', (event) => {
    const target = event.target as HTMLElement
    const pageLink = target.closest<HTMLAnchorElement>('[data-page-link]')
    if (pageLink) {
      event.preventDefault()
      apply({ page: Number(pageLink.dataset.pageLink) })
      results.scrollIntoView({ block: 'start' })
      return
    }
    if (target.closest('[data-clear-filters]')) {
      event.preventDefault()
      root.querySelector<HTMLElement>('[data-clear-all]')?.click()
    }
  })

  // Location search narrows the checkbox list without touching the filter itself.
  const locationSearch = form.querySelector<HTMLInputElement>('[data-location-search]')
  locationSearch?.addEventListener('input', () => {
    const needle = locationSearch.value.trim().toLowerCase()
    let shown = 0
    for (const item of form.querySelectorAll<HTMLElement>('[data-location-item]')) {
      const match = !needle || (item.dataset.search ?? '').includes(needle)
      item.hidden = !match
      if (match) shown++
    }
    const empty = form.querySelector<HTMLElement>('[data-location-empty]')
    if (empty) empty.hidden = shown > 0
  })

  // The info tooltip sits inside an accordion summary: tapping it must not fold the panel.
  for (const tip of form.querySelectorAll<HTMLElement>('.info-tip')) tip.addEventListener('click', (event) => event.preventDefault())

  window.addEventListener('popstate', () => {
    syncFormFromUrl()
    summarise()
    void load(new URLSearchParams(location.search).size ? new URLSearchParams(location.search) : params())
  })

  resetForViewport()
  summarise()
  if (showButton) showButton.textContent = digits(plural(labels.show, Number(results.querySelector<HTMLElement>('[data-results-inner]')?.dataset.total ?? 0)))
}

// Laravel's "singular|plural" form of a lang line, with :count filled in.
function plural(template: string, n: number): string {
  const forms = template.split('|')
  return (forms.length > 1 && n !== 1 ? forms[1]! : forms[0]!).replace(':count', String(n))
}
