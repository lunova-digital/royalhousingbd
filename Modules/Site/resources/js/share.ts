// Share a listing. Phones get the native share sheet; everyone else gets a small popover (Facebook, WhatsApp, Messenger on
// phones, X, LinkedIn, Telegram, Copy link). Shared links carry utm_source=share&utm_medium=<network>.

const pop = document.querySelector<HTMLElement>('[data-share-pop]')
const coarse = () => window.matchMedia('(pointer: coarse)').matches
let current: { title: string; text: string; url: string } | null = null
let opener: HTMLElement | null = null

const withUtm = (url: string, medium: string) => `${url}${url.includes('?') ? '&' : '?'}utm_source=share&utm_medium=${medium}`
const enc = encodeURIComponent

function link(net: string, d: { title: string; text: string; url: string }): string {
  const u = withUtm(d.url, net)
  switch (net) {
    case 'facebook':
      return `https://www.facebook.com/sharer/sharer.php?u=${enc(u)}`
    case 'whatsapp':
      return `https://wa.me/?text=${enc(`${d.text} ${u}`)}`
    case 'messenger':
      // The Messenger app on phones; on desktop the item is hidden and Facebook covers it.
      return `fb-messenger://share/?link=${enc(u)}`
    case 'x':
      return `https://twitter.com/intent/tweet?text=${enc(d.text)}&url=${enc(u)}`
    case 'linkedin':
      return `https://www.linkedin.com/sharing/share-offsite/?url=${enc(u)}`
    case 'telegram':
      return `https://t.me/share/url?url=${enc(u)}&text=${enc(d.text)}`
    default:
      return u
  }
}

function close() {
  if (!pop || pop.hidden) return
  pop.hidden = true
  opener?.setAttribute('aria-expanded', 'false')
  opener?.focus({ preventScroll: true })
}

function openPopover(button: HTMLElement) {
  if (!pop || !current) return
  for (const a of pop.querySelectorAll<HTMLAnchorElement>('a[data-net]')) a.href = link(a.dataset.net!, current)
  for (const li of pop.querySelectorAll<HTMLElement>('[data-net-mobile-only]')) li.hidden = !coarse()
  pop.hidden = false
  button.setAttribute('aria-expanded', 'true')
  if (window.matchMedia('(min-width: 640px)').matches) {
    const r = button.getBoundingClientRect()
    const w = pop.offsetWidth
    const left = Math.min(Math.max(8, r.right - w), window.innerWidth - w - 8)
    const below = r.bottom + 8
    const top = below + pop.offsetHeight > window.innerHeight - 8 ? Math.max(8, r.top - pop.offsetHeight - 8) : below
    pop.style.left = `${left}px`
    pop.style.top = `${top}px`
    pop.style.bottom = 'auto'
  } else {
    pop.style.left = pop.style.top = ''
    pop.style.bottom = '0'
  }
  pop.querySelector<HTMLElement>('a, button')?.focus({ preventScroll: true })
}

document.addEventListener('click', async (event) => {
  const target = event.target as HTMLElement
  const button = target.closest<HTMLElement>('[data-share]')
  if (button) {
    event.preventDefault()
    event.stopPropagation()
    current = { title: button.dataset.shareTitle ?? '', text: button.dataset.shareText ?? '', url: button.dataset.shareUrl ?? location.href }
    opener = button
    if (pop && !pop.hidden && opener === button && button.getAttribute('aria-expanded') === 'true') return close()
    // Native share sheet on phones; the popover is the fallback and the desktop experience.
    if (coarse() && typeof navigator.share === 'function') {
      try {
        await navigator.share({ title: current.title, text: current.text, url: withUtm(current.url, 'native') })
      } catch {
        /* dismissed */
      }
      return
    }
    openPopover(button)
    return
  }
  if (!pop || pop.hidden) return
  const copy = target.closest<HTMLElement>('[data-net="copy"]')
  if (copy && current) {
    const text = withUtm(current.url, 'copy')
    try {
      await navigator.clipboard.writeText(text)
    } catch {
      const input = document.createElement('input')
      input.value = text
      document.body.append(input)
      input.select()
      document.execCommand('copy')
      input.remove()
    }
    window.rhToast?.(copy.dataset.copied ?? '')
    close()
    return
  }
  if (target.closest('a[data-net]')) {
    window.setTimeout(close, 50)
    return
  }
  if (!pop.contains(target)) close()
})
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') close()
})
window.addEventListener('scroll', () => close(), { passive: true })

export {}
