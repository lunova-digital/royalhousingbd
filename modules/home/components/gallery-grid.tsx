'use client'

import Link from 'next/link'
import { useState } from 'react'

import { Icon } from '@/modules/site/components/icon'
import { LightboxTrigger } from '@/modules/site/components/lightbox'

export interface Photo {
  src: string
  thumb: string
  category: string
  caption: string
  location: string
  href: string
}

const FILTERS: [string, string][] = [['all', 'All Photos'], ['property', 'Property'], ['plot', 'Land Plots'], ['flat', 'Flats'], ['masterplan', 'Masterplans']]

/** The gallery page: filter chips over a grid that opens the lightbox. */
export function GalleryGrid({ photos }: { photos: Photo[] }) {
  const [filter, setFilter] = useState('all')
  const shown = photos.filter((p) => filter === 'all' || p.category === filter)

  return (
    <>
      <section className="sticky top-20 z-30 border-y border-white/5 bg-navy-900/95 py-4 backdrop-blur-xl">
        <div className="mx-auto flex max-w-7xl flex-wrap justify-center gap-2 px-4 sm:px-6" role="group" aria-label="Filter photos">
          {FILTERS.map(([key, label]) => (
            <button key={key} type="button" onClick={() => setFilter(key)} className={`rounded-full px-5 py-2 text-sm font-medium ${key === filter ? 'bg-brand text-white' : 'bg-navy-700 text-slate-300 hover:text-white'}`}>{label}</button>
          ))}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        {photos.length === 0 ? (
          <p className="card p-16 text-center text-slate-400">No photos yet.</p>
        ) : shown.length === 0 ? (
          <p className="card p-16 text-center text-slate-400">No photos in this category yet.</p>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {shown.map((photo, i) => (
              <figure key={`${photo.src}-${i}`} className="group relative aspect-square overflow-hidden rounded-2xl border border-white/8 bg-navy-800">
                <LightboxTrigger src={photo.src} className="block size-full cursor-zoom-in">
                  <img src={photo.thumb} alt={photo.caption} loading="lazy" className="size-full object-cover transition-transform duration-500 group-hover:scale-105" />
                </LightboxTrigger>
                <figcaption className="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-navy-950/90 to-transparent p-3 text-xs">
                  <Link href={photo.href} className="pointer-events-auto flex items-center gap-1.5 text-white"><Icon name="pin" className="size-3.5 text-brand" />{photo.location}</Link>
                </figcaption>
              </figure>
            ))}
          </div>
        )}
      </section>
    </>
  )
}
