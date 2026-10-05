import { defineConfig } from 'drizzle-kit'

// Every module owns its tables in Modules/<Name>/schema.ts; the generated SQL
// migrations are collected here, in one history.
export default defineConfig({
  dialect: 'postgresql',
  schema: './modules/*/schema.ts',
  out: './db/migrations',
  dbCredentials: { url: process.env.DATABASE_URL ?? 'postgres://postgres:postgres@localhost:5432/realestate1' },
})
