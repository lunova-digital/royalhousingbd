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

// Listing gallery: thumbnails swap the main image.
const gallery = document.querySelector<HTMLElement>('[data-gallery]')
if (gallery) {
  const main = gallery.querySelector<HTMLImageElement>('[data-gallery-main]')
  const thumbs = gallery.querySelectorAll<HTMLButtonElement>('[data-gallery-thumb]')
  for (const thumb of thumbs) {
    thumb.addEventListener('click', () => {
      if (!main) return
      main.src = thumb.dataset.galleryThumb ?? main.src
      main.dataset.lightboxSrc = main.src
      for (const other of thumbs) {
        other.classList.toggle('border-ink-950', other === thumb)
        other.classList.toggle('border-transparent', other !== thumb)
      }
    })
  }
}

// Lightbox: anything with data-lightbox-src opens full size.
const lightbox = document.querySelector<HTMLElement>('[data-lightbox]')
const lightboxImg = lightbox?.querySelector<HTMLImageElement>('[data-lightbox-img]')
if (lightbox && lightboxImg) {
  const close = () => {
    lightbox.hidden = true
    lightboxImg.src = ''
    document.body.style.overflow = ''
  }
  document.addEventListener('click', (event) => {
    const target = (event.target as HTMLElement).closest<HTMLElement>('[data-lightbox-src]')
    if (!target) return
    event.preventDefault()
    lightboxImg.src = target.dataset.lightboxSrc ?? ''
    lightbox.hidden = false
    document.body.style.overflow = 'hidden'
  })
  lightbox.addEventListener('click', (event) => {
    if (event.target === lightbox || (event.target as HTMLElement).closest('[data-lightbox-close]')) close()
  })
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !lightbox.hidden) close()
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
      for (const other of filters) {
        const active = other === button
        other.classList.toggle('bg-ink-950', active)
        other.classList.toggle('text-white', active)
        other.classList.toggle('bg-fill', !active)
        other.classList.toggle('text-fg', !active)
      }
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
