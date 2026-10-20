// Public accounts in the browser: the auth sheet, the Save heart, the header menu and the profile picture cropper.
// Every word comes from Blade (data attributes), never from this file.

declare global {
  interface Window {
    rhToast?: (text: string) => void
  }
}

const labels = document.querySelector<HTMLElement>('[data-account-labels]')?.dataset ?? {}
const isMember = () => document.body.dataset.member === '1'
const csrfMeta = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
const csrf = () => csrfMeta()?.content ?? ''
const toast = (text: string) => window.rhToast?.(text)

async function send(url: string, method: string, body?: BodyInit | Record<string, unknown>) {
  const isForm = body instanceof FormData
  const response = await fetch(url, {
    method,
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest', ...(body && !isForm ? { 'Content-Type': 'application/json' } : {}) },
    body: body ? (isForm ? (body as FormData) : JSON.stringify(body)) : undefined,
  })
  const data = (await response.json().catch(() => ({}))) as Record<string, any>
  return { ok: response.ok, status: response.status, data }
}

// ---- auth sheet --------------------------------------------------------------------------------------------------

const dialog = document.querySelector<HTMLDialogElement>('[data-auth-dialog]')

function setTab(tab: 'login' | 'register') {
  if (!dialog) return
  for (const button of dialog.querySelectorAll<HTMLElement>('[data-auth-tab]')) button.setAttribute('aria-selected', String(button.dataset.authTab === tab))
  for (const panel of dialog.querySelectorAll<HTMLElement>('[data-auth-panel]')) panel.hidden = panel.dataset.authPanel !== tab
}

function openAuth(tab: 'login' | 'register', prompt?: string) {
  if (!dialog) return
  for (const open of document.querySelectorAll<HTMLDialogElement>('dialog[open]')) if (open !== dialog) open.close()
  setTab(tab)
  const text = dialog.querySelector<HTMLElement>('[data-auth-prompt]')
  const title = dialog.querySelector<HTMLElement>('[data-auth-title]')
  if (text) text.textContent = prompt ? (dialog.dataset.promptText ?? text.dataset.default ?? '') : (text.dataset.default ?? '')
  if (title) title.textContent = prompt ?? (tab === 'register' ? (dialog.dataset.registerTitle ?? '') : (dialog.dataset.loginTitle ?? title.textContent ?? ''))
  // Google sends the visitor away: come back to this same page
  for (const link of dialog.querySelectorAll<HTMLAnchorElement>('[data-google-link]')) link.href = `/auth/google?redirect=${encodeURIComponent(location.pathname + location.search)}`
  for (const input of dialog.querySelectorAll<HTMLInputElement>('input[name=redirect]')) input.value = location.pathname + location.search
  dialog.showModal()
  window.setTimeout(() => dialog.querySelector<HTMLInputElement>(`[data-auth-panel="${tab}"] input[name=identifier]`)?.focus(), 60)
}

document.addEventListener('click', (event) => {
  const opener = (event.target as HTMLElement).closest<HTMLElement>('[data-auth-open]')
  if (opener) {
    event.preventDefault()
    openAuth((opener.dataset.authOpen as 'login' | 'register') ?? 'login', opener.dataset.authPrompt)
    return
  }
  const tab = (event.target as HTMLElement).closest<HTMLElement>('[data-auth-tab]')
  if (tab) setTab(tab.dataset.authTab as 'login' | 'register')
})

// Show / hide password
document.addEventListener('click', (event) => {
  const button = (event.target as HTMLElement).closest<HTMLButtonElement>('[data-toggle-password]')
  if (!button) return
  const input = button.parentElement?.querySelector<HTMLInputElement>('input')
  if (!input) return
  const show = input.type === 'password'
  input.type = show ? 'text' : 'password'
  button.setAttribute('aria-label', (show ? button.dataset.hide : button.dataset.show) ?? '')
  button.firstElementChild?.setAttribute('class', button.firstElementChild.getAttribute('class') ?? '')
})

// Log in / sign up: JSON, field errors inline, then finish a pending save and reload (or go to the redirect)
for (const form of document.querySelectorAll<HTMLFormElement>('[data-auth-form]')) {
  form.addEventListener('submit', async (event) => {
    event.preventDefault()
    const button = form.querySelector<HTMLButtonElement>('[data-submit]')
    const label = button?.textContent ?? ''
    for (const e of form.querySelectorAll<HTMLElement>('[data-error], [data-form-error]')) e.hidden = true
    if (button) {
      button.disabled = true
      button.textContent = labels.working ?? '…'
    }
    try {
      const { ok, status, data } = await send(form.action, 'POST', Object.fromEntries(new FormData(form)) as Record<string, unknown>)
      if (ok && data.ok) {
        if (data.csrf && csrfMeta()) csrfMeta()!.content = data.csrf
        const pending = takePending()
        if (pending) await send(`/account/saved/${pending}`, 'PUT')
        const inDialog = !!form.closest('dialog')
        if (inDialog) location.reload()
        else location.href = data.redirect ?? '/'
        return
      }
      if (status === 422 && data.errors) {
        for (const [field, messages] of Object.entries(data.errors as Record<string, string[]>)) {
          const slot = form.querySelector<HTMLElement>(`[data-field="${field}"] [data-error]`)
          if (slot) {
            slot.textContent = messages[0] ?? ''
            slot.hidden = false
          }
        }
      } else {
        const box = form.querySelector<HTMLElement>('[data-form-error]')
        if (box) {
          box.textContent = status === 429 ? (labels.tooMany ?? '') : (labels.error ?? '')
          box.hidden = false
        }
      }
    } catch {
      const box = form.querySelector<HTMLElement>('[data-form-error]')
      if (box) {
        box.textContent = labels.error ?? ''
        box.hidden = false
      }
    } finally {
      if (button) {
        button.disabled = false
        button.textContent = label
      }
    }
  })
}

// ---- header menu -------------------------------------------------------------------------------------------------

const menu = document.querySelector<HTMLElement>('[data-user-menu]')
if (menu) {
  const toggle = menu.querySelector<HTMLButtonElement>('[data-user-toggle]')!
  const panel = menu.querySelector<HTMLElement>('[data-user-panel]')!
  const close = () => {
    panel.hidden = true
    toggle.setAttribute('aria-expanded', 'false')
  }
  toggle.addEventListener('click', () => {
    panel.hidden = !panel.hidden
    toggle.setAttribute('aria-expanded', String(!panel.hidden))
  })
  document.addEventListener('click', (e) => {
    if (!menu.contains(e.target as Node)) close()
  })
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close()
  })
}

// ---- save for later ----------------------------------------------------------------------------------------------

const PENDING = 'rh-pending-save'
const SCROLL = 'rh-scroll-back'

function takePending(): string | null {
  try {
    const id = sessionStorage.getItem(PENDING)
    sessionStorage.removeItem(PENDING)
    return id
  } catch {
    return null
  }
}

// Back from Google (a full-page trip): return to the same scroll position.
try {
  const back = JSON.parse(sessionStorage.getItem(SCROLL) ?? 'null') as { path: string; y: number } | null
  if (back && back.path === location.pathname + location.search) window.requestAnimationFrame(() => window.scrollTo(0, back.y))
  sessionStorage.removeItem(SCROLL)
} catch {
  /* ignore */
}

function paintSaved(id: string, saved: boolean, animate = false) {
  for (const button of document.querySelectorAll<HTMLElement>(`[data-save="${id}"]`)) {
    button.setAttribute('aria-pressed', String(saved))
    const text = button.querySelector<HTMLElement>('[data-save-text]')
    if (text) text.textContent = saved ? (button.dataset.textSaved ?? '') : (button.dataset.textSave ?? '')
    button.setAttribute('aria-label', saved ? (button.dataset.labelSaved ?? '') : (button.dataset.labelSave ?? ''))
    if (animate && saved) pop(button)
  }
}

// The heart fills with a small scale-pop and a burst of dots.
function pop(button: HTMLElement) {
  const heart = button.querySelector<SVGElement>('svg')
  heart?.classList.remove('heart-pop')
  void heart?.getBoundingClientRect()
  heart?.classList.add('heart-pop')
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  const burst = document.createElement('span')
  burst.className = 'heart-burst'
  burst.setAttribute('aria-hidden', 'true')
  for (let i = 0; i < 7; i++) {
    const dot = document.createElement('i')
    dot.style.setProperty('--a', `${i * (360 / 7)}deg`)
    burst.append(dot)
  }
  button.append(burst)
  window.setTimeout(() => burst.remove(), 700)
}

document.addEventListener('click', async (event) => {
  const button = (event.target as HTMLElement).closest<HTMLElement>('[data-save]')
  if (!button) return
  event.preventDefault()
  event.stopPropagation()
  const id = button.dataset.save!
  if (!isMember()) {
    // A guest: ask them to log in; the save completes afterwards and the page returns to where they were.
    try {
      sessionStorage.setItem(PENDING, id)
      sessionStorage.setItem(SCROLL, JSON.stringify({ path: location.pathname + location.search, y: window.scrollY }))
    } catch {
      /* ignore */
    }
    void send('/account/pending-save', 'POST', { listing: Number(id) })
    openAuth('login', button.dataset.authPrompt ?? labels.savePrompt)
    return
  }
  const wasSaved = button.getAttribute('aria-pressed') === 'true'
  paintSaved(id, !wasSaved, true) // optimistic
  try {
    const { ok } = await send(`/account/saved/${id}`, wasSaved ? 'DELETE' : 'PUT')
    if (!ok) throw new Error('save failed')
    toast(wasSaved ? (labels.removed ?? '') : (labels.added ?? ''))
    // On the Saved page, removing a card removes it from the grid
    const card = button.closest<HTMLElement>('[data-saved-card]')
    if (card && wasSaved) {
      card.style.transition = 'opacity .25s, transform .25s'
      card.style.opacity = '0'
      card.style.transform = 'scale(.97)'
      window.setTimeout(() => {
        card.remove()
        document.dispatchEvent(new CustomEvent('saved:changed'))
      }, 260)
    }
  } catch {
    paintSaved(id, wasSaved) // revert
    toast(labels.error ?? '')
  }
})

// Saved page: type tabs filter the grid; counts and the empty state follow removals
const savedRoot = document.querySelector<HTMLElement>('[data-saved-root]')
if (savedRoot) {
  const chips = Array.from(savedRoot.querySelectorAll<HTMLButtonElement>('[data-saved-tab]'))
  let current = 'all'
  const apply = () => {
    const cards = Array.from(savedRoot.querySelectorAll<HTMLElement>('[data-saved-card]'))
    for (const card of cards) card.hidden = current !== 'all' && card.dataset.savedCard !== current
    for (const chip of chips) {
      const key = chip.dataset.savedTab!
      const n = key === 'all' ? cards.length : cards.filter((c) => c.dataset.savedCard === key).length
      const count = chip.querySelector<HTMLElement>('[data-count]')
      if (count) count.textContent = new Intl.NumberFormat(document.documentElement.lang === 'bn' ? 'bn-BD' : 'en').format(n)
      chip.hidden = key !== 'all' && n === 0
      chip.toggleAttribute('data-active', key === current)
    }
    if (cards.length === 0) savedRoot.querySelector<HTMLElement>('[data-saved-empty]')?.removeAttribute('hidden')
    savedRoot.querySelector<HTMLElement>('[data-saved-grid]')?.toggleAttribute('hidden', cards.length === 0)
    savedRoot.querySelector<HTMLElement>('[data-ask-all]')?.toggleAttribute('hidden', cards.length === 0)
    if (current !== 'all' && cards.filter((c) => c.dataset.savedCard === current).length === 0) {
      current = 'all'
      apply()
    }
  }
  for (const chip of chips)
    chip.addEventListener('click', () => {
      current = chip.dataset.savedTab ?? 'all'
      apply()
    })
  document.addEventListener('saved:changed', apply)
}

// ---- profile picture: choose → frame (drag + zoom) → 256px WebP on the server ---------------------------------------

const cropper = document.querySelector<HTMLDialogElement>('[data-crop-dialog]')
const fileInput = document.querySelector<HTMLInputElement>('[data-avatar-input]')
if (cropper && fileInput) {
  const stage = cropper.querySelector<HTMLElement>('[data-crop-stage]')!
  const img = cropper.querySelector<HTMLImageElement>('[data-crop-img]')!
  const zoom = cropper.querySelector<HTMLInputElement>('[data-crop-zoom]')!
  const FRAME = 280
  let state = { x: 0, y: 0, scale: 1, min: 1, w: 0, h: 0 }

  const clampPos = () => {
    const sw = state.w * state.scale
    const sh = state.h * state.scale
    state.x = Math.min(0, Math.max(FRAME - sw, state.x))
    state.y = Math.min(0, Math.max(FRAME - sh, state.y))
  }
  const paint = () => {
    img.style.transform = `translate(${state.x}px, ${state.y}px) scale(${state.scale})`
  }

  fileInput.addEventListener('change', () => {
    const file = fileInput.files?.[0]
    if (!file) return
    const url = URL.createObjectURL(file)
    img.onload = () => {
      state.w = img.naturalWidth
      state.h = img.naturalHeight
      state.min = FRAME / Math.min(state.w, state.h)
      state.scale = state.min
      state.x = (FRAME - state.w * state.scale) / 2
      state.y = (FRAME - state.h * state.scale) / 2
      zoom.min = String(state.min)
      zoom.max = String(state.min * 4)
      zoom.step = String(state.min / 50)
      zoom.value = String(state.scale)
      paint()
      cropper.showModal()
    }
    img.src = url
  })

  zoom.addEventListener('input', () => {
    const next = Number(zoom.value)
    const cx = (FRAME / 2 - state.x) / state.scale
    const cy = (FRAME / 2 - state.y) / state.scale
    state.scale = next
    state.x = FRAME / 2 - cx * next
    state.y = FRAME / 2 - cy * next
    clampPos()
    paint()
  })

  let drag: { px: number; py: number; x: number; y: number } | null = null
  stage.addEventListener('pointerdown', (e) => {
    drag = { px: e.clientX, py: e.clientY, x: state.x, y: state.y }
    stage.setPointerCapture(e.pointerId)
  })
  stage.addEventListener('pointermove', (e) => {
    if (!drag) return
    state.x = drag.x + e.clientX - drag.px
    state.y = drag.y + e.clientY - drag.py
    clampPos()
    paint()
  })
  stage.addEventListener('pointerup', () => (drag = null))
  stage.addEventListener('pointercancel', () => (drag = null))

  cropper.querySelector('[data-crop-cancel]')?.addEventListener('click', () => {
    cropper.close()
    fileInput.value = ''
  })
  cropper.querySelector<HTMLButtonElement>('[data-crop-save]')?.addEventListener('click', async (e) => {
    const button = e.currentTarget as HTMLButtonElement
    const canvas = document.createElement('canvas')
    canvas.width = canvas.height = 512
    const ctx = canvas.getContext('2d')!
    const k = 512 / FRAME
    ctx.drawImage(img, state.x * k, state.y * k, state.w * state.scale * k, state.h * state.scale * k)
    button.disabled = true
    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/webp', 0.9))
    if (!blob) return void (button.disabled = false)
    const form = new FormData()
    form.append('avatar', blob, 'avatar.webp')
    const { ok, data } = await send('/account/profile/avatar', 'POST', form)
    button.disabled = false
    cropper.close()
    fileInput.value = ''
    if (!ok) return toast(labels.error ?? '')
    toast(labels.photoSaved ?? '')
    for (const el of document.querySelectorAll<HTMLElement>('[data-avatar-preview]')) {
      el.innerHTML = `<img src="${String(data.url)}?v=${Date.now()}" alt="" class="size-full rounded-full object-cover" />`
    }
    document.querySelector('[data-avatar-remove]')?.removeAttribute('hidden')
  })
}
document.querySelector<HTMLButtonElement>('[data-avatar-remove]')?.addEventListener('click', async (e) => {
  const button = e.currentTarget as HTMLButtonElement
  const { ok } = await send('/account/profile/avatar', 'DELETE')
  if (ok) location.reload()
  else toast(labels.error ?? '')
  void button
})

export {}
