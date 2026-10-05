import { createHash, randomBytes } from 'node:crypto'
import { and, eq, gt } from 'drizzle-orm'
import { cookies } from 'next/headers'
import { cache } from 'react'

import { db } from '@/db'

import { SESSION_COOKIE, SESSION_DAYS } from './constants'
import { sessions, users } from './schema'

export interface Viewer {
  id: number
  name: string
  email: string
}

const hashToken = (token: string) => createHash('sha256').update(token).digest('hex')

/** Starts a session for the user and returns the raw cookie value. */
export async function createSession(userId: number): Promise<{ token: string; expires: Date }> {
  const token = randomBytes(32).toString('base64url')
  const expires = new Date(Date.now() + SESSION_DAYS * 24 * 60 * 60 * 1000)
  await db.insert(sessions).values({ id: hashToken(token), user_id: userId, expires_at: expires.toISOString() })
  return { token, expires }
}

export async function destroySession(token: string | undefined): Promise<void> {
  if (token) await db.delete(sessions).where(eq(sessions.id, hashToken(token)))
}

/** The signed-in admin for a raw session token, or null when it is missing, unknown or expired. */
export async function viewerForToken(token: string | undefined): Promise<Viewer | null> {
  if (!token) return null
  const [row] = await db
    .select({ id: users.id, name: users.name, email: users.email })
    .from(sessions)
    .innerJoin(users, eq(sessions.user_id, users.id))
    .where(and(eq(sessions.id, hashToken(token)), gt(sessions.expires_at, new Date().toISOString())))
    .limit(1)
  return row ?? null
}

/** The viewer of the current server render, read once per request. */
export const getViewer = cache(async (): Promise<Viewer | null> => {
  const store = await cookies()
  return viewerForToken(store.get(SESSION_COOKIE)?.value)
})

export function tokenFromRequest(request: Request): string | undefined {
  const match = request.headers.get('cookie')?.match(new RegExp(`(?:^|;\\s*)${SESSION_COOKIE}=([^;]+)`))
  return match ? decodeURIComponent(match[1]!) : undefined
}

export const sessionCookieOptions = {
  httpOnly: true,
  sameSite: 'lax' as const,
  secure: process.env.NODE_ENV === 'production',
  path: '/',
}
