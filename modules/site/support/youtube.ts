const ID = /^[A-Za-z0-9_-]{6,}$/

/**
 * Extract the video id from any usual YouTube URL shape (watch?v=, youtu.be/,
 * shorts/, embed/, live/), or return null.
 */
export function youtubeId(raw: string | null | undefined): string | null {
  const value = (raw ?? '').trim()
  if (value === '') return null
  if (ID.test(value) && !value.includes('.')) return value

  let url: URL
  try {
    url = new URL(value)
  } catch {
    return null
  }
  const host = url.hostname.toLowerCase().replace(/^(www|m)\./, '')
  const path = url.pathname.replace(/^\/+|\/+$/g, '')

  if (host === 'youtu.be') return segment(path)
  if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
    const v = url.searchParams.get('v')
    if (v) return segment(v)
    for (const prefix of ['shorts/', 'embed/', 'live/', 'v/']) {
      if (path.startsWith(prefix)) return segment(path.slice(prefix.length))
    }
  }
  return null
}

function segment(path: string): string | null {
  const first = path.replace(/^\/+|\/+$/g, '').split('/')[0]?.split('?')[0] ?? ''
  return ID.test(first) ? first : null
}
