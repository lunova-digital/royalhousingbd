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
    frame.title = 'YouTube video'
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
const form = document.querySelector<HTMLFormElement>('[data-whatsapp-form]')
if (form) {
  form.addEventListener('submit', (event) => {
    event.preventDefault()
    const data = new FormData(form)
    const lines = [
      `Subject: ${data.get('subject')}`,
      `Name: ${data.get('name')}`,
      data.get('phone') ? `Phone: ${data.get('phone')}` : '',
      data.get('email') ? `Email: ${data.get('email')}` : '',
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
