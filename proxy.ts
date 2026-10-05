import { NextResponse, type NextRequest } from 'next/server'

import { SESSION_COOKIE } from '@/modules/auth/constants'

/** A guest asking for the admin is sent to the login page before anything renders; the admin layout re-checks the session itself. */
export function proxy(request: NextRequest) {
  if (!request.cookies.has(SESSION_COOKIE)) {
    return NextResponse.redirect(new URL('/login', request.url))
  }
  return NextResponse.next()
}

export const config = { matcher: ['/admin/:path*'] }
