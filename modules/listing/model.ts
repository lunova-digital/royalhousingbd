// What the Eloquent model computed from a row: labels, urls, cover, availability.
import { ucfirst } from '@/lib/text'

import type { Listing, ListingImage, ListingWithPlan } from './types'

export function typeLabel(type: string): string {
  switch (type) {
    case 'property':
      return 'Property'
    case 'plot':
      return 'Plot'
    case 'flat':
      return 'Flat'
    default:
      return ucfirst(type)
  }
}

export function listingUrl(l: { type: string; slug: string }): string {
  return `/${l.type}/${l.slug}`
}

/** What the WhatsApp message quotes, e.g. "PLOT-12". */
export function reference(l: { type: string; id: number }): string {
  return `${l.type.toUpperCase()}-${l.id}`
}

export function cover(l: Pick<Listing, 'images'>): ListingImage | undefined {
  return l.images[0]
}

/** Plots available / total from the linked masterplan, or null. */
export function availability(l: Pick<ListingWithPlan, 'masterplan'>): { available: number; total: number; percent: number } | null {
  const plots = l.masterplan?.plots
  if (!plots || plots.length === 0) return null
  const available = plots.filter((p) => p.status === 'available').length
  return { available, total: plots.length, percent: Math.round((available * 100) / plots.length) }
}

/** The headline size for a card: land area for property, size otherwise. */
export function sizeLabel(l: Pick<Listing, 'type' | 'land_area' | 'built_area' | 'size'>): string {
  return l.type === 'property' ? l.land_area || l.built_area : l.size
}

export function priceLabel(l: Pick<Listing, 'call_for_price' | 'price'>): string {
  return l.call_for_price || l.price.trim() === '' ? 'Call for Price' : l.price
}
