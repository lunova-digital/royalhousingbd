import Link from 'next/link'

import type { SettingsMap } from '@/modules/settings/settings'
import { Icon } from '@/modules/site/components/icon'
import { assetUrl, whatsapp } from '@/modules/site/site'

import { availability, cover, listingUrl, priceLabel, reference, sizeLabel, typeLabel } from '../model'
import type { ListingWithPlan } from '../types'

/** Project card, shared by the grids and the homepage featured row. */
export function ListingCard({ listing, settings }: { listing: ListingWithPlan; settings: SettingsMap }) {
  const img = cover(listing)
  const avail = availability(listing)
  const url = listingUrl(listing)

  return (
    <article className="card group flex flex-col overflow-hidden transition-colors hover:border-white/15">
      <Link href={url} className="relative block aspect-[16/10] overflow-hidden bg-navy-700">
        {img ? (
          <img src={assetUrl(img.thumb_path || img.path)} alt="" loading="lazy" className="size-full object-cover transition-transform duration-500 group-hover:scale-105" />
        ) : (
          <div className="flex size-full items-center justify-center text-sm text-slate-500">No photo yet</div>
        )}
        <div className="absolute inset-0 bg-gradient-to-t from-navy-950/90 via-navy-950/20 to-transparent" />
        <span className="absolute top-3 left-3 rounded-full bg-plot-available/20 px-3 py-1 text-xs font-medium text-emerald-300 backdrop-blur">
          {avail ? `${avail.available} plots available` : typeLabel(listing.type)}
        </span>
        <span className="absolute bottom-3 left-3 flex items-center gap-1.5 text-sm text-white"><Icon name="pin" className="size-4 text-brand" />{listing.location}</span>
      </Link>
      <div className="flex flex-1 flex-col gap-4 p-5">
        <h3 className="font-display text-xl font-semibold leading-snug"><Link href={url} className="hover:text-brand">{listing.title}</Link></h3>
        <dl className="grid gap-2 text-sm">
          <div className="flex items-center justify-between gap-3"><dt className="flex items-center gap-2 text-slate-400"><Icon name="expand" className="size-4" /> {listing.type === 'flat' ? 'Size' : 'Plot Size'}</dt><dd className="font-medium text-white">{sizeLabel(listing) || '—'}</dd></div>
          <div className="flex items-center justify-between gap-3"><dt className="flex items-center gap-2 text-slate-400"><Icon name="tag" className="size-4" /> Price</dt><dd className="font-medium text-white">{priceLabel(listing)}</dd></div>
          {listing.type === 'flat' && listing.bedrooms > 0 && (
            <div className="flex items-center justify-between gap-3"><dt className="flex items-center gap-2 text-slate-400"><Icon name="home" className="size-4" /> Bedrooms</dt><dd className="font-medium text-white">{listing.bedrooms} bed · {listing.baths} bath</dd></div>
          )}
        </dl>
        {avail && (
          <div>
            <div className="flex justify-between text-xs text-slate-400"><span>Availability</span><span>{avail.percent}%</span></div>
            <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-navy-600"><div className="availability-bar h-full rounded-full" style={{ width: `${avail.percent}%` }} /></div>
          </div>
        )}
        <div className="mt-auto flex gap-2 pt-1">
          <Link href={url} className="btn-outline h-11 flex-1">View Details <Icon name="arrow" className="size-4" /></Link>
          {settings.whatsapp_number && (
            <a href={whatsapp(settings, `Hello, I would like to book "${listing.title}" (Ref: ${reference(listing)}).`)} target="_blank" rel="noreferrer" className="btn h-11 bg-plot-available px-4 text-white hover:bg-emerald-600"><Icon name="calendar" className="size-4" /> Book</a>
          )}
        </div>
      </div>
    </article>
  )
}
