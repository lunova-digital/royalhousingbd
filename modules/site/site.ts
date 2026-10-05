import type { Metadata } from 'next'
import { headers } from 'next/headers'
import { cache } from 'react'

import { allSettings, settingList } from '@/modules/settings/settings'

import { assetUrl } from './shared'

export { NAV, navKeyFor, assetUrl, whatsapp, type IconName, type NavItem } from './shared'

/**
 * What every public page shares: the settings-driven chrome (brand, nav,
 * contact, floating WhatsApp) and SEO meta (NFR-004).
 */

/** Site settings, read once per request. */
export const getSettings = cache(allSettings)

/** Scheme + host for canonical URLs and share messages. */
export const baseUrl = cache(async (): Promise<string> => {
  const configured = (process.env.APP_URL ?? '').replace(/\/+$/, '')
  if (configured !== '' && !configured.startsWith('http://localhost')) return configured
  const h = await headers()
  const host = h.get('x-forwarded-host') ?? h.get('host')
  if (!host) return configured || 'http://localhost:3000'
  const proto = h.get('x-forwarded-proto') ?? (host.startsWith('localhost') ? 'http' : 'https')
  return `${proto}://${host}`
})

/** A JSON list setting, as rows. */
export async function siteList<T extends object>(key: string): Promise<T[]> {
  return settingList<T>(await getSettings(), key)
}

export interface PageMeta {
  title?: string
  description?: string
  /** A stored image path or absolute URL for og:image; falls back to the og_image setting. */
  image?: string | null
  /** The page's path, for the canonical URL. */
  path: string
}

/** The <head> of a public page: title, description, canonical and Open Graph from the settings. */
export async function pageMetadata(meta: PageMeta): Promise<Metadata> {
  const [s, base] = await Promise.all([getSettings(), baseUrl()])
  const siteName = s.site_name ?? 'Royal Housing BD'
  const title = meta.title ? `${meta.title} | ${siteName}` : s.meta_title || siteName
  const description = meta.description || s.meta_description || undefined
  let image = meta.image || s.og_image || ''
  if (image !== '' && !image.startsWith('http')) image = base + assetUrl(image)
  const canonical = base + meta.path

  return {
    title,
    description,
    alternates: { canonical },
    openGraph: {
      type: 'website',
      siteName,
      title,
      description,
      url: canonical,
      images: image ? [image] : undefined,
    },
    twitter: { card: 'summary_large_image' },
    icons: s.logo ? { icon: assetUrl(s.logo) } : undefined,
  }
}
