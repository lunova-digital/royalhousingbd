import type { InferSelectModel } from 'drizzle-orm'

import type { MasterplanWithPlots } from '@/modules/masterplan/types'

import { LISTING_TYPES, type listingImages, type listings } from './schema'

export { LISTING_TYPES }
export type ListingType = (typeof LISTING_TYPES)[number]

export const LISTING_TYPE_OPTIONS: { value: ListingType; label: string }[] = [
  { value: 'property', label: 'Property' },
  { value: 'plot', label: 'Plot' },
  { value: 'flat', label: 'Flat' },
]

export function isListingType(value: unknown): value is ListingType {
  return typeof value === 'string' && (LISTING_TYPES as readonly string[]).includes(value)
}

export type ListingRow = InferSelectModel<typeof listings>
export type ListingImage = InferSelectModel<typeof listingImages>

/** A listing with its gallery, as the API returns it. */
export type Listing = ListingRow & { images: ListingImage[] }

/** With the linked masterplan too, for availability on cards (public pages). */
export type ListingWithPlan = Listing & { masterplan: MasterplanWithPlots | null }

/** The admin form payload; the same shape creates and updates. */
export interface ListingInput {
  type: ListingType
  title: string
  location: string
  price: string
  call_for_price: boolean
  land_area: string
  built_area: string
  size: string
  bedrooms: number
  baths: number
  floor: string
  video_url: string
  facilities: string[]
  description: string
  published: boolean
  masterplan_id: number | null
}

export const EMPTY_INPUT: ListingInput = {
  type: 'property',
  title: '',
  location: '',
  price: '',
  call_for_price: false,
  land_area: '',
  built_area: '',
  size: '',
  bedrooms: 0,
  baths: 0,
  floor: '',
  video_url: '',
  facilities: [],
  description: '',
  published: true,
  masterplan_id: null,
}
