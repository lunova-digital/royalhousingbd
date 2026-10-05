import type { MetadataRoute } from 'next'

import { baseUrl } from '@/modules/site/site'

/** /robots.txt */
export default async function robots(): Promise<MetadataRoute.Robots> {
  const base = await baseUrl()
  return {
    rules: { userAgent: '*', disallow: ['/admin', '/api', '/login'] },
    sitemap: `${base}/sitemap.xml`,
  }
}
