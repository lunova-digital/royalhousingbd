import { PGlite } from '@electric-sql/pglite'
import { drizzle } from 'drizzle-orm/pglite'
import { migrate } from 'drizzle-orm/pglite/migrator'

import * as schema from './schema'

/** An in-memory Postgres with the real migrations applied, for the vitest suite. */
export async function createTestDb() {
  const client = new PGlite()
  const db = drizzle({ client, schema })
  await migrate(db, { migrationsFolder: 'db/migrations' })
  return db
}
