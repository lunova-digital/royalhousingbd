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

export interface Location {
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
  location_id: number | null
  price: string
  call_for_price: boolean
  price_amount: number | null
  land_area: string
  built_area: string
  size: string
  bedrooms: number
  baths: number
  floor: string
  video_url: string
  brochure_path: string
  brochure_size: number
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

// What the form sends: the listing's own columns (the location as its id) plus the ids of its features.
export type ListingInput = Omit<Listing, 'id' | 'slug' | 'type_label' | 'location' | 'featured' | 'featured_order' | 'brochure_path' | 'brochure_size' | 'images' | 'features' | 'created_at' | 'updated_at'> & { features: number[] }

export const EMPTY_INPUT: ListingInput = {
  type: '',
  title: '',
  location_id: null,
  price: '',
  call_for_price: false,
  price_amount: null,
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
  const { id: _id, slug: _slug, type_label: _tl, location: _l, featured: _f, featured_order: _fo, brochure_path: _bp, brochure_size: _bs, images: _i, features, created_at: _c, updated_at: _u, ...columns } = listing
  return { ...columns, features: (features ?? []).map((f) => f.id) }
}

// "৳ 4.5 Crore" / "৳ 35 Lakh" / "৳ 85,000" for a Taka amount.
export function formatTaka(amount: number): string {
  const unit = (n: number, word: string) => `৳ ${Number((amount / n).toFixed(2))} ${word}`
  if (amount >= 1e7) return unit(1e7, 'Crore')
  if (amount >= 1e5) return unit(1e5, 'Lakh')
  return `৳ ${amount.toLocaleString('en-IN')}`
}

export function listingUrl(l: Pick<Listing, 'type' | 'slug'>): string {
  return `/${l.type}/${l.slug}`
}
