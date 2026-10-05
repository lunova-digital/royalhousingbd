import { relations } from 'drizzle-orm'
import { integer, pgTable, text, timestamp, varchar } from 'drizzle-orm/pg-core'

/** The admin account(s): FR-ADM-001 has exactly one, seeded from ADMIN_EMAIL / ADMIN_PASSWORD. */
export const users = pgTable('users', {
  id: integer().primaryKey().generatedAlwaysAsIdentity(),
  name: varchar({ length: 255 }).notNull(),
  email: varchar({ length: 255 }).notNull().unique(),
  password: text().notNull(),
  created_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
  updated_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
})

/** Server-side sessions behind the admin cookie; the id column holds the hash of the token. */
export const sessions = pgTable('sessions', {
  id: varchar({ length: 64 }).primaryKey(),
  user_id: integer()
    .notNull()
    .references(() => users.id, { onDelete: 'cascade' }),
  expires_at: timestamp({ withTimezone: true, mode: 'string' }).notNull(),
  created_at: timestamp({ withTimezone: true, mode: 'string' }).defaultNow().notNull(),
})

export const usersRelations = relations(users, ({ many }) => ({ sessions: many(sessions) }))
export const sessionsRelations = relations(sessions, ({ one }) => ({
  user: one(users, { fields: [sessions.user_id], references: [users.id] }),
}))
