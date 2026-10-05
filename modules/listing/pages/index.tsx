import type { Metadata } from 'next'
import Link from 'next/link'
import { notFound } from 'next/navigation'

import { Icon } from '@/modules/site/components/icon'
import { PageHero } from '@/modules/site/components/partials'
import { getSettings, pageMetadata } from '@/modules/site/site'

import { ListingCard } from '../components/card'
import { typeLabel } from '../model'
import * as listings from '../service'
import { isListingType, LISTING_TYPES } from '../types'

const PER_PAGE = 12

type Search = Record<string, string | string[] | undefined>
const first = (v: string | string[] | undefined) => (Array.isArray(v) ? v[0] : v) ?? ''

/** The public listing grids: /projects (all types) and /property, /plot, /flat (FR-LIST-001/002). */
export interface IndexProps {
  /** null for /projects, otherwise the type segment of the URL */
  type: string | null
  searchParams: Search
}

function resolveType(props: IndexProps): string | null {
  if (props.type !== null) {
    if (!isListingType(props.type)) notFound()
    return props.type
  }
  const q = first(props.searchParams.type)
  return isListingType(q) ? q : null
}

export async function indexMetadata(props: IndexProps): Promise<Metadata> {
  const type = resolveType(props)
  const title = type ? `${typeLabel(type)} listings` : 'Our projects'
  return pageMetadata({
    title,
    description: `Browse ${title} in Bangladesh. Contact us on WhatsApp for details and site visits.`,
    path: props.type ? `/${props.type}` : '/projects',
  })
}

export async function ListingIndexPage(props: IndexProps) {
  const type = resolveType(props)
  const filter = { type, location: first(props.searchParams.location), q: first(props.searchParams.q), published: true }
  const [settings, page, locs] = await Promise.all([
    getSettings(),
    listings.search(filter, PER_PAGE, Number(first(props.searchParams.page)) || 1),
    listings.locations(type),
  ])
  const path = props.type ? `/${props.type}` : '/projects'
  const pageLink = (p: number) => {
    const params = new URLSearchParams()
    if (filter.q) params.set('q', filter.q)
    if (filter.location) params.set('location', filter.location)
    if (p > 1) params.set('page', String(p))
    const qs = params.toString()
    return qs ? `${path}?${qs}` : path
  }

  return (
    <>
      <PageHero eyebrow="Our Projects" title="Explore Premium" accent="Properties" text="Discover carefully planned housing projects, plots and flats with modern amenities, strategic locations and excellent investment potential." />

      {/* FR-LIST-002: search by title/location, filter by type and location */}
      <section className="sticky top-20 z-30 border-y border-white/5 bg-navy-900/95 py-3 backdrop-blur-xl md:py-4">
        <form method="get" action={path} className="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-4 sm:px-6">
          <label className="relative min-w-56 flex-1 sm:max-w-xs">
            <span className="sr-only">Search</span>
            <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-500"><Icon name="zoom-out" className="size-4" /></span>
            <input type="search" name="q" defaultValue={filter.q} placeholder="Search projects…" className="input h-11 pl-10" />
          </label>
          <select name="location" defaultValue={filter.location} className="input h-11 w-auto min-w-44">
            <option value="">All locations</option>
            {locs.map((l) => <option key={l} value={l}>{l}</option>)}
          </select>
          <button type="submit" className="btn-outline h-11">Filter</button>
          <div className="ml-auto flex flex-wrap items-center gap-2">
            <span className="text-slate-500"><Icon name="tag" className="size-4" /></span>
            <Link href="/projects" className={`rounded-full px-4 py-1.5 text-sm font-medium ${type ? 'bg-navy-700 text-slate-300 hover:text-white' : 'bg-brand text-white'}`}>All</Link>
            {LISTING_TYPES.map((t) => (
              <Link key={t} href={`/${t}`} className={`rounded-full px-4 py-1.5 text-sm font-medium ${type === t ? 'bg-brand text-white' : 'bg-navy-700 text-slate-300 hover:text-white'}`}>{typeLabel(t)}</Link>
            ))}
          </div>
        </form>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        {page.items.length ? (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {page.items.map((l) => <ListingCard key={l.id} listing={l} settings={settings} />)}
          </div>
        ) : (
          <p className="card p-16 text-center text-slate-400">No projects match. Try a different search or location.</p>
        )}

        {page.lastPage > 1 && (
          <nav className="mt-12 flex flex-wrap items-center justify-center gap-2" aria-label="Pagination">
            {Array.from({ length: page.lastPage }, (_, i) => i + 1).map((p) => (
              <Link key={p} href={pageLink(p)} aria-current={p === page.page ? 'page' : undefined} className={`inline-flex size-10 items-center justify-center rounded-lg border text-sm font-medium ${p === page.page ? 'border-brand bg-brand text-white' : 'border-white/10 text-slate-300 hover:bg-white/5'}`}>{p}</Link>
            ))}
          </nav>
        )}
      </section>
    </>
  )
}
