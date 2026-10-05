import { vi } from 'vitest'

// Test uploads go to a scratch folder (STORAGE_ROOT is read when the media module loads, after this file).
process.env.STORAGE_PATH = 'storage/test'

// Every service imports `db` from '@/db'; the suite swaps in an in-memory Postgres (PGlite) with the real migrations.
vi.mock('@/db', async () => {
  const { createTestDb } = await import('@/db/test')
  const db = await createTestDb()
  return { db }
})
