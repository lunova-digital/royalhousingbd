import { eq } from 'drizzle-orm'

import type { Db } from '@/db'

import { hashPassword } from './password'
import { users } from './schema'

/** The single admin account; the login comes from ADMIN_EMAIL / ADMIN_PASSWORD. Change them before go-live. */
export async function seedAdmin(db: Db): Promise<void> {
  const email = (process.env.ADMIN_EMAIL ?? 'admin@royalhousingbd.com').toLowerCase()
  const password = process.env.ADMIN_PASSWORD ?? 'password'
  const existing = await db.query.users.findFirst({ where: eq(users.email, email) })
  if (existing) return
  await db.insert(users).values({ name: 'Admin', email, password: await hashPassword(password) })
}
