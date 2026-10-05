'use server'

import { eq } from 'drizzle-orm'
import { cookies, headers } from 'next/headers'
import { redirect } from 'next/navigation'
import { z } from 'zod'

import { db } from '@/db'

import { SESSION_COOKIE } from './constants'
import { verifyPassword } from './password'
import { tooManyAttempts } from './rate-limit'
import { users } from './schema'
import { createSession, destroySession, sessionCookieOptions } from './session'

export interface LoginState {
  error?: string
  email?: string
}

const credentials = z.object({
  email: z.email('Enter your email address.'),
  password: z.string().min(1, 'Enter your password.'),
})

/** The login form's server action (FR-ADM-001): session cookie on success, a message otherwise. */
export async function login(_state: LoginState, formData: FormData): Promise<LoginState> {
  const parsed = credentials.safeParse({ email: formData.get('email'), password: formData.get('password') })
  const email = String(formData.get('email') ?? '')
  if (!parsed.success) return { error: parsed.error.issues[0]?.message, email }

  const ip = (await headers()).get('x-forwarded-for')?.split(',')[0]?.trim() || 'local'
  if (tooManyAttempts(ip)) return { error: 'Too many attempts. Try again in a minute.', email }

  const user = await db.query.users.findFirst({ where: eq(users.email, parsed.data.email.toLowerCase()) })
  if (!user || !(await verifyPassword(parsed.data.password, user.password))) {
    return { error: 'These credentials do not match our records.', email }
  }

  const { token, expires } = await createSession(user.id)
  const store = await cookies()
  store.set(SESSION_COOKIE, token, { ...sessionCookieOptions, expires })
  redirect('/admin')
}

export async function logout(): Promise<void> {
  const store = await cookies()
  await destroySession(store.get(SESSION_COOKIE)?.value)
  store.delete(SESSION_COOKIE)
  redirect('/login')
}
