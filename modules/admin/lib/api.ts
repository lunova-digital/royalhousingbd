export class ApiError extends Error {
  constructor(
    public status: number,
    public payload: { message?: string; errors?: Record<string, string[]> },
  ) {
    super(payload.message ?? `Request failed with status ${status}`)
  }

  // The first validation message for a field, if the server sent one.
  fieldError(field: string): string | undefined {
    return this.payload.errors?.[field]?.[0]
  }

  // Every field error, flattened to one message each.
  fieldErrors(): Record<string, string> {
    const out: Record<string, string> = {}
    for (const [field, messages] of Object.entries(this.payload.errors ?? {})) out[field] = messages[0] ?? ''
    return out
  }
}

// Auth rides on the session cookie, which same-origin fetches carry automatically.
export async function api<T = unknown>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')
  if (init.body !== undefined && !(init.body instanceof FormData)) headers.set('Content-Type', 'application/json')

  const response = await fetch(`/api${path}`, { ...init, headers, credentials: 'same-origin' })
  const payload = response.status === 204 ? {} : await response.json().catch(() => ({}))

  if (!response.ok) {
    // The session ended: a full navigation to the login page drops every bit of admin state.
    // eslint-disable-next-line @next/next/no-location-assign-relative-destination
    if (response.status === 401) window.location.href = '/login'
    throw new ApiError(response.status, payload)
  }
  return payload as T
}

// upload posts files as multipart form data.
export function upload<T = unknown>(path: string, form: FormData, method = 'POST'): Promise<T> {
  return api<T>(path, { method, body: form })
}

// Where a stored file (relative path from the API) is served.
export function assetUrl(path: string | null | undefined): string {
  if (!path) return ''
  if (path.startsWith('http') || path.startsWith('/')) return path
  return `/storage/${path}`
}
