export interface ListingType {
  id: number
  slug: string
  name: string
  sort_order: number
  listings_count?: number
}

export interface Feature {
  id: number
  name: string
  slug: string
  listings_count?: number
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
  type: string
  type_label: string
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
  description: string
  featured: boolean
  featured_order: number
  published: boolean
  masterplan_id: number | null
  images: ListingImage[]
  features: Feature[]
  created_at: string
  updated_at: string
}

// What the form sends: the listing's own columns plus the ids of its features.
export type ListingInput = Omit<Listing, 'id' | 'slug' | 'type_label' | 'featured' | 'featured_order' | 'images' | 'features' | 'created_at' | 'updated_at'> & { features: number[] }

export const EMPTY_INPUT: ListingInput = {
  type: '',
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
  description: '',
  published: true,
  masterplan_id: null,
  features: [],
}

export function toInput(listing: Listing): ListingInput {
  const { id: _id, slug: _slug, type_label: _tl, featured: _f, featured_order: _fo, images: _i, features, created_at: _c, updated_at: _u, ...columns } = listing
  return { ...columns, features: (features ?? []).map((f) => f.id) }
}

export function listingUrl(l: Pick<Listing, 'type' | 'slug'>): string {
  return `/${l.type}/${l.slug}`
}
