import type { Metadata } from 'next'
import Link from 'next/link'

import { limit } from '@/lib/text'
import { Icon } from '@/modules/site/components/icon'
import { PageHero } from '@/modules/site/components/partials'
import { assetUrl, pageMetadata } from '@/modules/site/site'

import { masterplanUrl, statusCounts } from '../model'
import * as masterplans from '../service'

export async function generateMetadata(): Promise<Metadata> {
  return pageMetadata({
    title: 'Project Map',
    description: 'Choose a project to explore its interactive masterplan with live plot availability.',
    path: '/map',
  })
}

/** /map: the project cards that open an interactive layout. */
export default async function MasterplanIndexPage() {
  const maps = await masterplans.published()

  return (
    <>
      <PageHero eyebrow="Interactive Experience" icon="building" title="Choose a Project" accent="to Explore" text="Browse our housing projects and click on any one to explore the interactive land map with real-time plot availability." />

      <section className="bg-navy-900 py-8 sm:py-16">
        <div className="container-x">
          {maps.length ? (
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {maps.map((map) => {
                const c = statusCounts(map)
                const prices = [...new Set(map.plots.map((p) => p.price).filter(Boolean))]
                return (
                  <Link key={map.id} href={masterplanUrl(map)} className="group block cursor-pointer overflow-hidden rounded-xl border border-navy-500 bg-navy-800 transition-colors hover:border-brand/50">
                    <div className="relative h-48 overflow-hidden sm:h-56">
                      {map.image_path ? <img src={assetUrl(map.image_path)} alt={map.name} loading="lazy" className="size-full object-cover" /> : <div className="size-full bg-navy-700" />}
                      <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
                      <div className="absolute top-4 right-4"><span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/90 px-3 py-1.5 text-xs font-semibold text-white">{c.available} Available</span></div>
                      <div className="absolute right-4 bottom-4 left-4">
                        <h3 className="mb-1 font-display text-xl font-bold text-white sm:text-2xl">{map.name}</h3>
                        {map.description && <div className="flex items-center gap-2 text-sm text-white/80"><Icon name="pin" className="size-4" />{limit(map.description, 60)}</div>}
                      </div>
                    </div>
                    <div className="p-5">
                      <div className="mb-4 grid grid-cols-2 gap-4">
                        <div className="rounded-lg bg-navy-600/50 p-3 text-center"><div className="text-lg font-bold text-fg">{map.plots.length}</div><div className="text-xs text-muted-fg">Total Plots</div></div>
                        <div className="rounded-lg bg-navy-600/50 p-3 text-center"><div className="truncate text-lg font-bold text-brand">{prices.length === 1 ? prices[0] : prices.length > 1 ? `${prices.length} price tiers` : 'Call for Price'}</div><div className="text-xs text-muted-fg">Price Range</div></div>
                      </div>
                      <div className="mb-4 flex flex-wrap gap-2">
                        <span className="chip"><span className="size-2 rounded-full bg-plot-available" />{c.available} available</span>
                        <span className="chip"><span className="size-2 rounded-full bg-plot-booked" />{c.booked} booked</span>
                        <span className="chip"><span className="size-2 rounded-full bg-plot-sold" />{c.sold} sold</span>
                      </div>
                      <span className="btn-primary w-full"><Icon name="pin" className="size-4" /> View Map</span>
                    </div>
                  </Link>
                )
              })}
            </div>
          ) : (
            <p className="card p-16 text-center text-muted-fg">No project maps published yet.</p>
          )}
        </div>
      </section>
    </>
  )
}
