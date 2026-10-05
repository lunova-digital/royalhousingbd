import type { ReactNode } from 'react'

import { Icon } from './icon'
import type { IconName } from '../shared'

export function PageHero({ eyebrow, icon, title, accent, text }: { eyebrow: string; icon?: IconName; title: string; accent: string; text?: string }) {
  return (
    <section className="section-glow pt-8 pb-8 sm:pt-12 sm:pb-12">
      <div className="container-x">
        <div className="mx-auto max-w-4xl text-center">
          <span className="mb-4 inline-flex items-center gap-2 rounded-full border border-brand/20 bg-brand/10 px-4 py-2 text-xs font-medium uppercase tracking-wider text-brand sm:text-sm">
            {icon && <Icon name={icon} className="size-4" />}
            {eyebrow}
          </span>
          <h1 className="mt-3 mb-4 font-display text-3xl font-bold text-fg sm:mt-4 sm:mb-6 sm:text-4xl md:text-5xl">
            {title} <span className="accent">{accent}</span>
          </h1>
          {text && <p className="mx-auto max-w-2xl text-sm text-muted-fg sm:text-lg">{text}</p>}
        </div>
      </div>
    </section>
  )
}

export function SectionHeading({ eyebrow, title, text }: { eyebrow?: string; title: string; text?: string }) {
  return (
    <div className="mx-auto max-w-2xl text-center">
      {eyebrow && <p className="eyebrow">{eyebrow}</p>}
      <h2 className="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">{title}</h2>
      {text && <p className="mt-3 text-slate-400">{text}</p>}
    </div>
  )
}

export function WhatsAppButton({ href, label, className = '' }: { href: string; label: string; className?: string }) {
  return (
    <a href={href} target="_blank" rel="noreferrer" className={`btn-blue ${className}`}>
      <Icon name="whatsapp" className="size-4" />
      <span>{label}</span>
    </a>
  )
}

/** Splits a headline so its last word (or the second half) is the red accent. */
export function splitHeading(text: string, mode: 'last' | 'half' = 'last'): { head: string; accent: string } {
  const words = text.trim().split(/\s+/).filter(Boolean)
  if (words.length < 2) return { head: text.trim(), accent: '' }
  const split = mode === 'half' ? Math.max(1, Math.ceil(words.length / 2)) : words.length - 1
  return { head: words.slice(0, split).join(' '), accent: words.slice(split).join(' ') }
}

/** Plain text with the line breaks kept, like Blade's nl2br(e($text)). */
export function Multiline({ text }: { text: string }): ReactNode {
  const lines = text.split('\n')
  return lines.map((line, i) => (
    <span key={i}>
      {line}
      {i < lines.length - 1 && <br />}
    </span>
  ))
}
