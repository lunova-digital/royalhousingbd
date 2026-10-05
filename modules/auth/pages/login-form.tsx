'use client'

import Link from 'next/link'
import { useActionState } from 'react'

import { login, type LoginState } from '../actions'

const inputClass =
  'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50'

export function LoginForm() {
  const [state, action, pending] = useActionState<LoginState, FormData>(login, {})

  return (
    <form action={action} className="mt-6 grid gap-4">
      <div className="grid gap-2">
        <label htmlFor="email" className="text-sm font-medium leading-none">Email</label>
        <input id="email" name="email" type="email" defaultValue={state.email ?? ''} autoComplete="username" required autoFocus className={inputClass} />
      </div>
      <div className="grid gap-2">
        <label htmlFor="password" className="text-sm font-medium leading-none">Password</label>
        <input id="password" name="password" type="password" autoComplete="current-password" required className={inputClass} />
      </div>
      {state.error && <p className="text-sm text-destructive">{state.error}</p>}
      <button type="submit" disabled={pending} className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
        {pending ? 'Signing in…' : 'Sign in'}
      </button>
      <p className="text-center text-sm text-muted-foreground"><Link href="/" className="underline underline-offset-4">Back to website</Link></p>
    </form>
  )
}
