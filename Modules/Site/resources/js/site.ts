import './share'

// Shared behaviour for every server-rendered public page: mobile nav,
// click-to-load YouTube embeds, listing galleries, the lightbox, the gallery
// page filter, and the contact form that composes a WhatsApp message.

// Mobile navigation
const toggle = document.querySelector<HTMLButtonElement>('[data-nav-toggle]')
const menu = document.querySelector<HTMLElement>('[data-nav-menu]')
if (toggle && menu) {
  toggle.addEventListener('click', () => {
    menu.hidden = !menu.hidden
    toggle.setAttribute('aria-expanded', String(!menu.hidden))
  })
}

// YouTube: the thumbnail is a button; the iframe only loads on click (NFR-002).
for (const button of document.querySelectorAll<HTMLButtonElement>('[data-youtube]')) {
  button.addEventListener('click', () => {
    const id = button.dataset.youtube
    if (!id) return
    const frame = document.createElement('iframe')
    frame.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(id)}?autoplay=1&rel=0&playsinline=1`
    frame.title = button.getAttribute('aria-label') ?? ''
    frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
    frame.allowFullscreen = true
    frame.className = 'aspect-video w-full rounded-2xl'
    button.replaceWith(frame)
  })
}

// Listing gallery: a swipeable scroll-snap strip; thumbnails and arrows move it, a counter follows it.
for (const gallery of document.querySelectorAll<HTMLElement>('[data-gallery]')) {
  const slides = gallery.querySelector<HTMLElement>('[data-slides]')
  if (!slides) continue
  const count = slides.children.length
  const counter = gallery.querySelector<HTMLElement>('[data-gallery-counter]')
  const thumbs = Array.from(gallery.querySelectorAll<HTMLButtonElement>('[data-gallery-thumb]'))
  const index = () => Math.round(slides.scrollLeft / Math.max(1, slides.clientWidth))
  const go = (i: number) => slides.scrollTo({ left: Math.max(0, Math.min(count - 1, i)) * slides.clientWidth, behavior: 'smooth' })
  const sync = () => {
    const i = index()
    if (counter) counter.textContent = digits(`${i + 1} / ${count}`)
    thumbs.forEach((t, n) => {
      t.classList.toggle('border-ink-950', n === i)
      t.classList.toggle('border-transparent', n !== i)
    })
  }
  slides.addEventListener('scroll', () => requestAnimationFrame(sync), { passive: true })
  thumbs.forEach((t, n) => t.addEventListener('click', () => go(n)))
  gallery.querySelector('[data-gallery-prev]')?.addEventListener('click', () => go(index() - 1))
  gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => go(index() + 1))
  sync()
}

function digits(text: string): string {
  return document.documentElement.lang === 'bn' ? text.replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[Number(d)]!) : text
}

// Lightbox: anything with data-lightbox-src opens full size. Items inside one [data-lightbox-group] (or the whole
// page, if ungrouped) can be paged through with the arrows, the keyboard or a swipe; Esc, the backdrop or ✕ close it.
const lightbox = document.querySelector<HTMLElement>('[data-lightbox]')
const lightboxImg = lightbox?.querySelector<HTMLImageElement>('[data-lightbox-img]')
if (lightbox && lightboxImg) {
  const counter = lightbox.querySelector<HTMLElement>('[data-lightbox-counter]')
  const caption = lightbox.querySelector<HTMLElement>('[data-lightbox-caption]')
  let items: HTMLElement[] = []
  let at = 0
  let startX = 0
  const visible = (el: HTMLElement) => !el.closest('[hidden]')
  const show = (i: number) => {
    at = (i + items.length) % items.length
    const el = items[at]!
    lightboxImg.src = el.dataset.lightboxSrc ?? ''
    if (caption) caption.textContent = el.dataset.lightboxCaption ?? ''
    if (counter) counter.textContent = items.length > 1 ? digits(`${at + 1} / ${items.length}`) : ''
    for (const nav of lightbox.querySelectorAll<HTMLElement>('[data-lightbox-prev], [data-lightbox-next]')) nav.hidden = items.length < 2
  }
  const close = () => {
    lightbox.hidden = true
    lightboxImg.src = ''
    document.body.style.overflow = ''
  }
  document.addEventListener('click', (event) => {
    const target = (event.target as HTMLElement).closest<HTMLElement>('[data-lightbox-src]')
    if (!target) return
    event.preventDefault()
    const scope = target.closest<HTMLElement>('[data-lightbox-group], [data-gallery]') ?? document.body
    items = Array.from(scope.querySelectorAll<HTMLElement>('[data-lightbox-src]')).filter((el) => visible(el) && el.dataset.lightboxSrc)
    const i = items.indexOf(target)
    if (i < 0) items = [target]
    lightbox.hidden = false
    document.body.style.overflow = 'hidden'
    show(Math.max(0, i))
  })
  lightbox.addEventListener('click', (event) => {
    const el = event.target as HTMLElement
    if (el === lightbox || el.closest('[data-lightbox-close]')) close()
    else if (el.closest('[data-lightbox-prev]')) show(at - 1)
    else if (el.closest('[data-lightbox-next]')) show(at + 1)
  })
  lightbox.addEventListener('pointerdown', (event) => (startX = event.clientX))
  lightbox.addEventListener('pointerup', (event) => {
    const dx = event.clientX - startX
    if (Math.abs(dx) > 50 && items.length > 1) show(at + (dx < 0 ? 1 : -1))
  })
  document.addEventListener('keydown', (event) => {
    if (lightbox.hidden) return
    if (event.key === 'Escape') close()
    else if (event.key === 'ArrowRight') show(at + 1)
    else if (event.key === 'ArrowLeft') show(at - 1)
  })
}

// Gallery page filter chips.
const filters = document.querySelectorAll<HTMLButtonElement>('[data-gallery-filter]')
if (filters.length) {
  const items = document.querySelectorAll<HTMLElement>('[data-gallery-item]')
  const empty = document.querySelector<HTMLElement>('[data-gallery-empty]')
  for (const button of filters) {
    button.addEventListener('click', () => {
      const key = button.dataset.galleryFilter ?? 'all'
      let shown = 0
      for (const item of items) {
        const visible = key === 'all' || item.dataset.galleryItem === key
        item.hidden = !visible
        if (visible) shown++
      }
      if (empty) empty.hidden = shown > 0
      for (const other of filters) other.toggleAttribute('data-active', other === button)
    })
  }
}

// Listing filters: a ticked feature applies at once; the search box still needs Apply.
for (const box of document.querySelectorAll<HTMLInputElement>('[data-filter-form] [data-autosubmit]')) {
  box.addEventListener('change', () => box.form?.requestSubmit())
}

// Listing page tabs (description / amenities / map / video).
for (const tabs of document.querySelectorAll<HTMLElement>('[data-tabs]')) {
  const buttons = tabs.querySelectorAll<HTMLButtonElement>('[data-tab]')
  const panels = tabs.querySelectorAll<HTMLElement>('[data-tab-panel]')
  for (const button of buttons) {
    button.addEventListener('click', () => {
      const key = button.dataset.tab
      for (const other of buttons) {
        const active = other === button
        other.setAttribute('aria-selected', String(active))
        other.classList.toggle('border-ink-950', active)
        other.classList.toggle('text-fg', active)
        other.classList.toggle('border-transparent', !active)
        other.classList.toggle('text-muted-fg', !active)
      }
      for (const panel of panels) panel.hidden = panel.dataset.tabPanel !== key
    })
  }
}

// Contact form → WhatsApp (SRS: no enquiry storage; all contact is WhatsApp).
// The page passes the field labels (data-whatsapp-labels) in the visitor's language.
const form = document.querySelector<HTMLFormElement>('[data-whatsapp-form]')
if (form) {
  const labels = { subject: 'Subject', name: 'Name', phone: 'Phone', email: 'Email', ...parseLabels(form.dataset.whatsappLabels) }
  form.addEventListener('submit', (event) => {
    event.preventDefault()
    const data = new FormData(form)
    const lines = [
      `${labels.subject}: ${data.get('subject')}`,
      `${labels.name}: ${data.get('name')}`,
      data.get('phone') ? `${labels.phone}: ${data.get('phone')}` : '',
      data.get('email') ? `${labels.email}: ${data.get('email')}` : '',
      '',
      String(data.get('message') ?? ''),
    ].filter((line, i) => line !== '' || i === 4)
    const number = (form.dataset.whatsappNumber ?? '').replace(/\D+/g, '')
    const url = number
      ? `https://wa.me/${number}?text=${encodeURIComponent(lines.join('\n'))}`
      : `mailto:?subject=${encodeURIComponent(String(data.get('subject')))}&body=${encodeURIComponent(lines.join('\n'))}`
    window.open(url, '_blank', 'noopener')
  })
}

function parseLabels(json: string | undefined): Partial<Record<'subject' | 'name' | 'phone' | 'email', string>> {
  try {
    return json ? (JSON.parse(json) as Partial<Record<'subject' | 'name' | 'phone' | 'email', string>>) : {}
  } catch {
    return {}
  }
}

// ---- Public chrome ----

// Generic dialogs: [data-open-dialog="id"] opens it as a modal; backdrop clicks and [data-dialog-close] close it.
for (const opener of document.querySelectorAll<HTMLElement>('[data-open-dialog]')) {
  opener.addEventListener('click', () => {
    const target = document.getElementById(opener.dataset.openDialog ?? '')
    if (!(target instanceof HTMLDialogElement)) return
    for (const open of document.querySelectorAll<HTMLDialogElement>('dialog.sheet[open]')) open.close()
    target.showModal()
    target.querySelector<HTMLElement>('[data-autofocus]')?.focus()
  })
}
for (const dialog of document.querySelectorAll<HTMLDialogElement>('dialog')) {
  dialog.addEventListener('click', (event) => {
    const el = event.target as HTMLElement
    if (el === dialog || el.closest('[data-dialog-close]')) dialog.close()
  })
}

// ConsultSheet chooser: a bottom sheet on phones, a small non-modal card over the floating button on desktop.
const chooser = document.querySelector<HTMLDialogElement>('[data-chooser]')
if (chooser) {
  const desktop = window.matchMedia('(min-width: 1024px)')
  const toggles = document.querySelectorAll<HTMLElement>('[data-chooser-toggle]')
  const sync = () => {
    for (const t of toggles) t.setAttribute('aria-expanded', String(chooser.open))
  }
  for (const toggle of toggles) {
    toggle.addEventListener('click', (event) => {
      event.stopPropagation()
      if (chooser.open) chooser.close()
      else if (desktop.matches) chooser.show()
      else chooser.showModal()
      sync()
    })
  }
  chooser.addEventListener('close', sync)
  // Picking an option (or clicking away on desktop) closes it; the consult button then opens the booking dialog.
  chooser.addEventListener('click', (event) => {
    if ((event.target as HTMLElement).closest('a, [data-consult-open]')) chooser.close()
  })
  document.addEventListener('click', (event) => {
    if (chooser.open && desktop.matches && !chooser.contains(event.target as Node)) chooser.close()
  })
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && chooser.open) chooser.close()
  })
}

// Announcement ticker: one line at a time, cross-faded; dismissible for the session.
const ticker = document.querySelector<HTMLElement>('[data-ticker]')
if (ticker) {
  try {
    if (sessionStorage.getItem('ticker-dismissed')) ticker.remove()
  } catch {
    /* storage blocked: keep it */
  }
  const items = Array.from(ticker.querySelectorAll<HTMLElement>('[data-ticker-item]'))
  let current = 0
  if (items.length > 1) {
    setInterval(() => {
      items[current].removeAttribute('data-on')
      current = (current + 1) % items.length
      items[current].setAttribute('data-on', '')
    }, 5000)
  }
  ticker.querySelector('[data-ticker-dismiss]')?.addEventListener('click', () => {
    ticker.remove()
    try {
      sessionStorage.setItem('ticker-dismissed', '1')
    } catch {
      /* ignore */
    }
  })
}

// Available now: type tabs. Desktop shows at most two rows of three and never an orphan card.
const listingTabs = document.querySelector<HTMLElement>('[data-tabs-listings]')
if (listingTabs) {
  const chips = Array.from(listingTabs.querySelectorAll<HTMLButtonElement>('[data-tab-chip]'))
  const cards = Array.from(listingTabs.querySelectorAll<HTMLElement>('[data-tab-card]'))
  const apply = (key: string) => {
    for (const chip of chips) chip.setAttribute('aria-selected', String(chip.dataset.tabChip === key))
    const match = cards.filter((c) => key === 'all' || c.dataset.tabCard === key)
    for (const c of cards) {
      c.hidden = !match.includes(c)
      c.removeAttribute('data-orphan')
    }
    match.forEach((c, i) => {
      if (i >= 6) c.hidden = true
    })
    const shown = Math.min(match.length, 6)
    if (shown > 3 && shown % 3) match.slice(shown - (shown % 3), shown).forEach((c) => c.setAttribute('data-orphan', ''))
    else if (shown <= 3) match.slice(0, shown).forEach((c) => c.removeAttribute('data-orphan'))
  }
  for (const chip of chips) chip.addEventListener('click', () => apply(chip.dataset.tabChip ?? 'all'))
  apply('all')
}

// Videos: the thumbnail opens a modal player; the iframe only loads on click and is removed on close.
const videoDialog = document.querySelector<HTMLDialogElement>('[data-video-dialog]')
if (videoDialog) {
  const slot = videoDialog.querySelector<HTMLElement>('[data-video-slot]')!
  for (const button of document.querySelectorAll<HTMLElement>('[data-video]')) {
    button.addEventListener('click', () => {
      const frame = document.createElement('iframe')
      frame.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(button.dataset.video ?? '')}?autoplay=1&rel=0&playsinline=1`
      frame.title = button.getAttribute('aria-label') ?? ''
      frame.allow = 'autoplay; encrypted-media; picture-in-picture; web-share'
      frame.allowFullscreen = true
      frame.className = 'size-full'
      slot.replaceChildren(frame)
      videoDialog.showModal()
    })
  }
  videoDialog.addEventListener('close', () => slot.replaceChildren())
}

// Fade-up on section enter, once.
const reveals = document.querySelectorAll<HTMLElement>('.reveal')
if (reveals.length && 'IntersectionObserver' in window) {
  const io = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in')
          io.unobserve(entry.target)
        }
      }
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0.05 },
  )
  for (const el of reveals) io.observe(el)
} else {
  for (const el of reveals) el.classList.add('in')
}

// A small, polite toast ("Saved", "Link copied"): window.rhToast(text)
declare global {
  interface Window {
    rhToast?: (text: string) => void
  }
}
window.rhToast = (text: string) => {
  const region = document.querySelector<HTMLElement>('[data-toast-region]')
  if (!region || !text) return
  const el = document.createElement('div')
  el.className = 'toast-pill'
  el.textContent = text
  region.replaceChildren(el)
  window.setTimeout(() => el.classList.add('is-leaving'), 2200)
  window.setTimeout(() => el.remove(), 2600)
}
export {}

// The floating chat button's idle nudge: every ~8s a "Talk to an advisor" bubble slides out for 3s, at most 3 times a session.
const fab = document.querySelector<HTMLElement>('[data-fab]')
if (fab && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const KEY = 'rh-fab-nudges'
  let shown = 0
  try {
    shown = Number(sessionStorage.getItem(KEY) ?? 0)
  } catch {
    /* private mode: nudge freely, but only 3 times per page view */
  }
  const tick = () => {
    if (shown >= 3) return
    const busy = fab.getAttribute('aria-expanded') === 'true' || document.querySelector('dialog[open]')
    if (!busy) {
      shown++
      try {
        sessionStorage.setItem(KEY, String(shown))
      } catch {
        /* ignore */
      }
      fab.classList.add('is-nudging')
      window.setTimeout(() => fab.classList.remove('is-nudging'), 3000)
    }
    window.setTimeout(tick, 8000)
  }
  window.setTimeout(tick, 8000)
}
