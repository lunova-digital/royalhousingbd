import type { Metadata } from 'next'
import Link from 'next/link'

import * as content from '@/modules/content/service'
import { ListingCard } from '@/modules/listing/components/card'
import * as listings from '@/modules/listing/service'
import { statusCounts } from '@/modules/masterplan/model'
import * as masterplans from '@/modules/masterplan/service'
import { Icon } from '@/modules/site/components/icon'
import { SectionHeading, splitHeading, WhatsAppButton } from '@/modules/site/components/partials'
import { YouTubeEmbed } from '@/modules/site/components/youtube-embed'
import { assetUrl, getSettings, pageMetadata, siteList, whatsapp, type IconName } from '@/modules/site/site'
import { youtubeId } from '@/modules/site/support/youtube'

export async function generateMetadata(): Promise<Metadata> {
  return pageMetadata({ path: '/' })
}

const WHY: [IconName, string, string][] = [
  ['shield', '100% Legal Assurance', 'Every plot and flat comes with verified documents and clear ownership.'],
  ['award', 'Premium Quality', 'Planned infrastructure, roads and utilities in every project.'],
  ['clock', 'Timely Delivery', 'Handover on the committed schedule, project after project.'],
  ['users', 'Expert Team', 'Experienced professionals guide you through every step.'],
  ['pin', 'Prime Locations', 'Strategic locations with excellent connectivity and growth.'],
  ['handshake', 'Trusted Partnership', 'Long-term relationships with the families we serve.'],
]

const MAP_FEATURES: [IconName, string, string, string][] = [
  ['cursor', 'Click Any Plot', 'Simply tap any plot to view its details, size and pricing.', 'text-plot-available bg-plot-available/10'],
  ['info', 'Real-Time Status', 'See live availability: green for available, amber for booked, red for sold.', 'text-brand bg-brand/10'],
  ['zoom', 'Zoom & Navigate', 'Explore different sections of the project with zoom controls.', 'text-accent bg-accent/10'],
]

const TILE: Record<string, string> = {
  available: 'bg-plot-available/70 border-plot-available',
  booked: 'bg-plot-booked/70 border-plot-booked',
  sold: 'bg-plot-sold/70 border-plot-sold',
}
const PATTERN = ['available', 'sold', 'available', 'booked', 'sold', 'available', 'available', 'available', 'available', 'available', 'sold', 'available', 'booked', 'sold', 'available', 'available']

/** The homepage (FR-HOME-*): ticker, hero, featured row, why us, map teaser, videos, CTA. */
export default async function HomePage() {
  const [settings, featured, ticker, videos, maps, stats] = await Promise.all([
    getSettings(),
    listings.featured(),
    content.ticker(true),
    content.allVideos(),
    masterplans.published(),
    siteList<{ value: string; label: string }>('hero_stats'),
  ])
  const siteName = settings.site_name ?? ''
  const heroVideo = youtubeId(settings.hero_video_url)
  const hero = splitHeading(settings.hero_title ?? '', 'half')
  const badges = (settings.trust_badges ?? '').split(',').map((b) => b.trim()).filter(Boolean)
  const whatsappFloat = whatsapp(settings, `Hello, I found you on ${siteName} and I'd like to know more.`)
  const firstMap = maps[0]
  const firstCounts = firstMap ? statusCounts(firstMap) : null

  return (
    <>
      {/* FR-HOME-003: announcement ticker */}
      {ticker.length > 0 && (
        <div className="border-b border-white/5 bg-brand/10">
          <div className="ticker mx-auto max-w-7xl overflow-hidden py-2 text-sm text-slate-200">
            <div className="ticker-track flex w-max gap-12 whitespace-nowrap">
              {[0, 1].map((pass) => ticker.map((item) => item.link ? (
                <a key={`${pass}-${item.id}`} href={item.link} className="inline-flex items-center gap-2 hover:text-white"><span className="size-1.5 rounded-full bg-brand" />{item.text}</a>
              ) : (
                <span key={`${pass}-${item.id}`} className="inline-flex items-center gap-2"><span className="size-1.5 rounded-full bg-brand" />{item.text}</span>
              )))}
            </div>
          </div>
        </div>
      )}

      {/* FR-HOME-001: hero */}
      <section className="section-glow relative overflow-hidden">
        {heroVideo ? (
          <div className="absolute inset-0 -z-10 opacity-40">
            <iframe className="pointer-events-none absolute top-1/2 left-1/2 h-[56.25vw] min-h-full w-[177.78vh] min-w-full -translate-x-1/2 -translate-y-1/2" src={`https://www.youtube-nocookie.com/embed/${heroVideo}?autoplay=1&mute=1&controls=0&loop=1&playlist=${heroVideo}&rel=0&playsinline=1`} title="Hero video" allow="autoplay; encrypted-media" loading="lazy" />
            <div className="absolute inset-0 bg-gradient-to-r from-navy-900 via-navy-900/70 to-navy-900/30" />
          </div>
        ) : settings.hero_image ? (
          <>
            <img src={assetUrl(settings.hero_image)} alt="" className="absolute inset-0 -z-10 size-full object-cover opacity-30" />
            <div className="absolute inset-0 -z-10 bg-gradient-to-r from-navy-900 via-navy-900/80 to-navy-900/40" />
          </>
        ) : null}
        <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:min-h-[calc(100svh-5rem)] lg:grid-cols-2 lg:py-24">
          <div>
            {settings.hero_badge && <span className="chip border-brand/30 bg-brand/10 text-brand">✦ {settings.hero_badge}</span>}
            <h1 className="mt-5 font-display text-5xl font-bold leading-[1.05] tracking-tight text-balance sm:text-6xl lg:text-7xl">{hero.head} <span className="accent">{hero.accent}</span></h1>
            {settings.hero_subtitle && <p className="mt-6 max-w-xl text-lg text-slate-400">{settings.hero_subtitle}</p>}
            <div className="mt-8 flex flex-wrap gap-3">
              {settings.hero_cta_text && <Link href={settings.hero_cta_link || '/projects'} className="btn-primary h-14 px-8 text-base">{settings.hero_cta_text} <Icon name="arrow" className="size-4" /></Link>}
              <Link href="/map" className="btn-outline h-14 px-8 text-base"><Icon name="pin" className="size-4" /> View Project Map</Link>
            </div>
            {stats.length > 0 && (
              <dl className="mt-10 grid grid-cols-3 gap-6 border-t border-white/10 pt-8">
                {stats.map((stat, i) => (
                  <div key={i}><dt className="flex items-center gap-2 text-2xl font-bold sm:text-3xl"><span className="text-brand"><Icon name={(['building', 'users', 'pin'] as IconName[])[i % 3]!} className="size-5" /></span>{stat.value ?? ''}</dt><dd className="mt-1 text-sm text-slate-400">{stat.label ?? ''}</dd></div>
                ))}
              </dl>
            )}
          </div>

          {/* Floating brand card with info chips */}
          <div className="relative mx-auto hidden w-full max-w-md lg:block">
            <div className="card aspect-square overflow-hidden rounded-3xl bg-white p-10">
              {settings.logo ? (
                <img src={assetUrl(settings.logo)} alt="" className="size-full object-contain" />
              ) : (
                <div className="flex size-full flex-col items-center justify-center text-center"><span className="font-display text-6xl font-bold text-brand">R</span><span className="mt-2 font-display text-2xl font-semibold text-navy-900">{siteName}</span></div>
              )}
            </div>
            {firstMap && firstCounts && (
              <div className="card absolute -top-8 -right-6 w-60 bg-navy-800 p-4 shadow-xl shadow-black/40">
                <div className="flex items-center gap-3"><span className="inline-flex size-9 items-center justify-center rounded-full bg-plot-available/20"><span className="size-3 rounded-full bg-plot-available" /></span><span><span className="block text-sm font-semibold">Plots available</span><span className="block text-xs text-slate-400">{firstMap.name}</span></span></div>
                <p className="mt-3 text-2xl font-bold">{firstCounts.available} <span className="text-sm font-normal text-slate-400">of {firstMap.plots.length}</span></p>
              </div>
            )}
            {featured[0] && (
              <div className="card absolute -bottom-6 -left-8 w-56 bg-navy-800 p-4 shadow-xl shadow-black/40">
                <p className="flex items-center gap-2 text-sm font-semibold"><Icon name="pin" className="size-4 text-brand" /> Prime location</p>
                <p className="mt-1 text-xs text-slate-400">{featured[0].location}</p>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* FR-HOME-002: featured listings */}
      {featured.length > 0 && (
        <section className="section-glow mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24">
          <SectionHeading eyebrow="Featured Properties" title="Explore Our Premium Projects" text="Discover carefully planned housing projects with modern amenities, strategic locations and excellent investment potential." />
          <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {featured.slice(0, 6).map((l) => <ListingCard key={l.id} listing={l} settings={settings} />)}
          </div>
          <div className="mt-10 text-center"><Link href="/projects" className="btn-outline h-12 px-6">View All Projects <Icon name="arrow" className="size-4" /></Link></div>
        </section>
      )}

      {/* Why choose us */}
      <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24">
        <SectionHeading eyebrow="Why Choose Us" title="Your Trust, Our Commitment" text="We have established ourselves as a dependable name in property development." />
        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {WHY.map(([icon, heading, text]) => (
            <div key={heading} className="card p-7 text-center transition-colors hover:border-brand/40"><span className="icon-box"><Icon name={icon} className="size-5" /></span><h3 className="mt-4 font-display text-xl font-semibold">{heading}</h3><p className="mt-2 text-sm text-slate-400">{text}</p></div>
          ))}
        </div>
      </section>

      {/* Interactive project map teaser */}
      <section className="section-glow border-y border-white/5">
        <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-2">
          <div>
            <p className="eyebrow">Interactive Project Map</p>
            <h2 className="mt-3 font-display text-4xl font-bold tracking-tight sm:text-5xl">Explore Our Properties</h2>
            <p className="mt-4 text-lg text-slate-400">Use our interactive map to explore available plots, check their status and find your perfect location.</p>
            <ul className="mt-8 grid gap-5">
              {MAP_FEATURES.map(([icon, heading, text, color]) => (
                <li key={heading} className="flex gap-4"><span className={`inline-flex size-12 shrink-0 items-center justify-center rounded-xl ${color}`}><Icon name={icon} className="size-5" /></span><span><span className="block font-display text-lg font-semibold">{heading}</span><span className="block text-sm text-slate-400">{text}</span></span></li>
              ))}
            </ul>
            <Link href="/map" className="btn-primary mt-8 h-12 px-6">Explore Full Project Map <Icon name="arrow" className="size-4" /></Link>
          </div>
          <div className="card p-6">
            <div className="grid grid-cols-[1fr_12px_1fr] gap-x-4 gap-y-6 rounded-xl bg-navy-900/60 p-5">
              {[0, 1].map((row) => (
                <div key={row} className="contents">
                  <div className="grid grid-cols-2 gap-3">{PATTERN.slice(row * 8, row * 8 + 4).map((s, i) => <span key={i} className={`aspect-[4/3] rounded-lg border ${TILE[s]}`} />)}</div>
                  <div className="rounded-full bg-navy-600" />
                  <div className="grid grid-cols-2 gap-3">{PATTERN.slice(row * 8 + 4, row * 8 + 8).map((s, i) => <span key={i} className={`aspect-[4/3] rounded-lg border ${TILE[s]}`} />)}</div>
                  {row === 0 && <div className="col-span-3 h-1.5 rounded-full bg-navy-600" />}
                </div>
              ))}
            </div>
            <div className="mt-4 flex justify-center gap-6 text-xs text-slate-300">
              <span className="flex items-center gap-2"><span className="size-3 rounded-sm bg-plot-available" />Available</span>
              <span className="flex items-center gap-2"><span className="size-3 rounded-sm bg-plot-booked" />Booked</span>
              <span className="flex items-center gap-2"><span className="size-3 rounded-sm bg-plot-sold" />Sold</span>
            </div>
          </div>
        </div>
      </section>

      {/* FR-HOME-004: YouTube showcase */}
      {videos.length > 0 && (
        <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
          <SectionHeading eyebrow="Videos" title="See Our Projects In Action" text="Walkthroughs and updates from our projects." />
          <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {videos.map((video) => {
              const id = youtubeId(video.url)
              return id ? (
                <figure key={video.id}>
                  <YouTubeEmbed id={id} />
                  {video.title && <figcaption className="mt-2 text-sm text-slate-400">{video.title}</figcaption>}
                </figure>
              ) : null
            })}
          </div>
        </section>
      )}

      {/* CTA */}
      <section className="section-glow mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 sm:py-24">
        <h2 className="font-display text-4xl font-bold tracking-tight sm:text-5xl">Ready to Find Your <span className="accent">Perfect Property</span>?</h2>
        <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-400">Take the first step towards owning your dream home. Our team is ready to guide you through our projects and help you make the best investment.</p>
        <div className="mt-8 flex flex-wrap justify-center gap-3">
          <Link href="/contact" className="btn-primary h-14 px-8 text-base">Schedule Consultation <Icon name="arrow" className="size-4" /></Link>
          {settings.phone && <a href={`tel:${settings.phone}`} className="btn-outline h-14 px-8 text-base"><Icon name="phone" className="size-4" /> Call Us Now</a>}
          {settings.whatsapp_number && <WhatsAppButton href={whatsappFloat} label="WhatsApp" className="h-14 px-8 text-base" />}
        </div>
        {badges.length > 0 && (
          <div className="mx-auto mt-12 max-w-3xl border-t border-white/10 pt-8">
            <p className="text-sm text-slate-500">Trusted by leading organizations</p>
            <div className="mt-4 flex flex-wrap justify-center divide-x divide-white/10">
              {badges.map((badge) => <span key={badge} className="px-6 font-display text-lg font-semibold text-slate-300">{badge}</span>)}
            </div>
          </div>
        )}
      </section>
    </>
  )
}
