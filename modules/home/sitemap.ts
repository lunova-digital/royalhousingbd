import type { MetadataRoute } from 'next'

import { listingUrl } from '@/modules/listing/model'
import * as listings from '@/modules/listing/service'
import { masterplanUrl } from '@/modules/masterplan/model'
import * as masterplans from '@/modules/masterplan/service'
import { baseUrl, NAV } from '@/modules/site/site'

/** /sitemap.xml: the fixed pages plus every published listing and map. */
export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [base, listingRows, mapRows] = await Promise.all([baseUrl(), listings.publishedSlugs(), masterplans.publishedSlugs()])
  const now = new Date()
  return [
    ...NAV.map((item) => ({ url: base + item.href, lastModified: now })),
    ...listingRows.map((l) => ({ url: base + listingUrl(l), lastModified: new Date(l.updated_at) })),
    ...mapRows.map((m) => ({ url: base + masterplanUrl(m), lastModified: new Date(m.updated_at) })),
  ]
}
