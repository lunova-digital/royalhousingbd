import { relations } from 'drizzle-orm'
import { boolean, index, integer, jsonb, pgTable, text, timestamp, varchar } from 'drizzle-orm/pg-core'

export const PLOT_STATUSES = ['available', 'booked', 'sold'] as const

/** A point of a plot polygon, in image-pixel coordinates (x right, y down). */
export interface Point {
  x: number
  y: number
}

/**
 * Feature 2: an interactive project layout (FR-MAP-*). A masterplan is an
 * image; its plots are polygons drawn over it in image-pixel coordinates.
 */
export const masterplans = pgTable('masterplans', {
  id: integer().primaryKey().generatedAlwaysAsIdentity(),
  name: varchar({ length: 150 }).notNull(),
  slug: varchar({ length: 170 }).notNull().unique(),
  description: text().notNull().default(''),
  image_path: varchar({ length: 255 }).notNull().default(''),
  image_width: integer().notNull().default(0),
  image_height: integer().notNull().default(0),
  published: boolean().notNull().default(false),
  created_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
  updated_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
})

export const plots = pgTable(
  'plots',
  {
    id: integer().primaryKey().generatedAlwaysAsIdentity(),
    masterplan_id: integer()
      .notNull()
      .references(() => masterplans.id, { onDelete: 'cascade' }),
    number: varchar({ length: 50 }).notNull(),
    size: varchar({ length: 255 }).notNull().default(''),
    price: varchar({ length: 255 }).notNull().default(''),
    position: varchar({ length: 255 }).notNull().default(''),
    status: varchar({ length: 20, enum: PLOT_STATUSES }).notNull().default('available'),
    polygon: jsonb().$type<Point[]>().notNull(),
    created_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
    updated_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
  },
  (t) => [index('plots_status_idx').on(t.status)],
)

export const masterplansRelations = relations(masterplans, ({ many }) => ({ plots: many(plots) }))
export const plotsRelations = relations(plots, ({ one }) => ({
  masterplan: one(masterplans, { fields: [plots.masterplan_id], references: [masterplans.id] }),
}))
