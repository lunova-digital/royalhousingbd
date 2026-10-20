// Sitewide motion, loaded lazily by site.ts (never for visitors who prefer reduced motion, and after first paint).
// Slow, smooth, never bouncy: ease cubic-bezier(.22,1,.36,1), 400-700ms reveals, springs (stiffness 300, damping 30) for
// sheets, modals and dropdowns. Only transform and opacity are animated.
import { animate, inView, scroll, stagger } from 'motion'

const ease = [0.22, 1, 0.36, 1] as const
const bnDigits = '০১২৩৪৫৬৭৮৯'
const lang = document.documentElement.lang
const toLocalDigits = (s: string) => (lang === 'bn' ? s.replace(/\d/g, (d) => bnDigits[Number(d)]!) : s)
const toAscii = (s: string) => s.replace(/[০-৯]/g, (d) => String(bnDigits.indexOf(d)))

// 1. Scroll reveals: sections fade up 16px once; grids stagger their cards by 60ms.
for (const el of document.querySelectorAll<HTMLElement>('.reveal')) {
  inView(
    el,
    () => {
      if (el.dataset.revealed) return
      el.dataset.revealed = '1'
      el.classList.add('in')
      animate(el, { opacity: [0, 1], transform: ['translateY(16px)', 'translateY(0px)'] }, { duration: 0.7, ease })
    },
    { amount: 0.08, margin: '0px 0px -6% 0px' },
  )
}
for (const grid of document.querySelectorAll<HTMLElement>('[data-stagger]')) {
  inView(
    grid,
    () => {
      if (grid.dataset.revealed) return
      grid.dataset.revealed = '1'
      grid.classList.add('in')
      animate(Array.from(grid.children) as HTMLElement[], { opacity: [0, 1], transform: ['translateY(16px)', 'translateY(0px)'] }, { delay: stagger(0.06), duration: 0.6, ease })
    },
    { amount: 0.1, margin: '0px 0px -6% 0px' },
  )
}

// 2. The gold rule under section titles draws from 0 to full width.
for (const title of document.querySelectorAll<HTMLElement>('.section-title')) {
  inView(title, () => void title.classList.add('rule-in'), { amount: 0.6 })
}

// 3. Stats count up from 0 when they enter the viewport (Bangla digits in বাং).
for (const el of document.querySelectorAll<HTMLElement>('[data-count-up]')) {
  const m = /^(\D*)([\d.,০-৯]+)(.*)$/.exec(toAscii(el.textContent?.trim() ?? ''))
  if (!m) continue
  const [, prefix, number, suffix] = m
  const target = parseFloat(number!.replace(/,/g, ''))
  if (!Number.isFinite(target)) continue
  const decimals = (number!.split('.')[1] ?? '').length
  el.style.minWidth = `${el.getBoundingClientRect().width}px` // no layout shift while counting
  inView(el, () => {
    if (el.dataset.counted) return
    el.dataset.counted = '1'
    animate(0, target, {
      duration: 1.4,
      ease,
      onUpdate: (v: number) => {
        const shown = decimals ? v.toFixed(decimals) : Math.round(v).toLocaleString('en')
        el.textContent = toLocalDigits(`${prefix}${shown.replace(/,/g, number!.includes(',') ? ',' : '')}${suffix}`)
      },
    })
  })
}

// 4. Hero: the background drifts slower than the page.
const hero = document.querySelector<HTMLElement>('[data-hero]')
const par = hero?.querySelector<HTMLElement>('.hero-par')
if (hero && par) scroll(animate(par, { transform: ['translateY(0px)', 'translateY(70px)'] }, { ease: 'linear' }), { target: hero, offset: ['start start', 'end start'] })

// 5. Sheets, modals and dropdowns spring in (stiffness 300, damping 30). Their own CSS entrance is switched off first.
const spring = { type: 'spring', stiffness: 300, damping: 30 } as const
const rise = (el: HTMLElement, from = 36) => {
  el.style.animation = 'none'
  animate(el, { opacity: [0, 1], transform: [`translateY(${from}px) scale(0.985)`, 'translateY(0px) scale(1)'] }, spring)
}
const watched = 'dialog, [data-share-pop], [data-user-panel], .plot-sheet'
const seen = new WeakSet<Element>()
new MutationObserver((records) => {
  for (const r of records) {
    const el = r.target as HTMLElement
    if (!el.matches(watched)) continue
    const visible = el instanceof HTMLDialogElement ? el.open : !el.hasAttribute('hidden')
    if (visible && !seen.has(el)) {
      seen.add(el)
      const sheet = el.matches('.sheet, .auth-dialog, .consult-dialog, .plot-sheet, .share-pop') && window.innerWidth < 640
      rise(el, sheet ? 56 : 14)
    } else if (!visible) seen.delete(el)
  }
}).observe(document.body, { subtree: true, attributes: true, attributeFilter: ['open', 'hidden'] })

export {}
