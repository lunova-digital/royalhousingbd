// NFR-003: a small in-memory limiter for the login form (10 attempts a minute per address).
const WINDOW_MS = 60_000
const MAX = 10
const hits = new Map<string, number[]>()

export function tooManyAttempts(key: string): boolean {
  const now = Date.now()
  const recent = (hits.get(key) ?? []).filter((t) => now - t < WINDOW_MS)
  recent.push(now)
  hits.set(key, recent)
  if (hits.size > 10_000) hits.clear()
  return recent.length > MAX
}
