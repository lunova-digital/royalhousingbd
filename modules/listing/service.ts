import { and, asc, count, desc, eq, ne, or, sql, type SQL } from 'drizzle-orm'

import { db } from '@/db'
import { ValidationError } from '@/lib/http'
import { slugify } from '@/lib/text'
import { deleteStored, MAX_WIDTH, storeImage } from '@/modules/media/image-store'
import { masterplans } from '@/modules/masterplan/schema'

import { listingImages, listings } from './schema'
import type { Listing, ListingImage, ListingInput, ListingType, ListingWithPlan } from './types'

export interface ListingFilter {
  type?: string | null
  location?: string | null
  q?: string | null
  published?: boolean
}

export interface Page<T> {
  items: T[]
  total: number
  page: number
  perPage: number
  lastPage: number
}

const imagesOrdered = { orderBy: [asc(listingImages.sort_order), asc(listingImages.id)] }
const withRelations = { images: imagesOrdered, masterplan: { with: { plots: true as const } } }

function where(filter: ListingFilter): SQL | undefined {
  const parts: SQL[] = []
  if (filter.type) parts.push(eq(listings.type, filter.type as ListingType))
  if (filter.published) parts.push(eq(listings.published, true))
  const location = (filter.location ?? '').trim()
  if (location !== '') parts.push(eq(listings.location, location))
  const term = (filter.q ?? '').trim().toLowerCase()
  if (term !== '') {
    const like = `%${term}%`
    parts.push(or(sql`lower(${listings.title}) like ${like}`, sql`lower(${listings.location}) like ${like}`)!)
  }
  return parts.length ? and(...parts) : undefined
}

/** Drives both the public grid (FR-LIST-002) and the admin table. */
export async function search(filter: ListingFilter, perPage = 12, page = 1): Promise<Page<ListingWithPlan>> {
  const condition = where(filter)
  const safePage = Math.max(1, page)
  const [items, [totalRow]] = await Promise.all([
    db.query.listings.findMany({
      where: condition,
      with: withRelations,
      orderBy: [desc(listings.id)],
      limit: perPage,
      offset: (safePage - 1) * perPage,
    }),
    db.select({ total: count() }).from(listings).where(condition),
  ])
  const total = totalRow?.total ?? 0
  return { items, total, page: safePage, perPage, lastPage: Math.max(1, Math.ceil(total / perPage)) }
}

/** Distinct locations of published listings, for the filter dropdown. */
export async function locations(type?: string | null): Promise<string[]> {
  const rows = await db
    .selectDistinct({ location: listings.location })
    .from(listings)
    .where(and(eq(listings.published, true), type ? eq(listings.type, type as ListingType) : undefined))
    .orderBy(asc(listings.location))
  return rows.map((r) => r.location)
}

export async function findPublished(type: string, slug: string): Promise<ListingWithPlan | undefined> {
  return db.query.listings.findFirst({
    where: and(eq(listings.published, true), eq(listings.type, type as ListingType), eq(listings.slug, slug)),
    with: withRelations,
  })
}

export async function find(id: number): Promise<Listing | undefined> {
  return db.query.listings.findFirst({ where: eq(listings.id, id), with: { images: imagesOrdered } })
}

async function assertMasterplan(id: number | null): Promise<void> {
  if (id === null) return
  const row = await db.query.masterplans.findFirst({ where: eq(masterplans.id, id), columns: { id: true } })
  if (!row) throw ValidationError.of('masterplan_id', 'The selected project map does not exist.')
}

export async function create(input: ListingInput): Promise<Listing> {
  await assertMasterplan(input.masterplan_id)
  const [row] = await db
    .insert(listings)
    .values({ ...input, slug: await uniqueSlug(input.title) })
    .returning()
  return (await find(row!.id))!
}

export async function update(listing: Listing, input: ListingInput): Promise<Listing> {
  await assertMasterplan(input.masterplan_id)
  const slug = listing.title !== input.title ? await uniqueSlug(input.title, listing.id) : listing.slug
  await db
    .update(listings)
    .set({ ...input, slug, updated_at: new Date().toISOString() })
    .where(eq(listings.id, listing.id))
  return (await find(listing.id))!
}

export async function remove(listing: Listing): Promise<void> {
  for (const image of listing.images) await deleteStored(image.path, image.thumb_path)
  await db.delete(listings).where(eq(listings.id, listing.id))
}

/** Append uploaded files to the gallery, after the existing ones. */
export async function addImages(listing: Listing, files: File[]): Promise<ListingImage[]> {
  let next = listing.images.length
  const added: ListingImage[] = []
  for (const file of files) {
    const stored = await storeImage(file, 'files', 'listings', { maxWidth: MAX_WIDTH, thumb: true })
    const [row] = await db
      .insert(listingImages)
      .values({ listing_id: listing.id, path: stored.path, thumb_path: stored.thumb_path, sort_order: next++ })
      .returning()
    added.push(row!)
  }
  return added
}

export async function findImage(listing: Listing, imageId: number): Promise<ListingImage | undefined> {
  return db.query.listingImages.findFirst({ where: and(eq(listingImages.id, imageId), eq(listingImages.listing_id, listing.id)) })
}

export async function deleteImage(image: ListingImage): Promise<void> {
  await deleteStored(image.path, image.thumb_path)
  await db.delete(listingImages).where(eq(listingImages.id, image.id))
}

/** Set sort_order to the position of each id; the first becomes the cover. */
export async function reorderImages(listing: Listing, ids: number[]): Promise<void> {
  await db.transaction(async (tx) => {
    for (const [i, id] of ids.entries()) {
      await tx.update(listingImages).set({ sort_order: i }).where(and(eq(listingImages.id, id), eq(listingImages.listing_id, listing.id)))
    }
  })
}

/** The admin-selected homepage row (FR-HOME-002), in order. */
export async function featured(publishedOnly = true): Promise<ListingWithPlan[]> {
  return db.query.listings.findMany({
    where: and(eq(listings.featured, true), publishedOnly ? eq(listings.published, true) : undefined),
    with: withRelations,
    orderBy: [asc(listings.featured_order), asc(listings.id)],
  })
}

/** Replace the featured row with `ids`, in that order. */
export async function setFeatured(ids: number[]): Promise<void> {
  await db.transaction(async (tx) => {
    await tx.update(listings).set({ featured: false, featured_order: 0 }).where(eq(listings.featured, true))
    for (const [i, id] of ids.entries()) {
      await tx.update(listings).set({ featured: true, featured_order: i }).where(eq(listings.id, id))
    }
  })
}

/** Published listings of the same type, newest first, for "More … listings". */
export async function related(listing: Listing, limit = 3): Promise<ListingWithPlan[]> {
  return db.query.listings.findMany({
    where: and(eq(listings.published, true), eq(listings.type, listing.type), ne(listings.id, listing.id)),
    with: withRelations,
    orderBy: [desc(listings.id)],
    limit,
  })
}

/** Every published listing with its photos, for the gallery page. */
export async function publishedWithImages(): Promise<Listing[]> {
  return db.query.listings.findMany({ where: eq(listings.published, true), with: { images: imagesOrdered }, orderBy: [desc(listings.id)] })
}

export async function publishedSlugs(): Promise<{ type: string; slug: string; updated_at: string }[]> {
  return db.select({ type: listings.type, slug: listings.slug, updated_at: listings.updated_at }).from(listings).where(eq(listings.published, true))
}

/** Listings per type, for the dashboard. */
export async function countByType(): Promise<Record<string, number>> {
  const rows = await db.select({ type: listings.type, n: count() }).from(listings).groupBy(listings.type)
  return Object.fromEntries(rows.map((r) => [r.type, r.n]))
}

async function uniqueSlug(title: string, selfId?: number): Promise<string> {
  const base = slugify(title) || 'listing'
  let slug = base
  for (let i = 2; ; i++) {
    const [taken] = await db
      .select({ id: listings.id })
      .from(listings)
      .where(and(eq(listings.slug, slug), selfId ? ne(listings.id, selfId) : undefined))
      .limit(1)
    if (!taken) return slug
    slug = `${base}-${i}`
  }
}

