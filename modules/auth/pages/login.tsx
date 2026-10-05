import type { Metadata } from 'next'
import { redirect } from 'next/navigation'

import { getViewer } from '../session'
import { LoginForm } from './login-form'

export const metadata: Metadata = {
  title: 'Sign in',
  robots: { index: false },
}

/** The door to /admin, styled with the admin's tokens rather than the public theme. */
export default async function LoginPage() {
  if (await getViewer()) redirect('/admin')

  return (
    <main className="flex min-h-svh items-center justify-center p-4">
      <div className="w-full max-w-sm rounded-xl border bg-card p-6 text-card-foreground shadow-sm">
        <h1 className="font-semibold leading-none">Admin sign in</h1>
        <p className="mt-1.5 text-sm text-muted-foreground">Control panel.</p>
        <LoginForm />
      </div>
    </main>
  )
}
