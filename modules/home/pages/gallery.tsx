import type { Metadata } from 'next'

import { listingUrl } from '@/modules/listing/model'
import * as listings from '@/modules/listing/service'
import { masterplanUrl } from '@/modules/masterplan/model'
import * as masterplans from '@/modules/masterplan/service'
import { PageHero } from '@/modules/site/components/partials'
import { assetUrl, pageMetadata } from '@/modules/site/site'

import { GalleryGrid, type Photo } from '../components/gallery-grid'

export async function generateMetadata(): Promise<Metadata> {
  return pageMetadata({ title: 'Gallery', description: 'Photos of our properties, plots, flats and project masterplans.', path: '/gallery' })
}

/** /gallery: every photo on the site, grouped by listing type and masterplans. */
export default async function GalleryPage() {
  const [withImages, maps] = await Promise.all([listings.publishedWithImages(), masterplans.published('desc')])
  const photos: Photo[] = []
  for (const listing of withImages) {
    for (const image of listing.images) {
      photos.push({ src: assetUrl(image.path), thumb: assetUrl(image.thumb_path || image.path), category: listing.type, caption: listing.title, location: listing.location, href: listingUrl(listing) })
    }
  }
  for (const map of maps) {
    if (map.image_path) photos.push({ src: assetUrl(map.image_path), thumb: assetUrl(map.image_path), category: 'masterplan', caption: map.name, location: 'Masterplan', href: masterplanUrl(map) })
  }

  return (
    <>
      <PageHero eyebrow="Our Gallery" title="See Our Work" accent="In Action" text="Browse photos of our properties, plots, flats and project masterplans." />
      <GalleryGrid photos={photos} />
    </>
  )
}
