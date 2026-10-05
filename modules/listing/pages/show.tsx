import type { Metadata } from 'next'
import Link from 'next/link'
import { notFound } from 'next/navigation'

import { masterplanUrl } from '@/modules/masterplan/model'
import { Icon } from '@/modules/site/components/icon'
import { LightboxTrigger } from '@/modules/site/components/lightbox'
import { Multiline, WhatsAppButton } from '@/modules/site/components/partials'
import { YouTubeEmbed } from '@/modules/site/components/youtube-embed'
import { assetUrl, baseUrl, getSettings, pageMetadata, whatsapp } from '@/modules/site/site'
import { youtubeId } from '@/modules/site/support/youtube'

import { ListingCard } from '../components/card'
import { ListingGallery } from '../components/gallery'
import { availability, cover, listingUrl, priceLabel, reference, sizeLabel, typeLabel } from '../model'
import * as listings from '../service'
import { isListingType, type ListingWithPlan } from '../types'

export interface ShowProps {
  type: string
  slug: string
}

async function load({ type, slug }: ShowProps): Promise<ListingWithPlan> {
  if (!isListingType(type)) notFound()
  return (await listings.findPublished(type, slug)) ?? notFound()
}

export async function showMetadata(props: ShowProps): Promise<Metadata> {
  const l = await load(props)
  return pageMetadata({
    title: l.title,
    description: `${typeLabel(l.type)} in ${l.location}. ${priceLabel(l)}.`,
    image: cover(l)?.path,
    path: listingUrl(l),
  })
}

const WHY: [Parameters<typeof Icon>[0]['name'], string, string][] = [
  ['shield', 'Legal Security', 'All documents verified'],
  ['award', 'Quality Development', 'Planned infrastructure'],
  ['users', 'Community', 'Family-friendly environment'],
  ['clock', 'Timely Delivery', 'As per commitment'],
]

/** FR-LIST-003/004: the detail page with gallery, map, facilities, video and the WhatsApp reference. */
export async function ListingShowPage(props: ShowProps) {
  const l = await load(props)
  const [settings, base, related] = await Promise.all([getSettings(), baseUrl(), listings.related(l)])
  const message = `Hello, I'm interested in "${l.title}" (${l.location}, Ref: ${reference(l)}). ${base}${listingUrl(l)}`
  const wa = whatsapp(settings, message)
  const avail = availability(l)
  const plan = l.masterplan
  const videoId = youtubeId(l.video_url)
  const label = typeLabel(l.type)

  return (
    <>
      <section className="section-glow">
        <div className="mx-auto max-w-7xl px-4 pt-8 pb-10 sm:px-6">
          <Link href={`/${l.type}`} className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white"><Icon name="back" className="size-4" /> All {label} listings</Link>

          <div className="mt-5 grid gap-8 lg:grid-cols-2">
            <ListingGallery images={l.images} title={l.title} />

            <div>
              <span className="chip border-plot-available/30 bg-plot-available/15 text-emerald-300">{avail ? `${avail.available} plots available` : label}</span>
              <h1 className="mt-4 font-display text-4xl font-bold tracking-tight sm:text-5xl">{l.title}</h1>
              <p className="mt-3 flex items-center gap-2 text-slate-300"><Icon name="pin" className="size-4 text-brand" /> {l.location}</p>
              {l.description && <p className="mt-4 line-clamp-3 text-slate-400">{l.description}</p>}

              <div className="mt-6 grid grid-cols-2 gap-3">
                <div className="card p-4"><p className="flex items-center gap-2 text-xs text-slate-400"><Icon name="expand" className="size-4" /> {l.type === 'flat' ? 'Size' : 'Plot Size'}</p><p className="mt-2 text-lg font-bold">{sizeLabel(l) || '—'}</p></div>
                <div className="card p-4"><p className="flex items-center gap-2 text-xs text-slate-400"><Icon name="tag" className="size-4" /> Price</p><p className="mt-2 text-lg font-bold text-emerald-400">{priceLabel(l)}</p></div>
              </div>
              {avail && (
                <div className="card mt-3 p-4">
                  <div className="flex justify-between text-sm"><span className="text-slate-400">Availability</span><span className="font-medium">{avail.available} / {avail.total} plots</span></div>
                  <div className="mt-2 h-2 overflow-hidden rounded-full bg-navy-600"><div className="availability-bar h-full rounded-full" style={{ width: `${avail.percent}%` }} /></div>
                </div>
              )}

              {/* FR-LIST-004: WhatsApp with the pre-filled reference */}
              <div className="mt-6 grid gap-3 sm:grid-cols-3">
                {settings.whatsapp_number && <a href={wa} target="_blank" rel="noreferrer" className="btn-primary"><Icon name="calendar" className="size-4" /> Book Now</a>}
                {settings.phone && <a href={`tel:${settings.phone}`} className="btn-outline"><Icon name="phone" className="size-4" /> Call Now</a>}
                {settings.whatsapp_number && <WhatsAppButton href={wa} label="WhatsApp" />}
              </div>
              <p className="mt-3 text-xs text-slate-500">Reference: {reference(l)}</p>
            </div>
          </div>
        </div>
      </section>

      {plan?.image_path && (
        <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
          <h2 className="font-display text-3xl font-bold">Project Map</h2>
          <p className="mt-1 text-sm text-slate-400">Click the map to open it full size, or explore the interactive version to check every plot.</p>
          <div className="card relative mt-5 overflow-hidden">
            <LightboxTrigger src={assetUrl(plan.image_path)} className="block w-full cursor-zoom-in bg-navy-950/60">
              <img src={assetUrl(plan.image_path)} alt={`${plan.name} masterplan`} loading="lazy" className="mx-auto max-h-[70svh] object-contain" />
            </LightboxTrigger>
            <div className="absolute top-4 right-4 flex gap-2">
              <LightboxTrigger src={assetUrl(plan.image_path)} className="btn-outline h-10 bg-navy-900/80 px-4 text-xs"><Icon name="expand" className="size-4" /> View larger</LightboxTrigger>
              {plan.published && <Link href={masterplanUrl(plan)} className="btn-primary h-10 px-4 text-xs"><Icon name="map" className="size-4" /> Interactive map</Link>}
            </div>
          </div>
        </section>
      )}

      {(l.facilities.length > 0 || l.description) && (
        <section className="border-y border-white/5 bg-navy-950/40">
          <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            <h2 className="font-display text-3xl font-bold">Amenities &amp; Features</h2>
            <div className="mt-6 grid gap-6 lg:grid-cols-[1fr_1.2fr]">
              {l.facilities.length > 0 && (
                <ul className="grid gap-3 sm:grid-cols-2">
                  {l.facilities.map((f) => <li key={f} className="card flex items-center gap-3 p-4 text-sm"><Icon name="check" className="size-5 shrink-0 text-plot-available" />{f}</li>)}
                </ul>
              )}
              {l.description && <div className="card p-6 leading-relaxed text-slate-300"><Multiline text={l.description} /></div>}
            </div>
            {/* Key information (SRS §4.2 fields) */}
            <dl className="mt-8 grid grid-cols-2 gap-x-6 gap-y-4 text-sm sm:grid-cols-4">
              <div><dt className="text-slate-500">Type</dt><dd className="mt-1 font-medium">{label}</dd></div>
              <div><dt className="text-slate-500">Location</dt><dd className="mt-1 font-medium">{l.location}</dd></div>
              {l.type === 'property' ? (
                <>
                  {l.land_area && <div><dt className="text-slate-500">Land area</dt><dd className="mt-1 font-medium">{l.land_area}</dd></div>}
                  {l.built_area && <div><dt className="text-slate-500">Built area</dt><dd className="mt-1 font-medium">{l.built_area}</dd></div>}
                </>
              ) : l.type === 'flat' ? (
                <>
                  {l.size && <div><dt className="text-slate-500">Size</dt><dd className="mt-1 font-medium">{l.size}</dd></div>}
                  {l.bedrooms > 0 && <div><dt className="text-slate-500">Bedrooms</dt><dd className="mt-1 font-medium">{l.bedrooms}</dd></div>}
                  {l.baths > 0 && <div><dt className="text-slate-500">Baths</dt><dd className="mt-1 font-medium">{l.baths}</dd></div>}
                  {l.floor && <div><dt className="text-slate-500">Floor</dt><dd className="mt-1 font-medium">{l.floor}</dd></div>}
                </>
              ) : (
                l.size && <div><dt className="text-slate-500">Size</dt><dd className="mt-1 font-medium">{l.size}</dd></div>
              )}
              <div><dt className="text-slate-500">Reference</dt><dd className="mt-1 font-medium">{reference(l)}</dd></div>
            </dl>
          </div>
        </section>
      )}

      {videoId && (
        <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
          <h2 className="font-display text-3xl font-bold">Video</h2>
          <div className="mx-auto mt-6 max-w-4xl"><YouTubeEmbed id={videoId} /></div>
        </section>
      )}

      <section className="section-glow mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <h2 className="text-center font-display text-3xl font-bold">Why Choose This {label}?</h2>
        <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {WHY.map(([icon, heading, text]) => (
            <div key={heading} className="card p-6 text-center"><span className="icon-box"><Icon name={icon} className="size-5" /></span><h3 className="mt-4 font-display text-lg font-semibold">{heading}</h3><p className="mt-1 text-sm text-slate-400">{text}</p></div>
          ))}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 pb-16 sm:px-6">
        <div className="card mx-auto max-w-2xl p-8 text-center sm:p-12">
          <h2 className="font-display text-3xl font-bold">Need More Information?</h2>
          <p className="mt-2 text-slate-400">Get in touch with our team and get all your questions answered.</p>
          <div className="mt-6 flex flex-wrap justify-center gap-3">
            {settings.whatsapp_number && <a href={wa} target="_blank" rel="noreferrer" className="btn-primary"><Icon name="calendar" className="size-4" /> Book Now</a>}
            <Link href="/contact" className="btn-outline">Schedule Consultation</Link>
            {plan?.published && <Link href={masterplanUrl(plan)} className="btn-outline">View Project Map</Link>}
          </div>
        </div>
      </section>

      {related.length > 0 && (
        <section className="mx-auto max-w-7xl px-4 pb-16 sm:px-6">
          <h2 className="font-display text-3xl font-bold">More {label} listings</h2>
          <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {related.map((item) => <ListingCard key={item.id} listing={item} settings={settings} />)}
          </div>
        </section>
      )}
    </>
  )
}
