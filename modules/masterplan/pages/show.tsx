import type { Metadata } from 'next'
import Link from 'next/link'
import { notFound } from 'next/navigation'

import { Icon } from '@/modules/site/components/icon'
import { assetUrl, baseUrl, getSettings, pageMetadata, whatsapp } from '@/modules/site/site'

import { PlanMap } from '../components/plan-map'
import type { PlanData } from '../components/plan-map-view'
import { masterplanUrl, plotReference, statusCounts } from '../model'
import * as masterplans from '../service'

export interface ShowProps {
  slug: string
}

async function load(slug: string) {
  return (await masterplans.findPublished(slug)) ?? notFound()
}

export async function showMetadata({ slug }: ShowProps): Promise<Metadata> {
  const m = await load(slug)
  return pageMetadata({
    title: `${m.name} — Project Map`,
    description: `Tap a plot on the ${m.name} masterplan to see its size, price and availability.`,
    image: m.image_path,
    path: masterplanUrl(m),
  })
}

/** /map/[slug]: the interactive masterplan (FR-MAP-002..008). */
export async function MasterplanShowPage({ slug }: ShowProps) {
  const m = await load(slug)
  const [settings, base] = await Promise.all([getSettings(), baseUrl()])

  // What the map consumes: each plot's details plus a ready-made WhatsApp link (FR-MAP-004, FR-MAP-008).
  const plan: PlanData = {
    image: assetUrl(m.image_path),
    width: m.image_width,
    height: m.image_height,
    plots: m.plots.map((plot) => ({
      id: plot.id,
      number: plot.number,
      size: plot.size,
      price: plot.price,
      position: plot.position,
      status: plot.status,
      polygon: plot.polygon,
      ref: plotReference(plot),
      whatsapp: whatsapp(settings, `Hello, I'm interested in Plot ${plot.number} on ${m.name} (Ref: ${plotReference(plot)}). ${base}${masterplanUrl(m)}`),
    })),
  }

  return (
    <>
      <section className="section-glow pt-8 pb-4 sm:pt-12 sm:pb-6">
        <div className="container-x">
          <div className="mx-auto max-w-4xl">
            <Link href="/map" className="group mb-4 inline-flex items-center gap-2 text-muted-fg hover:text-fg"><Icon name="chevron-left" className="size-5" /> Back to Projects</Link>
            <div className="text-center">
              <span className="eyebrow">Interactive Experience</span>
              <h1 className="mt-2 mb-3 font-display text-2xl font-bold text-fg sm:text-4xl md:text-5xl">{m.name} <span className="accent">Project Map</span></h1>
              {m.description && <div className="flex items-center justify-center gap-2 text-sm text-muted-fg"><Icon name="pin" className="size-4 text-brand" />{m.description}</div>}
            </div>
          </div>
        </div>
      </section>

      {m.image_path ? (
        <PlanMap plan={plan} name={m.name} counts={statusCounts(m)} whatsappEnabled={Boolean(settings.whatsapp_number)} phone={settings.phone ?? ''} />
      ) : (
        <section className="py-8"><div className="container-x"><p className="card p-16 text-center text-muted-fg">The layout image for this project has not been uploaded yet.</p></div></section>
      )}
    </>
  )
}
