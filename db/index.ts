import { drizzle, type NodePgDatabase } from 'drizzle-orm/node-postgres'
import { Pool } from 'pg'

import * as schema from './schema'

export type Db = NodePgDatabase<typeof schema>

const globalForDb = globalThis as unknown as { __pool?: Pool }

function connectionString(): string {
  const url = process.env.DATABASE_URL
  if (!url) throw new Error('DATABASE_URL is not set (see .env.example).')
  return url
}

// One pool per process; kept on globalThis so Next's dev-time hot reloads reuse it.
const pool = globalForDb.__pool ?? new Pool({ connectionString: connectionString() })
if (process.env.NODE_ENV !== 'production') globalForDb.__pool = pool

export const db: Db = drizzle({ client: pool, schema })
export { pool, schema }
