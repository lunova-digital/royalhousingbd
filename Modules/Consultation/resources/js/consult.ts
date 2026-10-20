// The free consultation widget: loads the slot calendar, lets the visitor pick
// a day and a time, posts the booking and shows the confirmation. The floating
// launcher opens the same widget in a <dialog>; /consultation carries it inline.

interface Slot { time: string; label: string; taken: boolean }
interface Day { date: string; weekday: string; day: string; month: string; label: string; open: number; slots: Slot[] }
interface Summary { reference: string; url: string; date: string; time: string; whatsapp_url: string | null }
type Labels = Record<'open' | 'full' | 'no_slots' | 'no_days' | 'load_failed' | 'generic' | 'slot' | 'selected' | 'submitting' | 'required', string>

const q = <T extends HTMLElement>(root: ParentNode, selector: string) => root.querySelector<T>(selector)

class Widget {
  private form: HTMLFormElement
  private labels: Labels
  private days: Day[] = []
  private selected: { day: Day; slot?: Slot } | null = null
  private loaded = false
  private step = 1

  constructor(private root: HTMLElement) {
    this.form = q<HTMLFormElement>(root, '[data-consult-form]')!
    this.labels = JSON.parse(root.dataset.consultLabels ?? '{}') as Labels
    this.form.addEventListener('submit', (e) => void this.submit(e))
    for (const button of this.root.querySelectorAll<HTMLElement>('[data-consult-next]')) button.addEventListener('click', () => this.next())
    for (const button of this.root.querySelectorAll<HTMLElement>('[data-consult-back]')) button.addEventListener('click', () => this.goTo(this.step - 1))
    this.goTo(1)
    for (const input of this.form.querySelectorAll<HTMLElement>('input, select, textarea')) {
      input.addEventListener('input', () => this.clearError(input.getAttribute('name') ?? ''))
    }
  }

  // Fetch the calendar once; a retry after a failed load or a clash refreshes it.
  async load(force = false) {
    if (this.loaded && !force) return
    const feedError = q(this.root, '[data-consult-feed-error]')!
    feedError.hidden = true
    const fallback = q<HTMLElement>(this.root, '[data-consult-wa-fallback]')
    if (fallback) fallback.hidden = true
    try {
      const response = await fetch(this.root.dataset.consultFeed ?? '/consultation/availability', { headers: { Accept: 'application/json' } })
      if (!response.ok) throw new Error(String(response.status))
      this.days = ((await response.json()) as { days: Day[] }).days
      this.loaded = true
    } catch {
      feedError.textContent = this.labels.load_failed
      feedError.hidden = false
      if (fallback) fallback.hidden = false
      q(this.root, '[data-consult-loading]')?.remove()
      return
    }
    this.renderDays()
    if (fallback) fallback.hidden = this.days.length > 0
    const keep = this.selected && this.days.find((d) => d.date === this.selected!.day.date)
    this.select(keep ?? this.days.find((d) => d.open > 0) ?? this.days[0] ?? null, keep && this.selected?.slot ? keep.slots.find((s) => s.time === this.selected!.slot!.time && !s.taken) : undefined)
  }

  private renderDays() {
    const strip = q(this.root, '[data-consult-days]')!
    strip.innerHTML = ''
    if (this.days.length === 0) {
      strip.innerHTML = `<p class="text-sm text-muted-fg">${escape(this.labels.no_days)}</p>`
      return
    }
    for (const day of this.days) {
      const button = document.createElement('button')
      button.type = 'button'
      button.className = 'consult-day'
      button.dataset.date = day.date
      button.setAttribute('aria-pressed', 'false')
      button.disabled = day.open === 0
      const open = day.open > 0 ? count(this.labels.open, day.open) : this.labels.full
      button.innerHTML = `<span class="text-[11px] font-semibold uppercase tracking-wider opacity-70">${escape(day.weekday)}</span><span class="font-display text-xl font-bold leading-tight">${escape(day.day)}</span><span class="text-[11px] opacity-70">${escape(day.month)}</span><span class="mt-0.5 text-[10px] font-semibold ${day.open > 0 ? 'text-success' : 'opacity-70'}">${escape(open)}</span>`
      button.addEventListener('click', () => this.select(day))
      strip.append(button)
    }
  }

  private select(day: Day | null, slot?: Slot) {
    this.selected = day ? { day, slot } : null
    for (const b of this.root.querySelectorAll<HTMLButtonElement>('.consult-day')) {
      const active = b.dataset.date === day?.date
      b.setAttribute('aria-pressed', String(active))
      if (active) b.scrollIntoView({ block: 'nearest', inline: 'nearest' })
    }
    const grid = q(this.root, '[data-consult-slots]')!
    grid.innerHTML = ''
    if (!day) return
    if (day.open === 0) grid.innerHTML = `<p class="col-span-full text-sm text-muted-fg">${escape(this.labels.no_slots)}</p>`
    for (const s of day.slots) {
      const button = document.createElement('button')
      button.type = 'button'
      button.className = 'consult-slot'
      button.textContent = s.label
      button.disabled = s.taken
      button.setAttribute('aria-pressed', 'false')
      button.addEventListener('click', () => this.pick(day, s))
      grid.append(button)
    }
    if (slot) this.pick(day, slot)
    else this.pick(day, null)
  }

  private pick(day: Day, slot: Slot | null) {
    this.selected = { day, slot: slot ?? undefined }
    const buttons = [...this.root.querySelectorAll<HTMLButtonElement>('.consult-slot')]
    for (const b of buttons) b.setAttribute('aria-pressed', String(slot !== null && b.textContent === slot.label))
    q<HTMLInputElement>(this.form, '[data-consult-date]')!.value = slot ? day.date : ''
    q<HTMLInputElement>(this.form, '[data-consult-time]')!.value = slot ? slot.time : ''
    const line = q(this.root, '[data-consult-selected]')!
    line.hidden = !slot
    if (slot) {
      line.textContent = this.labels.selected.replace(':when', `${day.label} · ${slot.label}`)
      this.clearError('time')
    }
  }

  // Stepped form: 1 details, 2 day, 3 time (+ the confirm button); the progress bar fills as you go.
  private goTo(step: number) {
    this.step = Math.max(1, Math.min(3, step))
    const names = ['details', 'day', 'time']
    for (const section of this.root.querySelectorAll<HTMLElement>('[data-consult-step]')) section.hidden = section.dataset.consultStep !== names[this.step - 1]
    for (const item of this.root.querySelectorAll<HTMLElement>('[data-progress-step]')) {
      const n = Number(item.dataset.progressStep)
      const bar = item.firstElementChild as HTMLElement
      bar.className = `h-1 rounded-full transition-colors duration-200 ${n <= this.step ? 'bg-ink-950' : 'bg-line'}`
      item.classList.toggle('text-fg', n <= this.step)
      if (n === this.step) item.setAttribute('aria-current', 'step')
      else item.removeAttribute('aria-current')
    }
    this.root.closest('dialog')?.scrollTo({ top: 0 })
  }

  private next() {
    if (this.step === 1) {
      let ok = true
      for (const field of ['name', 'phone']) {
        const input = q<HTMLInputElement>(this.form, `[name="${field}"]`)
        if (input && input.value.trim() === '') {
          this.showError(field, this.labels.required)
          ok = false
        }
      }
      if (!ok) return
    }
    this.goTo(this.step + 1)
  }

  setListing(id: string) {
    q<HTMLInputElement>(this.form, '[data-consult-listing]')!.value = id
  }

  private async submit(event: Event) {
    event.preventDefault()
    this.clearErrors()
    if (!this.selected?.slot) {
      this.goTo(3)
      this.showError('time', this.labels.slot)
      return
    }
    const submit = q<HTMLButtonElement>(this.form, '[data-consult-submit]')!
    const label = q(this.form, '[data-consult-submit-label]')!
    const idle = label.textContent
    submit.disabled = true
    label.textContent = this.labels.submitting
    try {
      const response = await fetch(this.form.action, {
        method: 'POST',
        body: new FormData(this.form),
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const payload = (await response.json().catch(() => ({}))) as { consultation?: Summary; message?: string; errors?: Record<string, string[]> }
      if (response.status === 201 && payload.consultation) {
        this.success(payload.consultation)
        return
      }
      if (response.status === 422 && payload.errors) {
        for (const [field, messages] of Object.entries(payload.errors)) this.showError(field, messages[0] ?? '')
        const fields = Object.keys(payload.errors)
        if (fields.some((f) => ['name', 'phone', 'email', 'mode', 'topic', 'message'].includes(f))) this.goTo(1)
        else if (fields.some((f) => ['date', 'time'].includes(f))) this.goTo(3)
        if (payload.errors.time) void this.load(true)
        return
      }
      this.showMessage(payload.message && response.status !== 500 ? payload.message : this.labels.generic)
    } catch {
      this.showMessage(this.labels.generic)
    } finally {
      submit.disabled = false
      label.textContent = idle
    }
  }

  private success(c: Summary) {
    this.form.hidden = true
    for (const item of this.root.querySelectorAll<HTMLElement>('[data-progress-step]')) {
      ;(item.firstElementChild as HTMLElement).className = 'h-1 rounded-full bg-ink-950'
      item.classList.add('text-fg')
    }
    const panel = q(this.root, '[data-consult-success]')!
    q(panel, '[data-consult-when]')!.textContent = `${c.date} · ${c.time}`
    q(panel, '[data-consult-ref]')!.textContent = c.reference
    q<HTMLAnchorElement>(panel, '[data-consult-url]')!.href = c.url
    const wa = q<HTMLAnchorElement>(panel, '[data-consult-wa]')!
    wa.hidden = !c.whatsapp_url
    if (c.whatsapp_url) wa.href = c.whatsapp_url
    panel.hidden = false
    panel.scrollIntoView({ block: 'nearest' })
  }

  private showError(field: string, message: string) {
    const target = q(this.root, `[data-consult-error-for="${field}"]`) ?? q(this.root, `[data-consult-field="${field}"] [data-consult-error]`)
    if (!target) {
      this.showMessage(message)
      return
    }
    target.textContent = message
    target.hidden = false
    q<HTMLElement>(this.root, `[data-consult-field="${field}"] .input`)?.classList.add('border-brand')
  }

  private clearError(field: string) {
    const target = q(this.root, `[data-consult-error-for="${field}"]`) ?? q(this.root, `[data-consult-field="${field}"] [data-consult-error]`)
    if (target) target.hidden = true
    q<HTMLElement>(this.root, `[data-consult-field="${field}"] .input`)?.classList.remove('border-brand')
  }

  private clearErrors() {
    for (const el of this.root.querySelectorAll<HTMLElement>('[data-consult-error], [data-consult-error-for], [data-consult-message]')) el.hidden = true
    for (const el of this.root.querySelectorAll<HTMLElement>('.input.border-brand')) el.classList.remove('border-brand')
  }

  private showMessage(text: string) {
    const box = q(this.root, '[data-consult-message]')!
    box.textContent = text
    box.hidden = false
  }
}

// ":count free" / ":count টি খালি" with Laravel's "singular|plural" form.
function count(template: string, n: number): string {
  const forms = template.split('|')
  const digits = document.documentElement.lang === 'bn' ? String(n).replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[Number(d)]!) : String(n)
  return (forms.length > 1 && n !== 1 ? forms[1]! : forms[0]!).replace(':count', digits)
}

function escape(text: string): string {
  return text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]!)
}

const widgets = new Map<HTMLElement, Widget>()
for (const root of document.querySelectorAll<HTMLElement>('[data-consult-widget]')) {
  const widget = new Widget(root)
  widgets.set(root, widget)
  if ('consultInline' in root.dataset) void widget.load()
}

// The floating launcher and any "book about this listing" button open the dialog.
const dialog = document.querySelector<HTMLDialogElement>('[data-consult-dialog]')
const dialogWidget = dialog ? widgets.get(q<HTMLElement>(dialog, '[data-consult-widget]')!) : undefined
for (const opener of document.querySelectorAll<HTMLElement>('[data-consult-open]')) {
  opener.addEventListener('click', () => {
    if (!dialog || !dialogWidget || typeof dialog.showModal !== 'function') {
      window.location.href = '/consultation'
      return
    }
    if (opener.dataset.consultListing) dialogWidget.setListing(opener.dataset.consultListing)
    dialog.showModal()
    document.body.style.overflow = 'hidden'
    void dialogWidget.load()
  })
}
if (dialog) {
  dialog.addEventListener('close', () => {
    document.body.style.overflow = ''
  })
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog || (event.target as HTMLElement).closest('[data-consult-close]')) dialog.close()
  })
}
