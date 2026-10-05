import type { Metadata } from 'next'

import { Multiline, PageHero, splitHeading } from '@/modules/site/components/partials'
import { getSettings, pageMetadata } from '@/modules/site/site'

const PAGES = {
  privacy: { heading: 'Privacy Policy', key: 'privacy_text', path: '/privacy' },
  terms: { heading: 'Terms of Service', key: 'terms_text', path: '/terms' },
} as const

export type LegalPage = keyof typeof PAGES

export async function legalMetadata(page: LegalPage): Promise<Metadata> {
  return pageMetadata({ title: PAGES[page].heading, path: PAGES[page].path })
}

/** /privacy and /terms: settings-driven text. */
export async function LegalPageView({ page }: { page: LegalPage }) {
  const { heading, key } = PAGES[page]
  const settings = await getSettings()
  const body = (settings[key] ?? '').trim()
  const title = splitHeading(heading)

  return (
    <>
      <PageHero eyebrow="Legal" title={title.head} accent={title.accent} />
      <section className="mx-auto max-w-3xl px-4 pb-20 sm:px-6">
        <div className="card p-8 leading-relaxed text-slate-300">
          {body ? <Multiline text={body} /> : <p className="text-slate-400">This page has not been written yet. Add the text under Settings → Legal in the admin panel.</p>}
        </div>
      </section>
    </>
  )
}
