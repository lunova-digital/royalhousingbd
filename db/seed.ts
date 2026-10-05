import { db, pool } from './index'
import { seedDemo } from './demo-seed'
import { seedAdmin } from '@/modules/auth/seed'

// `pnpm db:seed`: the admin account every install needs and, outside
// production, demo content so every page has something to show.
await seedAdmin(db)
if (process.env.NODE_ENV !== 'production') {
  await seedDemo(db)
}
console.log('Seeded.')
await pool.end()
