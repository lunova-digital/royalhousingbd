export type ListingType = 'property' | 'plot' | 'flat'

export const LISTING_TYPES: { value: ListingType; label: string }[] = [
  { value: 'property', label: 'Property' },
  { value: 'plot', label: 'Plot' },
  { value: 'flat', label: 'Flat' },
]

export function typeLabel(type: string): string {
  return LISTING_TYPES.find((t) => t.value === type)?.label ?? type
}

export interface ListingImage {
  id: number
  listing_id: number
  path: string
  thumb_path: string
  sort_order: number
}

export interface Listing {
  id: number
  type: ListingType
  title: string
  slug: string
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
  featured: boolean
  featured_order: number
  published: boolean
  masterplan_id: number | null
  images: ListingImage[]
  created_at: string
  updated_at: string
}

export type ListingInput = Omit<Listing, 'id' | 'slug' | 'featured' | 'featured_order' | 'images' | 'created_at' | 'updated_at'>

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

export function listingUrl(l: Pick<Listing, 'type' | 'slug'>): string {
  return `/${l.type}/${l.slug}`
}
