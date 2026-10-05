import { eq } from 'drizzle-orm'
import { z } from 'zod'

import { db } from '@/db'
import { handle, json, readJson, validate, ValidationError } from '@/lib/http'

import { requireAdmin } from '../guard'
import { hashPassword, verifyPassword } from '../password'
import { users } from '../schema'

const schema = z.object({
  current_password: z.string().min(1, 'The current password field is required.'),
  password: z.string().min(8, 'The password must be at least 8 characters.'),
})

/** PUT /api/auth/password: change the signed-in admin's password. */
export const PUT = handle(async (request) => {
  const viewer = await requireAdmin(request)
  const data = validate(schema, await readJson(request))
  const user = await db.query.users.findFirst({ where: eq(users.id, viewer.id) })
  if (!user || !(await verifyPassword(data.current_password, user.password))) {
    throw ValidationError.of('current_password', 'The current password is wrong.')
  }
  await db.update(users).set({ password: await hashPassword(data.password), updated_at: new Date().toISOString() }).where(eq(users.id, user.id))
  return json({ message: 'Password updated.' })
})
