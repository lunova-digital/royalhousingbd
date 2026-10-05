import Link from 'next/link'

import { SiteShell } from '../components/shell'
import { SiteDocument } from '../layout'
import { DEFAULTS } from '@/modules/settings/settings'

export function NotFoundContent() {
  return (
    <section className="section-glow mx-auto flex max-w-7xl flex-col items-center px-4 py-28 text-center sm:px-6">
      <p className="eyebrow">404</p>
      <h1 className="mt-3 font-display text-4xl font-bold">Page <span className="accent">not found</span></h1>
      <p className="mt-3 text-slate-400">The page you are looking for does not exist or was removed.</p>
      <Link href="/" className="btn-primary mt-8">Back to home</Link>
    </section>
  )
}

/** Inside the public layout: a listing or map slug that does not exist. */
export function SiteNotFound() {
  return <NotFoundContent />
}

/** The application-wide 404: rendered without any layout (so it brings its own document) and prerendered at build, so it uses the default settings. */
export default function GlobalNotFound() {
  return (
    <SiteDocument>
      <SiteShell settings={DEFAULTS}>
        <NotFoundContent />
      </SiteShell>
    </SiteDocument>
  )
}
