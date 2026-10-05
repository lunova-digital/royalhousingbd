import { boolean, integer, pgTable, timestamp, varchar } from 'drizzle-orm/pg-core'

/** One line of the homepage announcement ticker (FR-HOME-003). */
export const tickerItems = pgTable('ticker_items', {
  id: integer().primaryKey().generatedAlwaysAsIdentity(),
  text: varchar({ length: 300 }).notNull(),
  link: varchar({ length: 255 }).notNull().default(''),
  active: boolean().notNull().default(true),
  sort_order: integer().notNull().default(0),
  created_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
  updated_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
})

/** One embed of the homepage YouTube showcase (FR-HOME-004). */
export const videos = pgTable('videos', {
  id: integer().primaryKey().generatedAlwaysAsIdentity(),
  title: varchar({ length: 200 }).notNull().default(''),
  url: varchar({ length: 255 }).notNull(),
  sort_order: integer().notNull().default(0),
  created_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
  updated_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
})
