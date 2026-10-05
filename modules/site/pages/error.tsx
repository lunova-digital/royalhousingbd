'use client'

import Link from 'next/link'

/** The 500 page inside the public layout. */
export default function SiteError({ reset }: { error: Error; reset: () => void }) {
  return (
    <section className="section-glow mx-auto flex max-w-7xl flex-col items-center px-4 py-28 text-center sm:px-6">
      <p className="eyebrow">500</p>
      <h1 className="mt-3 font-display text-4xl font-bold">Something <span className="accent">went wrong</span></h1>
      <p className="mt-3 text-slate-400">Please try again in a moment.</p>
      <div className="mt-8 flex gap-3">
        <button type="button" onClick={reset} className="btn-outline">Try again</button>
        <Link href="/" className="btn-primary">Back to home</Link>
      </div>
    </section>
  )
}
