import { migrate } from 'drizzle-orm/node-postgres/migrator'

import { db, pool } from './index'

// `pnpm db:migrate`: applies the SQL in db/migrations that has not run yet.
await migrate(db, { migrationsFolder: 'db/migrations' })
console.log('Migrations applied.')
await pool.end()
