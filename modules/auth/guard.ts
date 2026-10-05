import { HttpError } from '@/lib/http'

import { tokenFromRequest, viewerForToken, type Viewer } from './session'

/**
 * The `auth` middleware of the admin API: the session cookie must name a
 * live session, and a mutating request must come from this origin (the
 * cookie is SameSite=Lax, this is the second lock on the door).
 */
export async function requireAdmin(request: Request): Promise<Viewer> {
  const viewer = await viewerForToken(tokenFromRequest(request))
  if (!viewer) throw new HttpError(401, 'Unauthenticated.')

  if (!['GET', 'HEAD', 'OPTIONS'].includes(request.method)) {
    const origin = request.headers.get('origin')
    const host = request.headers.get('x-forwarded-host') ?? request.headers.get('host')
    if (origin && host && new URL(origin).host !== host) {
      throw new HttpError(403, 'Cross-origin request refused.')
    }
  }

  return viewer
}
