import { relations } from 'drizzle-orm'
import { boolean, index, integer, jsonb, pgTable, text, timestamp, unique, varchar } from 'drizzle-orm/pg-core'

import { masterplans } from '@/modules/masterplan/schema'

export const LISTING_TYPES = ['property', 'plot', 'flat'] as const

/** Feature 1: a Property / Plot / Flat listing (FR-LIST-*). */
export const listings = pgTable(
  'listings',
  {
    id: integer().primaryKey().generatedAlwaysAsIdentity(),
    type: varchar({ length: 20, enum: LISTING_TYPES }).notNull(),
    title: varchar({ length: 200 }).notNull(),
    slug: varchar({ length: 220 }).notNull(),
    location: varchar({ length: 255 }).notNull(),
    price: varchar({ length: 255 }).notNull().default(''),
    call_for_price: boolean().notNull().default(false),
    // Property
    land_area: varchar({ length: 255 }).notNull().default(''),
    built_area: varchar({ length: 255 }).notNull().default(''),
    // Plot (katha/sqft) and Flat (sqft)
    size: varchar({ length: 255 }).notNull().default(''),
    // Flat
    bedrooms: integer().notNull().default(0),
    baths: integer().notNull().default(0),
    floor: varchar({ length: 255 }).notNull().default(''),
    video_url: varchar({ length: 255 }).notNull().default(''),
    facilities: jsonb().$type<string[]>().notNull().default([]),
    description: text().notNull().default(''),
    featured: boolean().notNull().default(false),
    featured_order: integer().notNull().default(0),
    published: boolean().notNull().default(false),
    /** A plot-development listing can point at its masterplan so cards show live availability. */
    masterplan_id: integer().references(() => masterplans.id, { onDelete: 'set null' }),
    created_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
    updated_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
  },
  (t) => [unique().on(t.type, t.slug), index('listings_type_idx').on(t.type), index('listings_location_idx').on(t.location), index('listings_featured_idx').on(t.featured)],
)

export const listingImages = pgTable('listing_images', {
  id: integer().primaryKey().generatedAlwaysAsIdentity(),
  listing_id: integer()
    .notNull()
    .references(() => listings.id, { onDelete: 'cascade' }),
  path: varchar({ length: 255 }).notNull(),
  thumb_path: varchar({ length: 255 }).notNull().default(''),
  sort_order: integer().notNull().default(0),
  created_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
  updated_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
})

export const listingsRelations = relations(listings, ({ many, one }) => ({
  images: many(listingImages),
  masterplan: one(masterplans, { fields: [listings.masterplan_id], references: [masterplans.id] }),
}))

export const listingImagesRelations = relations(listingImages, ({ one }) => ({
  listing: one(listings, { fields: [listingImages.listing_id], references: [listings.id] }),
}))
