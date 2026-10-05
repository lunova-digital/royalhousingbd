// The parts of the site module that client components may import: no
// database, no request headers. site.ts re-exports them for the server side.
import { whatsappLink } from './support/whatsapp'

export type IconName =
  | 'phone' | 'menu' | 'x' | 'pin' | 'mail' | 'clock' | 'home' | 'info' | 'building' | 'map' | 'image' | 'whatsapp'
  | 'arrow' | 'back' | 'chevron-left' | 'shield' | 'award' | 'users' | 'handshake' | 'cursor' | 'zoom' | 'zoom-out'
  | 'reset' | 'check' | 'send' | 'expand' | 'tag' | 'calendar' | 'facebook' | 'instagram' | 'youtube' | 'tiktok'

export interface NavItem {
  key: string
  label: string
  href: string
  icon: IconName
}

/** The sitemap from SRS §3, in order. */
export const NAV: NavItem[] = [
  { key: 'home', label: 'Home', href: '/', icon: 'home' },
  { key: 'about', label: 'About', href: '/about', icon: 'info' },
  { key: 'projects', label: 'Projects', href: '/projects', icon: 'building' },
  { key: 'map', label: 'Project Map', href: '/map', icon: 'map' },
  { key: 'gallery', label: 'Gallery', href: '/gallery', icon: 'image' },
  { key: 'contact', label: 'Contact', href: '/contact', icon: 'phone' },
]

/** Which nav item a public path belongs to. */
export function navKeyFor(pathname: string): string {
  if (pathname === '/') return 'home'
  if (/^\/(projects|property|plot|flat)(\/|$)/.test(pathname)) return 'projects'
  const hit = NAV.find((item) => item.href !== '/' && (pathname === item.href || pathname.startsWith(`${item.href}/`)))
  return hit?.key ?? ''
}

/** Turn a stored relative path ("uploads/…/x.jpg") into its URL. */
export function assetUrl(path: string | null | undefined): string {
  if (!path) return ''
  if (path.startsWith('http') || path.startsWith('/')) return path
  return `/storage/${path}`
}

export function whatsapp(settings: Record<string, string | undefined>, message: string): string {
  return whatsappLink(settings.whatsapp_number, message)
}
