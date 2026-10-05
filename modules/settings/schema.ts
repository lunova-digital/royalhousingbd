import { integer, pgTable, text, timestamp, varchar } from 'drizzle-orm/pg-core'

/** Key/value store for everything the admin edits under Settings. */
export const settings = pgTable('settings', {
  id: integer().primaryKey().generatedAlwaysAsIdentity(),
  key: varchar({ length: 100 }).notNull().unique(),
  value: text().notNull(),
  created_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
  updated_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
})
