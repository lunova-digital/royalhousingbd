export class ApiError extends Error {
  constructor(
    public status: number,
    public payload: { message?: string; errors?: Record<string, Record<string, string>> },
  ) {
    super(payload.message ?? `Request failed with status ${status}`)
  }

  // The first validation message for a field, if the server sent one.
  fieldError(field: string): string | undefined {
    const rules = this.payload.errors?.[field]
    return rules ? Object.values(rules)[0] : undefined
  }

  // Every field error, flattened to one message each.
  fieldErrors(): Record<string, string> {
    const out: Record<string, string> = {}
    for (const [field, rules] of Object.entries(this.payload.errors ?? {})) {
      out[field] = Object.values(rules)[0] ?? ''
    }
    return out
  }
}

// Auth rides on Laravel's session cookie, which same-origin fetches carry
// automatically. CSRF: Laravel sets an XSRF-TOKEN cookie and accepts it back
// in the X-XSRF-TOKEN header on every mutating request.
function xsrfToken(): string {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]!) : ''
}

export async function api<T = unknown>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')
  headers.set('X-Requested-With', 'XMLHttpRequest')
  headers.set('X-XSRF-TOKEN', xsrfToken())
  if (init.body !== undefined && !(init.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json')
  }

  const response = await fetch(`/api${path}`, { ...init, headers })
  const payload = response.status === 204 ? {} : await response.json().catch(() => ({}))

  if (!response.ok) {
    // The session ended: reloading lets the server send the admin to the login page.
    if (response.status === 401) window.location.reload()
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
